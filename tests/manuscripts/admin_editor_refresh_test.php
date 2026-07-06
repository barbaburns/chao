<?php

namespace App\Manuscripts;

$mockActions = [];
$mockEnqueuedScripts = [];
$mockInlineScripts = [];
$mockScreen = null;

function add_action($hook, $callback)
{
    global $mockActions;

    $mockActions[] = [
        'hook' => $hook,
        'callback' => $callback,
    ];
}

function get_current_screen()
{
    global $mockScreen;

    return $mockScreen;
}

function wp_enqueue_script($handle)
{
    global $mockEnqueuedScripts;

    $mockEnqueuedScripts[] = $handle;
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

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function resetEditorRefreshState()
{
    global $mockActions, $mockEnqueuedScripts, $mockInlineScripts, $mockScreen;

    $mockActions = [];
    $mockEnqueuedScripts = [];
    $mockInlineScripts = [];
    $mockScreen = null;
}

resetEditorRefreshState();
Admin::register();

$registeredHooks = array_map(function ($registration) {
    return $registration['hook'];
}, $mockActions);

assertTrueValue(in_array('admin_enqueue_scripts', $registeredHooks, true), 'Register should include the admin editor refresh hook');
assertTrueValue(in_array('admin_footer-post.php', $registeredHooks, true), 'Register should include the post editor footer refresh hook');
assertTrueValue(in_array('admin_footer-post-new.php', $registeredHooks, true), 'Register should include the new post editor footer refresh hook');

resetEditorRefreshState();
$mockScreen = (object) [
    'post_type' => 'post',
];

Admin::enqueueEditorRefreshScript('post.php');

assertTrueValue($mockEnqueuedScripts === [], 'Editor refresh script should not load for other post types');
assertTrueValue($mockInlineScripts === [], 'Editor refresh script should not inject inline code for other post types');

resetEditorRefreshState();
$mockScreen = (object) [
    'post_type' => 'manuscrito',
];

Admin::enqueueEditorRefreshScript('post.php');

assertTrueValue(in_array('wp-data', $mockEnqueuedScripts, true), 'Editor refresh script should enqueue wp-data on manuscript edit screens');
assertTrueValue(in_array('wp-dom-ready', $mockEnqueuedScripts, true), 'Editor refresh script should enqueue wp-dom-ready on manuscript edit screens');
assertTrueValue(count($mockInlineScripts) === 1, 'Editor refresh script should inject exactly one inline script');
assertTrueValue($mockInlineScripts[0]['handle'] === 'wp-dom-ready', 'Editor refresh script should be attached to wp-dom-ready');
assertTrueValue(strpos($mockInlineScripts[0]['script'], 'field_manuscript_source_subfolder') !== false, 'Editor refresh script should watch the manuscript source subfolder field');
assertTrueValue(strpos($mockInlineScripts[0]['script'], 'window.location.reload()') !== false, 'Editor refresh script should reload the editor after a successful save when the import button is stale');

ob_start();
Admin::renderEditorRefreshScript();
$footerMarkup = ob_get_clean();

assertTrueValue(strpos($footerMarkup, '<script>') !== false, 'Editor refresh footer should render an inline script tag');
assertTrueValue(strpos($footerMarkup, 'field_manuscript_source_subfolder') !== false, 'Editor refresh footer should include the manuscript source subfolder watcher');
assertTrueValue(strpos($footerMarkup, 'window.location.reload()') !== false, 'Editor refresh footer should include the reload behavior');

echo 'admin_editor_refresh_test: ok' . PHP_EOL;
