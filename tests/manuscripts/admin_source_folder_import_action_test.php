<?php

namespace App\Manuscripts;

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', sys_get_temp_dir() . '/wp-content-source-folder-test');
}

$mockFields = [];
$mockMeta = [];
$mockUpdatedMeta = [];
$mockActions = [];
$mockVerifyNonce = true;
$mockCanEdit = true;

class WpDieException extends \RuntimeException
{
}

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

    return isset($mockFields[$postId][$fieldName]) ? $mockFields[$postId][$fieldName] : '';
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

function wp_die($message)
{
    throw new WpDieException((string) $message);
}

function __($message, $domain = null)
{
    return $message;
}

function content_url($path = '')
{
    $path = ltrim((string) $path, '/');

    return 'http://example.test/wp-content' . ($path === '' ? '' : '/' . $path);
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

function assertThrowsMessage($expectedMessage, callable $callback, $message)
{
    try {
        $callback();
    } catch (WpDieException $exception) {
        assertSameValue($expectedMessage, $exception->getMessage(), $message);
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected exception: ' . var_export($expectedMessage, true) . PHP_EOL);
    exit(1);
}

function resetImportSourceFolderState()
{
    global $mockFields, $mockMeta, $mockUpdatedMeta, $mockActions, $mockVerifyNonce, $mockCanEdit;

    $mockFields = [];
    $mockMeta = [];
    $mockUpdatedMeta = [];
    $mockActions = [];
    $mockVerifyNonce = true;
    $mockCanEdit = true;
    TestAdmin::$redirects = [];
    $_GET = [];
    $_POST = [];
}

function removeDirectoryRecursively($directory)
{
    if (!is_dir($directory)) {
        return;
    }

    $entries = scandir($directory);

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $entry;

        if (is_dir($path)) {
            removeDirectoryRecursively($path);
            continue;
        }

        unlink($path);
    }

    rmdir($directory);
}

resetImportSourceFolderState();
TestAdmin::register();

$registeredHooks = array_map(function ($registration) {
    return $registration['hook'];
}, $mockActions);

assertTrueValue(in_array('admin_post_chao_import_manuscript_source_folder', $registeredHooks, true), 'Register should include the source-folder import admin action');
assertTrueValue(!in_array('admin_post_chao_import_manuscript_zip', $registeredHooks, true), 'Register should no longer include the ZIP import admin action');

resetImportSourceFolderState();
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];
$mockVerifyNonce = false;

assertThrowsMessage('Solicitacao invalida.', function () {
    TestAdmin::importSourceFolder();
}, 'Invalid nonce should be rejected with wp_die');

resetImportSourceFolderState();
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];
$mockCanEdit = false;

assertThrowsMessage('Voce nao tem permissao para importar este manuscrito.', function () {
    TestAdmin::importSourceFolder();
}, 'Permission denial should be rejected with wp_die');

resetImportSourceFolderState();
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];

TestAdmin::importSourceFolder();

assertSameValue(1, count(TestAdmin::$redirects), 'Missing source folder should trigger a single redirect');
assertSameValue('missing_source_folder', TestAdmin::$redirects[0]['notice'], 'Missing source folder should redirect with missing_source_folder notice');
assertSameValue('Pasta fonte do manuscrito ausente ou invalida.', $mockMeta[77]['chao_manuscript_import_last_error'], 'Missing source folder should store the import error message');

resetImportSourceFolderState();
$basePath = WP_CONTENT_DIR . '/manuscripts-source';
$subfolder = 'venancia-caderno-admin-action';
$sourceDir = $basePath . '/' . $subfolder;
mkdir($sourceDir, 0777, true);

$altoXml = '<?xml version="1.0" encoding="UTF-8"?><alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="10" HEIGHT="20"><PrintSpace><TextBlock><TextLine HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"><String CONTENT="Casa" HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"/></TextLine></TextBlock></PrintSpace></Page></Layout></alto>';
file_put_contents($sourceDir . '/venanciaCaderno_0051B.jpg', 'fake-image');
file_put_contents($sourceDir . '/venanciaCaderno_0051B.xml', $altoXml);

$mockFields[77]['manuscript_source_subfolder'] = $subfolder;
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];

TestAdmin::importSourceFolder();

assertSameValue(1, count(TestAdmin::$redirects), 'Source-folder import should trigger a single redirect');
assertSameValue('success', TestAdmin::$redirects[0]['notice'], 'Source-folder import should redirect with success notice');
assertSameValue('', $mockMeta[77]['chao_manuscript_import_last_error'], 'Source-folder import should clear the last import error');
assertSameValue($subfolder, $mockMeta[77]['chao_manuscript_import_source']['source_subfolder'], 'Source-folder import should persist the imported source subfolder');
assertSameValue($sourceDir, $mockMeta[77]['chao_manuscript_import_source']['source_path'], 'Source-folder import should persist the imported source path');
assertSameValue('http://example.test/wp-content/manuscripts-source/' . $subfolder, $mockMeta[77]['chao_manuscript_import_source']['source_url'], 'Source-folder import should persist the imported source URL');
assertSameValue($subfolder, $mockMeta[77]['chao_manuscript_import_last_snapshot']['source_name'], 'Source-folder import should persist the imported source folder name in the snapshot');
assertSameValue(1, $mockMeta[77]['chao_manuscript_import_last_snapshot']['page_count'], 'Source-folder import should persist imported pages in the snapshot');

removeDirectoryRecursively(WP_CONTENT_DIR);

resetImportSourceFolderState();
$staleSubfolder = 'venancia-caderno-stale-source';
$staleSourceDir = WP_CONTENT_DIR . '/manuscripts-source/' . $staleSubfolder;
mkdir($staleSourceDir, 0777, true);
file_put_contents($staleSourceDir . '/venanciaCaderno_0060A.jpg', 'fake-image');
file_put_contents($staleSourceDir . '/venanciaCaderno_0060A.xml', $altoXml);
$mockMeta[77]['chao_manuscript_import_source'] = [
    'source_subfolder' => $staleSubfolder,
    'source_path' => $staleSourceDir,
    'source_url' => 'http://example.test/wp-content/manuscripts-source/' . $staleSubfolder,
];
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];

TestAdmin::importSourceFolder();

assertSameValue(1, count(TestAdmin::$redirects), 'Stored source metadata without a current field value should still trigger a single redirect');
assertSameValue('missing_source_folder', TestAdmin::$redirects[0]['notice'], 'Stored source metadata without a current field value should not be used as an import fallback');
assertSameValue('Pasta fonte do manuscrito ausente ou invalida.', $mockMeta[77]['chao_manuscript_import_last_error'], 'Stored source metadata without a current field value should still store the missing-source error');

removeDirectoryRecursively(WP_CONTENT_DIR);

resetImportSourceFolderState();
$mockFields[77]['manuscript_source_subfolder'] = '../invalid';
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];

TestAdmin::importSourceFolder();

assertSameValue(1, count(TestAdmin::$redirects), 'Invalid source folder should trigger a single redirect');
assertSameValue('missing_source_folder', TestAdmin::$redirects[0]['notice'], 'Invalid source folder should redirect with missing_source_folder notice');
assertSameValue('Pasta fonte do manuscrito ausente ou invalida.', $mockMeta[77]['chao_manuscript_import_last_error'], 'Invalid source folder should store the source-folder error message');

echo 'admin_source_folder_import_action_test: ok' . PHP_EOL;
