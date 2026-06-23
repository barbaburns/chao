<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/scripts/routes/single-manuscrito.js');
$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');
$package = file_get_contents(dirname(__DIR__, 2) . '/package.json');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($package, '"openseadragon"') !== false, 'Theme package should declare openseadragon as a dependency');
assertTrueValue(strpos($template, 'data-manuscript-osd') !== false, 'Manuscript template should expose a dedicated OpenSeadragon container');
assertTrueValue(strpos($template, 'data-manuscript-pages') !== false, 'Manuscript template should keep serialized page data for the viewer');
assertTrueValue(strpos($template, 'data-manuscript-prev') !== false, 'Manuscript template should keep previous-page navigation');
assertTrueValue(strpos($template, 'data-manuscript-next') !== false, 'Manuscript template should keep next-page navigation');
assertTrueValue(strpos($template, 'data-manuscript-scale-mode') === false, 'Manuscript template should remove legacy scale-mode controls');
assertTrueValue(strpos($template, 'data-manuscript-highlight-label') === false, 'Manuscript template should remove the legacy highlight label');
assertTrueValue(strpos($script, "from 'openseadragon'") !== false || strpos($script, 'from "openseadragon"') !== false, 'Single manuscript route should import OpenSeadragon');
assertTrueValue(strpos($script, 'OpenSeadragon({') !== false, 'Single manuscript route should initialize OpenSeadragon');
assertTrueValue(strpos($script, 'data-manuscript-osd') !== false, 'Single manuscript route should target the OpenSeadragon container');
assertTrueValue(strpos($script, 'data-manuscript-scale-mode') === false, 'Single manuscript route should remove scale-mode logic');
assertTrueValue(strpos($script, 'manuscript-single__viewer-highlight') === false, 'Single manuscript route should remove highlight overlay rendering');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-osd') !== false, 'Manuscript styles should define an OpenSeadragon viewer container');
assertTrueValue(strpos($styles, '.manuscript-single__viewer-highlight') === false, 'Manuscript styles should remove highlight overlay styles');

echo 'single_manuscrito_viewer_markup_test: ok' . PHP_EOL;
