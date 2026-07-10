<?php

$script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/scripts/routes/single-manuscrito.js');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(
    preg_match('/osdElement\.addEventListener\(\'click\',\s*\(\)\s*=>\s*\{.*?!isViewerFullscreen\(\).*?toggleFullscreen\(\);.*?\}\);/s', $script) === 1,
    'Single manuscript route should open fullscreen when the viewer image area is clicked'
);

assertTrueValue(strpos($script, 'document.webkitFullscreenElement') !== false, 'Single manuscript route should detect Safari fullscreen state');
assertTrueValue(strpos($script, 'viewerElement.webkitRequestFullscreen') !== false, 'Single manuscript route should request Safari fullscreen when available');
assertTrueValue(strpos($script, 'setPseudoFullscreen(true)') !== false, 'Single manuscript route should provide a visual fullscreen fallback on unsupported mobile browsers');

echo 'single_manuscrito_click_fullscreen_test: ok' . PHP_EOL;
