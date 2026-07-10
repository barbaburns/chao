<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/scripts/routes/single-manuscrito.js');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(
    strpos($template, 'class="screen-reader-text" data-manuscript-page-label') !== false,
    'Manuscript template should keep the page label hidden from the visual toolbar'
);
assertTrueValue(
    strpos($script, 'data-manuscript-page-label') !== false,
    'Single manuscript route should keep updating the page label for accessibility'
);

echo 'single_manuscrito_page_label_test: ok' . PHP_EOL;
