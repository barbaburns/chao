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
assertTrueValue(strpos($template, 'manuscript-single__viewer-fit-icon--width') !== false, 'Fit toggle should render the supplied width icon');
assertTrueValue(strpos($template, 'manuscript-single__viewer-fit-icon--page') !== false, 'Fit toggle should render the supplied whole-page icon');
assertTrueValue(strpos($template, 'stroke="currentColor"') !== false, 'Fit-width SVG icon should inherit the control color');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-fit-icon') !== false, 'Fit SVG icons should have explicit dimensions');

echo 'single_manuscrito_fit_width_icon_test: ok' . PHP_EOL;
