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

assertTrueValue(strpos($template, '>↔</button>') === false, 'Fit-width control should not use an emoji-prone Unicode arrow');
assertTrueValue(strpos($template, 'class="manuscript-single__viewer-fit-width-icon"') !== false, 'Fit-width control should render a stable SVG icon');
assertTrueValue(strpos($template, 'stroke="currentColor"') !== false, 'Fit-width SVG icon should inherit the control color');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-fit-width-icon') !== false, 'Fit-width SVG icon should have an explicit display width');

echo 'single_manuscrito_fit_width_icon_test: ok' . PHP_EOL;
