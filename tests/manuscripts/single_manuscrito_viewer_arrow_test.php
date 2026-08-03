<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(substr_count($template, '<svg class="manuscript-single__viewer-edge-icon"') === 2, 'Manuscript template should render SVG arrows for both navigation directions');
assertTrueValue(strpos($template, '<path d="M15 6H2M7 1l-5 5 5 5"/>') !== false, 'Manuscript template should render the straight previous arrow');
assertTrueValue(strpos($template, '<path d="M1 6h13M9 1l5 5-5 5"/>') !== false, 'Manuscript template should render the straight next arrow');
assertTrueValue(preg_match('/\.manuscript-single__viewer-edge-icon\s*\{[^}]*height:\s*rem\(12px\);[^}]*stroke:\s*currentColor;[^}]*stroke-width:\s*1\.25;[^}]*width:\s*rem\(16px\);/s', $styles) === 1, 'Manuscript navigation arrows should use the compact straight-line SVG styling');

echo 'single_manuscrito_viewer_arrow_test: ok' . PHP_EOL;
