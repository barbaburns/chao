<?php

$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($styles, '.manuscript-single__viewer.is-fullscreen {') !== false, 'Fullscreen manuscript styles block should exist');
assertTrueValue(strpos($styles, 'box-sizing: border-box;') !== false, 'Fullscreen manuscript viewer should use border-box sizing');
assertTrueValue(strpos($styles, 'display: flex;') !== false, 'Fullscreen manuscript viewer should become a flex container');
assertTrueValue(strpos($styles, 'flex-direction: column;') !== false, 'Fullscreen manuscript viewer should stack content vertically');
assertTrueValue(strpos($styles, 'height: 100vh;') !== false, 'Fullscreen manuscript viewer should fill the viewport height');
assertTrueValue(strpos($styles, 'height: 100dvh;') !== false, 'Fullscreen manuscript viewer should follow the dynamic mobile viewport height');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-shell {') !== false, 'Fullscreen manuscript viewer should style the viewer shell');
assertTrueValue(strpos($styles, 'flex: 1 1 auto;') !== false, 'Fullscreen manuscript viewer should let the shell stretch');
assertTrueValue(strpos($styles, 'min-height: 0;') !== false, 'Fullscreen manuscript viewer shell should allow the OSD surface to shrink correctly');
assertTrueValue(strpos($styles, 'height: 100%;') !== false, 'Fullscreen manuscript viewer should let the OpenSeadragon surface fill the shell height');
assertTrueValue(strpos($styles, '.manuscript-single__viewer.is-pseudo-fullscreen {') !== false, 'Fullscreen manuscript viewer should include a mobile visual fallback');
assertTrueValue(strpos($styles, 'position: fixed;') !== false, 'Mobile visual fullscreen fallback should cover the viewport independently of page layout');
assertTrueValue(
    strpos($styles, 'height: calc(100vh - #{rem(220px)})') === false,
    'Fullscreen manuscript viewer should not reserve a fixed 220px gap anymore'
);

echo 'single_manuscrito_fullscreen_height_test: ok' . PHP_EOL;
