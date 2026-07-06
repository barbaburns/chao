<?php

namespace App\Manuscripts;

$mockActions = [];
$mockInsertedPosts = [];
$mockCanCreate = true;
$mockCapabilityChecks = [];
$mockVerifyNonce = true;
$mockNonceChecks = [];
$mockInsertResult = 321;
$mockInsertWpErrorFlag = null;
$mockJsonSuccess = null;
$mockJsonError = null;

function add_action($hook, $callback)
{
    global $mockActions;

    $mockActions[] = [
        'hook' => $hook,
        'callback' => $callback,
    ];
}

function current_user_can($capability)
{
    global $mockCanCreate, $mockCapabilityChecks;

    $mockCapabilityChecks[] = $capability;

    return $mockCanCreate;
}

function wp_verify_nonce($nonce, $action)
{
    global $mockVerifyNonce, $mockNonceChecks;

    $mockNonceChecks[] = [
        'nonce' => $nonce,
        'action' => $action,
    ];

    return $mockVerifyNonce;
}

function wp_insert_post($postarr, $wp_error = false)
{
    global $mockInsertedPosts, $mockInsertResult, $mockInsertWpErrorFlag;

    $mockInsertedPosts[] = $postarr;
    $mockInsertWpErrorFlag = $wp_error;

    return $mockInsertResult;
}

function is_wp_error($value)
{
    return $value instanceof \WP_Error;
}

function admin_url($path = '')
{
    $path = ltrim((string) $path, '/');

    return 'http://example.test/wp-admin' . ($path === '' ? '' : '/' . $path);
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

function __($message, $domain = null)
{
    return $message;
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

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

function assertJsonError($expectedMessage, $expectedStatusCode, callable $callback, $message)
{
    global $mockJsonError;

    try {
        $callback();
    } catch (\RuntimeException $exception) {
        assertSameValue('json-error', $exception->getMessage(), $message);
        assertSameValue($expectedMessage, $mockJsonError['value']['message'], $message . ' message');
        assertSameValue($expectedStatusCode, $mockJsonError['status_code'], $message . ' status code');
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected JSON error response.' . PHP_EOL);
    exit(1);
}

function resetDraftCreationState()
{
    global $mockActions, $mockInsertedPosts, $mockCanCreate, $mockCapabilityChecks, $mockVerifyNonce, $mockNonceChecks, $mockInsertResult, $mockInsertWpErrorFlag, $mockJsonSuccess, $mockJsonError;

    $mockActions = [];
    $mockInsertedPosts = [];
    $mockCanCreate = true;
    $mockCapabilityChecks = [];
    $mockVerifyNonce = true;
    $mockNonceChecks = [];
    $mockInsertResult = 321;
    $mockInsertWpErrorFlag = null;
    $mockJsonSuccess = null;
    $mockJsonError = null;
    $_POST = [];
}

resetDraftCreationState();
Admin::register();

$registeredHooks = array_map(function ($registration) {
    return $registration['hook'];
}, $mockActions);

assertTrueValue(in_array('admin_post_chao_create_manuscript_draft', $registeredHooks, true), 'Register should include the manuscript draft creation endpoint');

resetDraftCreationState();
assertJsonError('Solicitacao invalida.', 403, function () {
    Admin::createDraftForImport();
}, 'Missing nonce should be rejected');

resetDraftCreationState();
$_POST = [
    'chao_manuscript_import_nonce' => 'nonce-value',
];
$mockVerifyNonce = false;

assertJsonError('Solicitacao invalida.', 403, function () {
    Admin::createDraftForImport();
}, 'Invalid nonce should be rejected');

resetDraftCreationState();
$_POST = [
    'chao_manuscript_import_nonce' => 'nonce-value',
];
$mockCanCreate = false;

assertJsonError('Voce nao tem permissao para criar este manuscrito.', 403, function () {
    Admin::createDraftForImport();
}, 'Permission denial should be rejected');

resetDraftCreationState();
$_POST = [
    'chao_manuscript_import_nonce' => 'nonce-value',
];
$mockInsertResult = 0;

assertJsonError('Nao foi possivel criar o rascunho do manuscrito.', 500, function () {
    Admin::createDraftForImport();
}, 'Insert failure should return a JSON error');

resetDraftCreationState();
$_POST = [
    'chao_manuscript_import_nonce' => 'nonce-value',
];

try {
    Admin::createDraftForImport();
} catch (\RuntimeException $exception) {
    assertSameValue('json-success', $exception->getMessage(), 'Draft creation should finish with a JSON success response');
}

assertSameValue(1, count($mockInsertedPosts), 'Draft creation should insert one manuscript draft');
assertSameValue('manuscrito', $mockInsertedPosts[0]['post_type'], 'Draft creation should create a manuscript post');
assertSameValue('draft', $mockInsertedPosts[0]['post_status'], 'Draft creation should create a draft post');
assertSameValue('', $mockInsertedPosts[0]['post_title'], 'Draft creation should start with an empty title');
assertSameValue([
    'nonce' => 'nonce-value',
    'action' => 'chao_manuscript_import_create_draft',
], $mockNonceChecks[0], 'Draft creation should verify the draft-creation nonce action');
assertSameValue(['edit_posts'], $mockCapabilityChecks, 'Draft creation should require edit_posts capability');
assertSameValue(true, $mockInsertWpErrorFlag, 'Draft creation should request a WP_Error result from wp_insert_post');
assertSameValue(321, $mockJsonSuccess['post_id'], 'Draft creation should return the new post ID');
assertTrueValue(strpos($mockJsonSuccess['edit_url'], 'post.php') !== false, 'Draft creation should return the edit URL');
assertTrueValue(strpos($mockJsonSuccess['edit_url'], 'post=321') !== false, 'Draft creation should return an edit URL for the new post');

echo 'admin_create_draft_test: ok' . PHP_EOL;
