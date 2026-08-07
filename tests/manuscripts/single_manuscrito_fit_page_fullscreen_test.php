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
    strpos($script, "const initialFitMode = 'page';") !== false,
    'Single manuscript viewer should initially fit the whole page to the available height'
);
assertTrueValue(
    preg_match('/osd\.viewport\.goHome\(true\);.*?!state\.isFullscreen.*?osd\.world\.getHomeBounds\(\).*?osd\.viewport\.panTo\(new OpenSeadragon\.Point/s', $script) === 1,
    'Single manuscript viewer should align a height-fitted page to the left outside fullscreen'
);

assertTrueValue(
    preg_match('/function\s+applyFitMode\s*\(mode\)\s*\{.*?if\s*\(mode\s*===\s*\'page\'\)\s*\{.*?osd\.viewport\.goHome\(true\);.*?return;.*?osd\.viewport\.fitHorizontally\(true\);/s', $script) === 1,
    'Single manuscript route should use distinct native viewport operations for fitting the whole page and fitting the width'
);

assertTrueValue(
    strpos($script, 'osd.viewport.resize();') === false,
    'Single manuscript route should not call viewport.resize without the required container size'
);

echo 'single_manuscrito_fit_page_fullscreen_test: ok' . PHP_EOL;
