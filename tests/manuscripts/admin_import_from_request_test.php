<?php

namespace App\Manuscripts;

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', sys_get_temp_dir() . '/wp-content-import-request-test');
}

$mockMeta = [];
$mockVerifyNonce = true;
$mockCanEdit = true;
$mockJsonSuccess = null;
$mockJsonError = null;

class WpDieException extends \RuntimeException
{
}

function get_post_meta($postId, $key, $single)
{
    global $mockMeta;

    return isset($mockMeta[$postId][$key]) ? $mockMeta[$postId][$key] : '';
}

function update_post_meta($postId, $key, $value)
{
    global $mockMeta;

    if (!isset($mockMeta[$postId])) {
        $mockMeta[$postId] = [];
    }

    $mockMeta[$postId][$key] = $value;
}

function delete_post_meta($postId, $key)
{
    global $mockMeta;

    unset($mockMeta[$postId][$key]);
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

function wp_send_json_success($value)
{
    global $mockJsonSuccess;

    $mockJsonSuccess = $value;
    throw new \RuntimeException('json-success');
}

function wp_send_json_error($value, $status_code = null)
{
    global $mockJsonError;

    $mockJsonError = [
        'value' => $value,
        'status_code' => $status_code,
    ];

    throw new \RuntimeException('json-error');
}

function wp_create_nonce($action)
{
    return 'source-folder-nonce';
}

function admin_url()
{
    return 'http://example.test/wp-admin/';
}

function add_query_arg($queryArgs, $url)
{
    return $url . '?' . http_build_query($queryArgs);
}

function __($message, $domain = null)
{
    return $message;
}

function esc_html__($message, $domain = null)
{
    return $message;
}

function esc_html($value)
{
    return (string) $value;
}

function esc_url($value)
{
    return (string) $value;
}

function esc_attr($value)
{
    return (string) $value;
}

function content_url($path = '')
{
    return 'http://example.test/wp-content/' . ltrim((string) $path, '/');
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

function resetImportRequestState()
{
    global $mockMeta, $mockVerifyNonce, $mockCanEdit, $mockJsonSuccess, $mockJsonError;

    $mockMeta = [];
    $mockVerifyNonce = true;
    $mockCanEdit = true;
    $mockJsonSuccess = null;
    $mockJsonError = null;
    TestAdmin::$redirects = [];
    $_GET = [];
    $_POST = [];
}

removeDirectoryRecursively(WP_CONTENT_DIR);

$sourceDir = WP_CONTENT_DIR . '/manuscripts-source/venancia-request';
mkdir($sourceDir, 0777, true);
file_put_contents($sourceDir . '/venanciaCaderno_0051B.jpg', 'fake-image');
file_put_contents($sourceDir . '/venanciaCaderno_0051B.xml', '<?xml version="1.0" encoding="UTF-8"?><alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="10" HEIGHT="20"><PrintSpace><TextBlock><TextLine HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"><String CONTENT="Casa" HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"/></TextLine></TextBlock></PrintSpace></Page></Layout></alto>');

resetImportRequestState();
$_POST = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
    'response_format' => 'json',
];
$mockVerifyNonce = false;

try {
    TestAdmin::importSourceFolder();
} catch (\RuntimeException $exception) {
    assertSameValue('json-error', $exception->getMessage(), 'JSON import should return a structured error for an invalid nonce');
}

assertSameValue('Solicitacao invalida.', $mockJsonError['value']['message'], 'JSON invalid nonce response should include the request error message');
assertSameValue(403, $mockJsonError['status_code'], 'JSON invalid nonce response should use HTTP 403');
assertSameValue([], TestAdmin::$redirects, 'JSON invalid nonce response should not redirect');

resetImportRequestState();
$_POST = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
    'response_format' => 'json',
];
$mockCanEdit = false;

try {
    TestAdmin::importSourceFolder();
} catch (\RuntimeException $exception) {
    assertSameValue('json-error', $exception->getMessage(), 'JSON import should return a structured error for permission denial');
}

assertSameValue('Voce nao tem permissao para importar este manuscrito.', $mockJsonError['value']['message'], 'JSON permission response should include the permission error message');
assertSameValue(403, $mockJsonError['status_code'], 'JSON permission response should use HTTP 403');
assertSameValue([], TestAdmin::$redirects, 'JSON permission response should not redirect');

resetImportRequestState();
$_POST = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
    'subfolder' => '../invalid',
    'response_format' => 'json',
];

try {
    TestAdmin::importSourceFolder();
} catch (\RuntimeException $exception) {
    assertSameValue('json-error', $exception->getMessage(), 'JSON import should return a structured error when the source folder is missing');
}

assertSameValue('Pasta fonte do manuscrito ausente ou invalida.', $mockJsonError['value']['message'], 'JSON missing-folder response should include the source-folder error message');
assertSameValue(422, $mockJsonError['status_code'], 'JSON missing-folder response should use HTTP 422');
assertSameValue('Pasta fonte do manuscrito ausente ou invalida.', $mockJsonError['value']['summary']['last_error'], 'JSON missing-folder response should include the summary payload');
assertTrueValue(strpos($mockJsonError['value']['metabox'], 'Subpasta informada:') !== false, 'JSON missing-folder response should include the rendered import metabox markup');
assertTrueValue(strpos($mockJsonError['value']['pages_metabox'], 'Importe a pasta fonte do manuscrito') !== false, 'JSON missing-folder response should include the rendered pages metabox markup');
assertSameValue([], TestAdmin::$redirects, 'JSON missing-folder response should not redirect');

resetImportRequestState();
$_POST = [
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
    'subfolder' => 'venancia-request',
    'response_format' => 'json',
];

try {
    TestAdmin::importSourceFolder();
} catch (\RuntimeException $exception) {
    assertSameValue('json-success', $exception->getMessage(), 'Request-driven import should return JSON success');
}

assertSameValue('venancia-request', $mockMeta[77]['manuscript_source_subfolder'], 'Request-driven import should persist the typed subfolder value');
assertSameValue('venancia-request', $mockMeta[77]['chao_manuscript_import_source']['source_subfolder'], 'Request-driven import should keep the imported source metadata');
assertSameValue(1, $mockMeta[77]['chao_manuscript_import_last_snapshot']['page_count'], 'Request-driven import should persist the snapshot');
assertSameValue(1, $mockJsonSuccess['summary']['page_count'], 'Request-driven import should return the summary payload');
assertTrueValue(strpos($mockJsonSuccess['metabox'], 'Importar da subpasta') !== false, 'Request-driven import should return the rendered import metabox markup');
assertTrueValue(strpos($mockJsonSuccess['pages_metabox'], 'Paginas importadas: 1') !== false, 'Request-driven import should return the rendered pages metabox markup');

echo 'admin_import_from_request_test: ok' . PHP_EOL;
