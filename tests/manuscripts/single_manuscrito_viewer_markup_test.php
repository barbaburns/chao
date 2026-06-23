<?php

$template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/content-single-manuscrito.blade.php');
$script = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/scripts/routes/single-manuscrito.js');
$styles = file_get_contents(dirname(__DIR__, 2) . '/resources/assets/styles/layouts/_manuscripts.scss');
$package = file_get_contents(dirname(__DIR__, 2) . '/package.json');
$lockfile = file_get_contents(dirname(__DIR__, 2) . '/yarn.lock');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function extractMatch($pattern, $subject, $message)
{
    if (preg_match($pattern, $subject, $matches) !== 1) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    return $matches;
}

$viewerSectionMatches = extractMatch(
    '/<section\s+[^>]*class="manuscript-single__viewer"[^>]*>(?P<body>.*?)<\/section>/s',
    $template,
    'Manuscript template should define the viewer section'
);
$viewerSection = $viewerSectionMatches['body'];

$resultsSectionMatches = extractMatch(
    '/<section\b[^>]*class="[^"]*\bmanuscript-single__results\b[^"]*"[^>]*>(?P<body>.*?)<\/section>/s',
    $template,
    'Manuscript template should define the search results section'
);
$resultsSection = $resultsSectionMatches['body'];

$scriptInitMatches = extractMatch(
    '/\binit\(\)\s*\{(?P<body>.*?)\n\s*\}/s',
    $script,
    'Single manuscript route should define an init() block'
);
$scriptInitBody = $scriptInitMatches['body'];

assertTrueValue(preg_match('/"openseadragon"\s*:\s*"\^5\.0\.1"/', $package) === 1, 'Theme package should declare openseadragon as a dependency');
assertTrueValue(preg_match('/^openseadragon@\^5\.0\.1:\s*$/m', $lockfile) === 1, 'Theme lockfile should pin the openseadragon dependency');

assertTrueValue(strpos($viewerSection, 'data-manuscript-osd') !== false, 'Manuscript template should expose a dedicated OpenSeadragon container');
assertTrueValue(strpos($viewerSection, 'data-manuscript-pages') !== false, 'Manuscript template should keep serialized page data for the viewer');
assertTrueValue(strpos($viewerSection, 'data-manuscript-prev') !== false, 'Manuscript template should keep previous-page navigation');
assertTrueValue(strpos($viewerSection, 'data-manuscript-next') !== false, 'Manuscript template should keep next-page navigation');
assertTrueValue(strpos($viewerSection, 'data-initial-page-number') !== false, 'Manuscript template should preserve the initial-page seed for the viewer');
assertTrueValue(strpos($viewerSection, 'data-manuscript-scale-mode') === false, 'Manuscript template should remove legacy scale-mode controls');
assertTrueValue(strpos($viewerSection, 'data-manuscript-highlight-label') === false, 'Manuscript template should remove the legacy highlight label');

assertTrueValue(strpos($resultsSection, 'data-manuscript-go-page') !== false, 'Manuscript results should preserve page-jump links for the viewer');

assertTrueValue(preg_match('/import\s+OpenSeadragon\s+from\s+[\'"]openseadragon[\'"]\s*;?/', $script) === 1, 'Single manuscript route should import OpenSeadragon');
assertTrueValue(strpos($scriptInitBody, 'data-manuscript-viewer') !== false, 'Single manuscript route init() should still target the manuscript viewer');
assertTrueValue(preg_match('/\bOpenSeadragon\s*\(/', $scriptInitBody) === 1, 'Single manuscript route should initialize OpenSeadragon');
assertTrueValue(strpos($scriptInitBody, 'data-manuscript-osd') !== false, 'Single manuscript route should target the OpenSeadragon container');
assertTrueValue(strpos($scriptInitBody, 'initialPageNumber') !== false, 'Single manuscript route should preserve the initial page number handoff');
assertTrueValue(strpos($scriptInitBody, 'data-manuscript-go-page') !== false, 'Single manuscript route should preserve search-result page jumps');
assertTrueValue(strpos($scriptInitBody, 'data-manuscript-scale-mode') === false, 'Single manuscript route should remove scale-mode logic');
assertTrueValue(strpos($scriptInitBody, 'manuscript-single__viewer-highlight') === false, 'Single manuscript route should remove highlight overlay rendering');

assertTrueValue(preg_match('/\.manuscript-single__viewer-osd\b/', $styles) === 1, 'Manuscript styles should define an OpenSeadragon viewer container');
assertTrueValue(preg_match('/\.manuscript-single__viewer-highlight\b/', $styles) === 0, 'Manuscript styles should remove highlight overlay styles');

echo 'single_manuscrito_viewer_markup_test: ok' . PHP_EOL;
