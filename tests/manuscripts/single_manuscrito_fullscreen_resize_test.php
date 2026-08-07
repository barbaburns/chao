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
    preg_match('/function\s+resizeViewer\s*\(callback\)\s*\{.*?osd\.addOnceHandler\(\'after-resize\',\s*\(\)\s*=>\s*\{.*?window\.requestAnimationFrame\(callback\);.*?\}\);.*?osd\.forceResize\(\);.*?function\s+syncFullscreenState\s*\(\)\s*\{.*?viewerElement\.classList\.toggle\(\'is-fullscreen\',\s*isFullscreen\);.*?resizeViewer\(\(\)\s*=>\s*\{.*?applyFitMode\(state\.fitMode\);.*?\}\);/s', $script) === 1,
    'Single manuscript route should reapply the active fit mode after OpenSeadragon finishes restoring its resize zoom'
);

assertTrueValue(
    preg_match('/const\s+isEnteringFullscreen\s*=\s*isFullscreen\s*&&\s*!state\.isFullscreen;.*?if\s*\(isEnteringFullscreen\)\s*\{.*?state\.fitModeBeforeFullscreen\s*=\s*state\.fitMode;.*?state\.fitMode\s*=\s*\'page\';.*?else if\s*\(!isFullscreen\s*&&\s*state\.isFullscreen\)\s*\{.*?state\.fitMode\s*=\s*state\.fitModeBeforeFullscreen;/s', $script) === 1,
    'Single manuscript route should fit the whole page by default on fullscreen entry and restore the previous mode on exit'
);

assertTrueValue(
    preg_match('/function\s+scheduleMobileFullscreenFit\s*\(\)\s*\{.*?mobileViewport\.matches.*?window\.setTimeout\(\(\)\s*=>\s*\{.*?state\.fitMode\s*!==\s*\'page\'.*?resizeViewer\(\(\)\s*=>\s*\{.*?applyFitMode\(\'page\'\);.*?\},\s*250\);/s', $script) === 1,
    'Single manuscript route should refit the whole page after the mobile fullscreen viewport stabilizes'
);
assertTrueValue(strpos($script, 'scheduleMobileFullscreenFit();') !== false, 'Single manuscript route should schedule the mobile fullscreen page fit');
assertTrueValue(
    strpos($script, "window.visualViewport.addEventListener('resize', handleFullscreenViewportResize)") !== false,
    'Single manuscript route should refit after the mobile visual viewport changes'
);
assertTrueValue(
    preg_match('/const\s+isEnteringFullscreen\s*=\s*isFullscreen\s*&&\s*!state\.isFullscreen;.*?resizeViewer\(\(\)\s*=>\s*\{.*?applyFitMode\(state\.fitMode\);.*?if\s*\(isEnteringFullscreen\)\s*\{.*?zoomOut\(\);/s', $script) === 1,
    'Single manuscript route should zoom out by one step after fitting the page on fullscreen entry'
);
assertTrueValue(
    preg_match('/scheduleMobileFullscreenFit.*?applyFitMode\(\'page\'\);\s*zoomOut\(\);/s', $script) === 1,
    'Single manuscript route should preserve the one-step zoom reduction after the mobile fullscreen viewport stabilizes'
);

echo 'single_manuscrito_fullscreen_resize_test: ok' . PHP_EOL;
