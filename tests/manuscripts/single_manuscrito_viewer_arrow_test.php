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

assertTrueValue(strpos($template, 'aria-hidden="true">←</span>') !== false, 'Manuscript template should render the unicode previous arrow glyph');
assertTrueValue(strpos($template, 'aria-hidden="true">→</span>') !== false, 'Manuscript template should render the unicode next arrow glyph');
assertTrueValue(strpos($template, '<svg class="manuscript-single__viewer-edge-icon"') === false, 'Manuscript template should remove the custom viewer arrow svg');
assertTrueValue(preg_match('/\.manuscript-single__viewer-edge-icon\s*\{[^}]*font-family:\s*Arial,\s*sans-serif;[^}]*font-size:\s*rem\(34px\);[^}]*font-weight:\s*400;[^}]*line-height:\s*1;/s', $styles) === 1, 'Manuscript styles should restore the lightweight text arrow styling');

echo 'single_manuscrito_viewer_arrow_test: ok' . PHP_EOL;
