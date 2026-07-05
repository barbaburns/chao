<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\Search;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$job = [
    'pages' => [
        [
            'page_number' => 1,
            'filename' => 'page-1.png',
            'image_url' => 'http://127.0.0.1:8002/jobs/job-123/pages/page-1.png',
            'raw_text' => 'Casa grande. Outra casa na margem.',
            'layout_data' => [
                'text' => 'Casa grande. Outra casa na margem.',
                'lines' => [
                    [
                        'text' => 'Casa grande.',
                        'bbox' => ['x' => 20, 'y' => 30, 'width' => 180, 'height' => 28],
                        'words' => [
                            ['text' => 'Casa', 'bbox' => ['x' => 20, 'y' => 30, 'width' => 54, 'height' => 28]],
                            ['text' => 'grande', 'bbox' => ['x' => 84, 'y' => 30, 'width' => 92, 'height' => 28]],
                        ],
                    ],
                    [
                        'text' => 'Outra casa na margem.',
                        'bbox' => ['x' => 24, 'y' => 74, 'width' => 240, 'height' => 28],
                        'words' => [
                            ['text' => 'Outra', 'bbox' => ['x' => 24, 'y' => 74, 'width' => 62, 'height' => 28]],
                            ['text' => 'casa', 'bbox' => ['x' => 96, 'y' => 74, 'width' => 52, 'height' => 28]],
                            ['text' => 'na', 'bbox' => ['x' => 156, 'y' => 74, 'width' => 22, 'height' => 28]],
                            ['text' => 'margem', 'bbox' => ['x' => 186, 'y' => 74, 'width' => 78, 'height' => 28]],
                        ],
                    ],
                ],
            ],
        ],
        [
            'page_number' => 2,
            'filename' => 'page-2.png',
            'image_url' => 'http://127.0.0.1:8002/jobs/job-123/pages/page-2.png',
            'raw_text' => 'Carta velha ao fundo.',
            'layout_data' => [
                'text' => 'Carta velha ao fundo.',
                'lines' => [
                    [
                        'text' => 'Carta velha ao fundo.',
                        'bbox' => ['x' => 18, 'y' => 26, 'width' => 210, 'height' => 28],
                        'words' => [
                            ['text' => 'Carta', 'bbox' => ['x' => 18, 'y' => 26, 'width' => 58, 'height' => 28]],
                            ['text' => 'velha', 'bbox' => ['x' => 86, 'y' => 26, 'width' => 52, 'height' => 28]],
                        ],
                    ],
                ],
            ],
        ],
        [
            'page_number' => 3,
            'filename' => 'page-3.png',
            'image_url' => 'http://127.0.0.1:8002/jobs/job-123/pages/page-3.png',
            'raw_text' => 'A casa fica longe da estrada.',
        ],
        [
            'page_number' => 4,
            'filename' => 'page-4.png',
            'image_url' => 'http://127.0.0.1:8002/jobs/job-123/pages/page-4.png',
            'raw_text' => 'Carta velha no canto.',
            'layout_data' => [
                'text' => 'Carta velha no canto.',
                'lines' => [
                    [
                        'text' => 'Carta velha no canto.',
                        'bbox' => ['x' => 14, 'y' => 20, 'width' => 220, 'height' => 28],
                        'words' => [
                            ['text' => 'Carta', 'bbox' => ['x' => 14, 'y' => 20, 'width' => 56, 'height' => 28]],
                            ['text' => 'velha', 'bbox' => ['x' => 80, 'y' => 20, 'width' => 54, 'height' => 28]],
                        ],
                    ],
                ],
            ],
        ],
        [
            'page_number' => 5,
            'filename' => 'page-5.png',
            'image_url' => 'http://127.0.0.1:8002/jobs/job-123/pages/page-5.png',
            'raw_text' => 'Na dobra ficou a marca.',
            'layout_data' => [
                'text' => 'Na dobra ficou a marca.',
                'lines' => [
                    [
                        'text' => 'Na dobra ficou a marca.',
                        'bbox' => ['x' => 16, 'y' => 38, 'width' => 260, 'height' => 34],
                        'words' => [],
                    ],
                ],
            ],
        ],
    ],
];

$result = Search::buildViewModel($job, 'casa');
$quotedResult = Search::buildViewModel($job, '"casa"');
$rawFallbackResult = Search::buildViewModel($job, 'carta');
$approxFallbackResult = Search::buildViewModel($job, 'dobra');
$noMatchResult = Search::buildViewModel($job, 'caderno');
$accentInsensitiveResult = Search::buildViewModel([
    'pages' => [
        [
            'page_number' => 1,
            'filename' => 'page-accent.png',
            'image_url' => 'http://127.0.0.1:8002/jobs/job-123/pages/page-accent.png',
            'raw_text' => 'Em Paraíso, Paraná.',
            'layout_data' => [
                'text' => 'Em Paraíso, Paraná.',
                'lines' => [
                    [
                        'text' => 'Em Paraíso, Paraná.',
                        'bbox' => ['x' => 12, 'y' => 18, 'width' => 200, 'height' => 28],
                        'words' => [
                            ['text' => 'Em', 'bbox' => ['x' => 12, 'y' => 18, 'width' => 24, 'height' => 28]],
                            ['text' => 'Paraíso,', 'bbox' => ['x' => 44, 'y' => 18, 'width' => 88, 'height' => 28]],
                            ['text' => 'Paraná.', 'bbox' => ['x' => 140, 'y' => 18, 'width' => 72, 'height' => 28]],
                        ],
                    ],
                ],
            ],
        ],
    ],
], 'paraiso');
$emptyQuery = Search::buildViewModel($job, '');

assertSameValue('casa', $result['query'], 'Search should normalize the submitted query');
assertTrueValue($result['viewer_enabled'] === true, 'Search should enable the viewer when page images are available');
assertSameValue(1, $result['initial_page_number'], 'Search should open the viewer on the first matching page');
assertSameValue(5, $result['page_count'], 'Search should keep total indexed pages');
assertSameValue(5, $result['pages_with_text_count'], 'Search should count pages with searchable text');
assertSameValue(2, $result['match_count'], 'Search should only match pages with exact-word occurrences');
assertSameValue(2, $quotedResult['match_count'], 'Search should ignore wrapping quotes in the submitted query');
assertSameValue(2, count($result['matches']), 'Search should return one result entry per matching page');
assertSameValue(1, $result['matches'][0]['page_number'], 'Search should keep the first matching page number');
assertSameValue(2, $result['matches'][0]['occurrences'], 'Search should count repeated exact-word matches on the same page');
assertSameValue('manuscript-page-1', $result['matches'][0]['anchor_id'], 'Search should build stable anchors for results');
assertSameValue(2, $result['matches'][0]['highlight_box_count'], 'Search should count visual highlights for matched words when layout data is available');
assertTrueValue(strpos($result['matches'][0]['excerpt_html'], '<mark>Casa</mark>') !== false, 'Search should highlight the matched term in the excerpt');
assertSameValue(3, $result['matches'][1]['page_number'], 'Search should keep later matching pages');
assertSameValue('http://127.0.0.1:8002/jobs/job-123/pages/page-1.png', $result['pages'][0]['image_url'], 'Search should expose page image url to the viewer');
assertSameValue('raw', $result['pages'][0]['effective_text_source'], 'Search should prioritize imported HTR text for manuscript search');
assertSameValue(2, count($result['pages'][0]['highlight_boxes']), 'Search should expose highlight boxes for exact matched words');
assertSameValue(20.0, $result['pages'][0]['highlight_boxes'][0]['x'], 'Search should keep box coordinates from layout data');
assertTrueValue(strpos($result['pages'][1]['effective_text_html'], 'Carta velha ao fundo.') !== false, 'Search should expose imported HTR text even when a review transcription exists');
assertTrueValue(strpos($result['pages'][1]['effective_text_html'], '<mark>') === false, 'Search should not highlight partial-word matches');
assertSameValue(2, $rawFallbackResult['match_count'], 'Search should search imported HTR text even when legacy review transcriptions differ');
assertSameValue(2, $rawFallbackResult['matches'][0]['page_number'], 'Search should include the imported HTR text from page 2');
assertSameValue(4, $rawFallbackResult['matches'][1]['page_number'], 'Search should still find the raw-text-only page');
assertSameValue('raw', $rawFallbackResult['pages'][3]['effective_text_source'], 'Search should mark imported HTR text as the active source in the page model');
assertSameValue(1, count($rawFallbackResult['pages'][3]['highlight_boxes']), 'Search should expose raw-layout highlights when raw text matched');
assertSameValue(1, $approxFallbackResult['match_count'], 'Search should still match imported HTR text when only line-level geometry is available');
assertSameValue(5, $approxFallbackResult['matches'][0]['page_number'], 'Search should return the page that only has approximate line geometry');
assertSameValue(1, count($approxFallbackResult['pages'][4]['highlight_boxes']), 'Search should build a fallback highlight box from line geometry');
assertTrueValue($approxFallbackResult['pages'][4]['highlight_boxes'][0]['approximate'] === true, 'Search should mark fallback line highlights as approximate');
assertSameValue('approx-line', $approxFallbackResult['pages'][4]['highlight_boxes'][0]['box_source'], 'Search should label fallback highlights with the approximate line source');
assertSameValue(0, $noMatchResult['match_count'], 'Search should only depend on the imported manuscript text in this flow');
assertSameValue(1, $accentInsensitiveResult['match_count'], 'Search should match accent-insensitive queries against imported text');
assertTrueValue(strpos($accentInsensitiveResult['matches'][0]['excerpt_html'], '<mark>Paraíso</mark>') !== false, 'Search should keep the original accented text in excerpts');
assertSameValue(1, count($accentInsensitiveResult['pages'][0]['highlight_boxes']), 'Search should keep exact word boxes for accent-insensitive matches');
assertSameValue(0, $emptyQuery['match_count'], 'Search should not return matches for an empty query');
assertSameValue(1, $emptyQuery['initial_page_number'], 'Search should fallback to the first page when there is no query');
assertSameValue(5, count($emptyQuery['pages']), 'Search should still expose indexed pages without a query');

echo 'search_test: ok' . PHP_EOL;
