<?php

namespace App\Manuscripts;

$mockFields = [];
$mockMeta = [];
$mockAttachmentUrls = [];
$mockAttachmentPaths = [];
$mockUpdatedMeta = [];
$mockActions = [];
$mockVerifyNonce = true;
$mockCanEdit = true;

function add_action($hook, $callback)
{
    global $mockActions;

    $mockActions[] = [
        'hook' => $hook,
        'callback' => $callback,
    ];
}

function get_field($fieldName, $postId, $formatValue = false)
{
    global $mockFields;

    return isset($mockFields[$postId][$fieldName]) ? $mockFields[$postId][$fieldName] : 0;
}

function get_post_meta($postId, $key, $single)
{
    global $mockMeta;

    return isset($mockMeta[$postId][$key]) ? $mockMeta[$postId][$key] : '';
}

function update_post_meta($postId, $key, $value)
{
    global $mockMeta, $mockUpdatedMeta;

    if (!isset($mockMeta[$postId])) {
        $mockMeta[$postId] = [];
    }

    $mockMeta[$postId][$key] = $value;
    $mockUpdatedMeta[] = [
        'post_id' => $postId,
        'key' => $key,
        'value' => $value,
    ];
}

function delete_post_meta($postId, $key)
{
    global $mockMeta, $mockUpdatedMeta;

    if (isset($mockMeta[$postId]) && array_key_exists($key, $mockMeta[$postId])) {
        unset($mockMeta[$postId][$key]);
    }

    $mockUpdatedMeta[] = [
        'post_id' => $postId,
        'key' => $key,
        'value' => null,
    ];
}

function wp_get_attachment_url($attachmentId)
{
    global $mockAttachmentUrls;

    return isset($mockAttachmentUrls[$attachmentId]) ? $mockAttachmentUrls[$attachmentId] : '';
}

function get_attached_file($attachmentId)
{
    global $mockAttachmentPaths;

    return isset($mockAttachmentPaths[$attachmentId]) ? $mockAttachmentPaths[$attachmentId] : '';
}

function wp_verify_nonce($nonce, $action)
{
    global $mockVerifyNonce;

    return $mockVerifyNonce;
}

function current_user_can($capability, $postId)
{
    global $mockCanEdit;

    return $mockCanEdit;
}

function __($message, $domain = null)
{
    return $message;
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class TestAdmin extends Admin
{
    public static $redirects = [];

    protected static function redirectBack($postId, $notice = null, $source = 'import')
    {
        static::$redirects[] = [
            'post_id' => $postId,
            'notice' => $notice,
            'source' => $source,
        ];
    }
}

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function resetImportZipState()
{
    global $mockFields, $mockMeta, $mockAttachmentUrls, $mockAttachmentPaths, $mockUpdatedMeta, $mockActions, $mockVerifyNonce, $mockCanEdit;

    $mockFields = [];
    $mockMeta = [];
    $mockAttachmentUrls = [];
    $mockAttachmentPaths = [];
    $mockUpdatedMeta = [];
    $mockActions = [];
    $mockVerifyNonce = true;
    $mockCanEdit = true;
    TestAdmin::$redirects = [];
    $_GET = [];
    $_POST = [];
}

resetImportZipState();
TestAdmin::register();

$registeredHooks = array_map(function ($registration) {
    return $registration['hook'];
}, $mockActions);

assertTrueValue(in_array('admin_post_chao_import_manuscript_zip', $registeredHooks, true), 'Register should include the ZIP import admin action');
assertTrueValue(!in_array('admin_post_chao_process_manuscript_pdf', $registeredHooks, true), 'Register should no longer include the legacy PDF admin action');

resetImportZipState();
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];

TestAdmin::importZip();

assertSameValue(1, count(TestAdmin::$redirects), 'Missing ZIP should trigger a single redirect');
assertSameValue('missing_zip', TestAdmin::$redirects[0]['notice'], 'Missing ZIP should redirect with missing_zip notice');
assertSameValue('import', TestAdmin::$redirects[0]['source'], 'Missing ZIP should redirect using the import notice key');
assertSameValue('Pacote ZIP do manuscrito ausente ou invalido.', $mockMeta[77]['chao_manuscript_import_last_error'], 'Missing ZIP should store the import error message');

resetImportZipState();
$zipPath = sys_get_temp_dir() . '/venancia-admin-action.zip';
$zip = new \ZipArchive();
$zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
$zip->addFromString('venanciaCaderno_0051B.jpg', 'fake-image');
$zip->addFromString('venanciaCaderno_0051B.xml', '<?xml version="1.0" encoding="UTF-8"?><alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="10" HEIGHT="20"><PrintSpace><TextBlock><TextLine HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"><String CONTENT="Casa" HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"/></TextLine></TextBlock></PrintSpace></Page></Layout></alto>');
$zip->close();

$mockFields[77]['manuscript_zip_file'] = 321;
$mockAttachmentUrls[321] = 'http://example.test/uploads/venancia.zip';
$mockAttachmentPaths[321] = $zipPath;
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];

TestAdmin::importZip();

assertSameValue(1, count(TestAdmin::$redirects), 'ZIP import should trigger a single redirect');
assertSameValue('success', TestAdmin::$redirects[0]['notice'], 'ZIP import should redirect with success notice');
assertSameValue('import', TestAdmin::$redirects[0]['source'], 'ZIP import should redirect using the import notice key');
assertSameValue('', $mockMeta[77]['chao_manuscript_import_last_error'], 'ZIP import should clear the last import error');
assertSameValue([], $mockMeta[77]['chao_manuscript_import_last_errors'], 'ZIP import should persist an empty error list when the package is valid');
assertSameValue(321, $mockMeta[77]['chao_manuscript_import_source']['zip_id'], 'ZIP import should persist the imported attachment id');
assertSameValue($zipPath, $mockMeta[77]['chao_manuscript_import_source']['zip_path'], 'ZIP import should persist the imported ZIP path');
assertSameValue('venancia.zip', $mockMeta[77]['chao_manuscript_import_last_snapshot']['zip_name'], 'ZIP import should persist the imported ZIP filename in the snapshot');
assertSameValue(1, $mockMeta[77]['chao_manuscript_import_last_snapshot']['page_count'], 'ZIP import should persist imported pages in the snapshot');
assertSameValue('venanciaCaderno_0051B', $mockMeta[77]['chao_manuscript_import_last_snapshot']['pages'][0]['basename'], 'ZIP import should persist imported page basenames');
assertSameValue('http://example.test/uploads/manuscript-imports/77/venancia/venanciaCaderno_0051B.jpg', $mockMeta[77]['chao_manuscript_import_last_snapshot']['pages'][0]['image_url'], 'ZIP import should persist extracted image URLs for the viewer');

$extractedImagePath = $mockMeta[77]['chao_manuscript_import_last_snapshot']['pages'][0]['image_path'];
@unlink($extractedImagePath);
@rmdir(dirname($extractedImagePath));
@rmdir(dirname(dirname($extractedImagePath)));
@rmdir(dirname(dirname(dirname($extractedImagePath))));

@unlink($zipPath);

resetImportZipState();
$zipPath = sys_get_temp_dir() . '/venancia-admin-fallback.zip';
$zip = new \ZipArchive();
$zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
$zip->addFromString('venanciaCaderno_0052A.jpg', 'fake-image');
$zip->addFromString('venanciaCaderno_0052A.xml', '<?xml version="1.0" encoding="UTF-8"?><alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="10" HEIGHT="20"><PrintSpace><TextBlock><TextLine HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"><String CONTENT="Margem" HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"/></TextLine></TextBlock></PrintSpace></Page></Layout></alto>');
$zip->close();

$mockMeta[77]['chao_manuscript_import_source'] = [
    'zip_id' => 654,
    'zip_path' => $zipPath,
    'zip_url' => 'http://example.test/uploads/venancia-fallback.zip',
    'zip_filename' => 'venancia-fallback.zip',
];
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];

TestAdmin::importZip();

assertSameValue(1, count(TestAdmin::$redirects), 'Stored import source fallback should still trigger a single redirect');
assertSameValue('success', TestAdmin::$redirects[0]['notice'], 'Stored import source fallback should still import successfully');
assertSameValue('venancia-fallback.zip', $mockMeta[77]['chao_manuscript_import_last_snapshot']['zip_name'], 'Stored import source fallback should keep the previous ZIP filename');
assertSameValue('venanciaCaderno_0052A', $mockMeta[77]['chao_manuscript_import_last_snapshot']['pages'][0]['basename'], 'Stored import source fallback should still import the ZIP pages');

$fallbackExtractedImagePath = $mockMeta[77]['chao_manuscript_import_last_snapshot']['pages'][0]['image_path'];
@unlink($fallbackExtractedImagePath);
@rmdir(dirname($fallbackExtractedImagePath));
@rmdir(dirname(dirname($fallbackExtractedImagePath)));
@rmdir(dirname(dirname(dirname($fallbackExtractedImagePath))));

@unlink($zipPath);

echo 'admin_zip_import_action_test: ok' . PHP_EOL;
