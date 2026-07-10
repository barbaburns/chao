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

assertTrueValue($script !== '', 'Main dist bundle should exist for the manuscript viewer runtime');
assertTrueValue(strpos($script, 'data-manuscript-zoom-actual') === false, 'Main dist bundle should no longer target the 1:1 zoom control');
assertTrueValue(strpos($script, 'data-manuscript-zoom-out') !== false, 'Main dist bundle should target the zoom-out control');
assertTrueValue(strpos($script, 'data-manuscript-zoom-in') !== false, 'Main dist bundle should target the zoom-in control');

echo 'single_manuscrito_zoom_bundle_test: ok' . PHP_EOL;
