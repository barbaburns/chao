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
    strpos($script, "window.matchMedia('(max-width: 991.98px)')") !== false,
    'Single manuscript swipe navigation should only run on the mobile viewer breakpoint'
);
assertTrueValue(strpos($script, "osdElement.addEventListener('touchstart', startSwipe, true)") !== false, 'Single manuscript viewer should track swipe starts');
assertTrueValue(strpos($script, "osdElement.addEventListener('touchend', endSwipe, true)") !== false, 'Single manuscript viewer should track swipe ends');
assertTrueValue(
    preg_match('/function\s+endSwipe.*?Math\.abs\(deltaX\).*?Math\.abs\(deltaY\).*?goToPage\(state\.activeIndex \+ \(deltaX < 0 \? 1 : -1\)\);/s', $script) === 1,
    'Single manuscript viewer should navigate according to a predominantly horizontal swipe direction'
);
assertTrueValue(strpos($script, 'if (suppressViewerClick)') !== false, 'Single manuscript viewer should suppress the synthetic click after a swipe');

echo 'single_manuscrito_swipe_navigation_test: ok' . PHP_EOL;
