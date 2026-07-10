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
    preg_match('/osd\.addOnceHandler\(\'open\',\s*\(\)\s*=>\s*\{.*?fitToWidth\(\);.*?state\.overlayIds\s*=\s*renderHighlightOverlays/s', $script) === 1,
    'Single manuscript route should fit the viewer to the page width by default when a manuscript page opens'
);

echo 'single_manuscrito_default_fit_width_test: ok' . PHP_EOL;
