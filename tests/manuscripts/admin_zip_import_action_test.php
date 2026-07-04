<?php

namespace App\Manuscripts;

$mockFields = [];
$mockMeta = [];
$mockAttachmentUrls = [];
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

function wp_get_attachment_url($attachmentId)
{
    global $mockAttachmentUrls;

    return isset($mockAttachmentUrls[$attachmentId]) ? $mockAttachmentUrls[$attachmentId] : '';
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

    protected static function redirectBack($postId, $notice = null, $source = 'worker')
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
    global $mockFields, $mockMeta, $mockAttachmentUrls, $mockUpdatedMeta, $mockActions, $mockVerifyNonce, $mockCanEdit;

    $mockFields = [];
    $mockMeta = [];
    $mockAttachmentUrls = [];
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
assertTrueValue(!in_array('admin_post_chao_process_manuscript_pdf', $registeredHooks, true), 'Register should no longer include the legacy PDF worker admin action');

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
$mockFields[77]['manuscript_zip_file'] = 321;
$mockAttachmentUrls[321] = 'http://example.test/uploads/venancia.zip';
$_GET = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
];

TestAdmin::importZip();

assertSameValue(1, count(TestAdmin::$redirects), 'Unavailable ZIP pipeline should trigger a single redirect');
assertSameValue('import_unavailable', TestAdmin::$redirects[0]['notice'], 'Unavailable ZIP pipeline should redirect with import_unavailable notice');
assertSameValue('import', TestAdmin::$redirects[0]['source'], 'Unavailable ZIP pipeline should redirect using the import notice key');
assertSameValue('', $mockMeta[77]['chao_manuscript_import_last_error'], 'Unavailable ZIP pipeline should clear the last import error');

echo 'admin_zip_import_action_test: ok' . PHP_EOL;
