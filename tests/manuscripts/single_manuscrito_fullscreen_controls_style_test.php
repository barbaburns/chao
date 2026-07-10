<?php

$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(
    preg_match('/\.manuscript-single__viewer\.is-fullscreen\s*\{.*?\.btn,\s*\.manuscript-single__viewer-jump input\s*\{.*?background:\s*transparent;.*?color:\s*#fff;/s', $styles) === 1,
    'Fullscreen manuscript controls should use a transparent background with light content'
);

echo 'single_manuscrito_fullscreen_controls_style_test: ok' . PHP_EOL;
