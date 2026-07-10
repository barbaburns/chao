<?php

$distRoot = dirname(__DIR__, 2) . '/dist';
$assetsPath = $distRoot . '/assets.json';
$scriptPaths = [$distRoot . '/scripts/main.js'];

if (file_exists($assetsPath)) {
    $assets = json_decode(file_get_contents($assetsPath), true);
    $manifestPath = $distRoot . '/' . ($assets['scripts/main.js'] ?? '');

    if (is_file($manifestPath)) {
        $scriptPaths[] = $manifestPath;
    }
}

$scriptPaths = array_values(array_filter($scriptPaths, 'is_file'));
usort($scriptPaths, function ($left, $right) {
    return filemtime($right) <=> filemtime($left);
});
$scriptPath = $scriptPaths[0] ?? '';
$script = is_file($scriptPath) ? file_get_contents($scriptPath) : '';

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue($script !== '', 'Main dist bundle should exist for the overlay-search viewer runtime');
assertTrueValue(strpos($script, 'data-manuscript-search-toggle') !== false, 'Main dist bundle should target the search toggle control');
assertTrueValue(strpos($script, 'data-manuscript-search-overlay') !== false, 'Main dist bundle should target the search overlay panel');
assertTrueValue(strpos($script, 'data-manuscript-search-close') !== false, 'Main dist bundle should target the search close control');
assertTrueValue(strpos($script, 'data-manuscript-search-reset') !== false, 'Main dist bundle should target the clear-search control');
assertTrueValue(preg_match('/searchParams\.get\([\'"]busca[\'"]\)/', $script) === 1, 'Main dist bundle should update clear-search visibility after fullscreen searches');

echo 'single_manuscrito_search_overlay_bundle_test: ok' . PHP_EOL;
