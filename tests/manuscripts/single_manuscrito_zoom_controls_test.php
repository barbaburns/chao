<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/scripts/routes/single-manuscrito.js');
$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($template, 'data-manuscript-zoom-actual') === false, 'Manuscript template should remove the 1:1 zoom control');
assertTrueValue(strpos($template, '>1:1<') === false, 'Manuscript template should no longer render the 1:1 button label');
assertTrueValue(strpos($template, 'data-manuscript-zoom-out') !== false, 'Manuscript template should render a zoom-out control');
assertTrueValue(strpos($template, 'data-manuscript-zoom-in') !== false, 'Manuscript template should render a zoom-in control');
assertTrueValue(strpos($template, 'data-manuscript-tooltip="Diminuir zoom"') !== false, 'Manuscript template should label the zoom-out control');
assertTrueValue(strpos($template, 'data-manuscript-tooltip="Aumentar zoom"') !== false, 'Manuscript template should label the zoom-in control');

assertTrueValue(strpos($script, 'data-manuscript-zoom-actual') === false, 'Single manuscript route should stop targeting the 1:1 zoom control');
assertTrueValue(strpos($script, 'data-manuscript-zoom-out') !== false, 'Single manuscript route should target the zoom-out control');
assertTrueValue(strpos($script, 'data-manuscript-zoom-in') !== false, 'Single manuscript route should target the zoom-in control');
assertTrueValue(strpos($script, "zoomOutButton.addEventListener('click'") !== false, 'Single manuscript route should handle zoom-out clicks');
assertTrueValue(strpos($script, "zoomInButton.addEventListener('click'") !== false, 'Single manuscript route should handle zoom-in clicks');
assertTrueValue(strpos($script, 'osd.viewport.zoomBy(1 / 1.2)') !== false, 'Single manuscript route should zoom out with the expected ratio');
assertTrueValue(strpos($script, 'osd.viewport.zoomBy(1.2)') !== false, 'Single manuscript route should zoom in with the expected ratio');
assertTrueValue(strpos($script, 'minZoomLevel: 0.05') !== false, 'Single manuscript viewer should allow zooming out beyond the whole-page fit');
assertTrueValue(
    preg_match('/@include media-breakpoint-down\(md\).*?\[data-manuscript-zoom-out\],\s*\[data-manuscript-zoom-in\],.*?\{\s*display:\s*none;/s', $styles) === 1,
    'Single manuscript viewer should hide zoom buttons on mobile'
);

echo 'single_manuscrito_zoom_controls_test: ok' . PHP_EOL;
