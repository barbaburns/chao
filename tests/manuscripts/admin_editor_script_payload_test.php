<?php

namespace App\Manuscripts;

$mockScreen = (object) ['post_type' => 'manuscrito'];
$mockEnqueuedScripts = [];
$mockInlineScripts = [];

function get_current_screen()
{
    global $mockScreen;

    return $mockScreen;
}

function wp_enqueue_script($handle, $src = '', $deps = [], $ver = null, $in_footer = false)
{
    global $mockEnqueuedScripts;

    $mockEnqueuedScripts[] = [
        'handle' => $handle,
        'src' => $src,
        'deps' => $deps,
        'in_footer' => $in_footer,
    ];
}

function wp_add_inline_script($handle, $script, $position = 'after')
{
    global $mockInlineScripts;

    $mockInlineScripts[] = [
        'handle' => $handle,
        'script' => $script,
        'position' => $position,
    ];
}

function admin_url($path = '')
{
    return 'http://example.test/wp-admin/' . ltrim((string) $path, '/');
}

function get_theme_file_uri($path = '')
{
    return 'http://example.test/wp-content/themes/chao/' . ltrim((string) $path, '/');
}

function wp_create_nonce($action)
{
    return 'nonce-value';
}

function wp_json_encode($value)
{
    return json_encode($value);
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$_GET = [
    'post' => '77',
];

Admin::enqueueImportBeforeSaveScript('post.php');

assertTrueValue(count($mockEnqueuedScripts) > 0, 'Import-before-save script should enqueue an editor-side admin script');
assertTrueValue($mockEnqueuedScripts[0]['src'] === 'http://example.test/wp-content/themes/chao/resources/assets/scripts/admin/manuscript-import-before-save.js', 'Import-before-save script should load from the direct theme file URL');
assertTrueValue(count($mockInlineScripts) > 0, 'Import-before-save script should bootstrap configuration inline');
assertTrueValue($mockInlineScripts[0]['handle'] === 'chao/manuscript-import-before-save', 'Import-before-save bootstrap should attach to the manuscript import script handle');
assertTrueValue($mockInlineScripts[0]['position'] === 'before', 'Import-before-save bootstrap should be added before the manuscript import script');
assertTrueValue(strpos($mockInlineScripts[0]['script'], 'chao_create_manuscript_draft') !== false, 'Import-before-save script payload should expose the draft creation endpoint');
assertTrueValue(strpos($mockInlineScripts[0]['script'], 'chao_import_manuscript_source_folder') !== false, 'Import-before-save script payload should expose the import endpoint');
assertTrueValue(strpos($mockInlineScripts[0]['script'], '"postId":77') !== false, 'Import-before-save script payload should include the current manuscript post id');

echo 'admin_editor_script_payload_test: ok' . PHP_EOL;
