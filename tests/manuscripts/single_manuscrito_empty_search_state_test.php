<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(
    preg_match('/@if\(\$hasViewerPages\).*?@elseif\(\$search\[\'has_query\'\]\).*?manuscript-single__viewer-empty/s', $template) === 1,
    'Manuscript template should render a dedicated empty viewer state when an active search has no viewer pages'
);
assertTrueValue(strpos($template, 'Nenhum resultado encontrado para') !== false, 'Manuscript template should explain when the active search has no matching pages');
assertTrueValue(
    preg_match('/manuscript-single__viewer-search-form manuscript-single__viewer-search-form--static/', $template) === 1,
    'Manuscript template should keep a visible search form inside the empty viewer state'
);
assertTrueValue(preg_match('/\.manuscript-single__viewer-empty\b/', $styles) === 1, 'Manuscript styles should define the viewer empty state block');
assertTrueValue(preg_match('/\.manuscript-single__viewer-search-form--static\b/', $styles) === 1, 'Manuscript styles should define the static empty-state search form variant');

echo 'single_manuscrito_empty_search_state_test: ok' . PHP_EOL;
