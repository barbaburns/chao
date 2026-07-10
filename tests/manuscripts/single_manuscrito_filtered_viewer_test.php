<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($template, "'viewer_pages' => []") !== false, 'Manuscript template should define a default filtered viewer-pages payload');
assertTrueValue(strpos($template, "array_filter(\$search['viewer_pages']") !== false, 'Manuscript template should serialize only the filtered viewer pages into the viewer payload');
assertTrueValue(
    preg_match('/@if\(!\$search\[\'has_query\'\]\).*?data-manuscript-page-jump-form/s', $template) === 1,
    'Manuscript template should only render the compact page-jump form when no search is active'
);

echo 'single_manuscrito_filtered_viewer_test: ok' . PHP_EOL;
