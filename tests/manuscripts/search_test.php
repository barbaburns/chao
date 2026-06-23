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
    ],
];

$review = [
    1 => [
        'status' => 'revisado',
        'transcription' => 'Casa grande. Outra casa na margem.',
        'notes' => '',
    ],
    2 => [
        'status' => 'revisado',
        'transcription' => 'Casario antigo ao fundo.',
        'notes' => '',
    ],
    3 => [
        'status' => 'em_revisao',
        'transcription' => 'A CASA fica longe da estrada.',
        'notes' => '',
    ],
];
$reviewApproximate = $review;
$reviewApproximate[1]['transcription'] = 'Casa grande. Outra casa no caderno da margem.';

$result = Search::buildViewModel($job, $review, 'casa');
$rawFallbackResult = Search::buildViewModel($job, $review, 'carta');
$approximateFallbackResult = Search::buildViewModel($job, $reviewApproximate, 'caderno');
$emptyQuery = Search::buildViewModel($job, $review, '');
$hydratedJob = Search::mergeJobWithWorkerResponse(
    [
        'pages' => [
            [
                'page_number' => 1,
                'filename' => 'page-1.png',
                'image_url' => 'http://127.0.0.1:8002/jobs/job-123/pages/page-1.png',
            ],
        ],
    ],
    [
        'pages' => [
            [
                'page_number' => 1,
                'raw_text' => 'Casa grande.',
                'layout_data' => [
                    'text' => 'Casa grande.',
                    'lines' => [
                        [
                            'text' => 'Casa grande.',
                            'words' => [
                                ['text' => 'Casa', 'bbox' => ['x' => 20, 'y' => 30, 'width' => 54, 'height' => 28]],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]
);

assertSameValue('casa', $result['query'], 'Search should normalize the submitted query');
assertTrueValue($result['viewer_enabled'] === true, 'Search should enable the viewer when page images are available');
assertSameValue(1, $result['initial_page_number'], 'Search should open the viewer on the first matching page');
assertSameValue(4, $result['page_count'], 'Search should keep total indexed pages');
assertSameValue(4, $result['pages_with_text_count'], 'Search should count pages with searchable text');
assertSameValue(2, $result['match_count'], 'Search should only match pages with exact-word occurrences');
assertSameValue(2, count($result['matches']), 'Search should return one result entry per matching page');
assertSameValue(1, $result['matches'][0]['page_number'], 'Search should keep the first matching page number');
assertSameValue(2, $result['matches'][0]['occurrences'], 'Search should count repeated exact-word matches on the same page');
assertSameValue('manuscript-page-1', $result['matches'][0]['anchor_id'], 'Search should build stable anchors for results');
assertSameValue(2, $result['matches'][0]['highlight_box_count'], 'Search should count visual highlights for matched words when layout data is available');
assertTrueValue(strpos($result['matches'][0]['excerpt_html'], '<mark>Casa</mark>') !== false, 'Search should highlight the matched term in the excerpt');
assertSameValue(3, $result['matches'][1]['page_number'], 'Search should keep later matching pages');
assertSameValue('http://127.0.0.1:8002/jobs/job-123/pages/page-1.png', $result['pages'][0]['image_url'], 'Search should expose page image url to the viewer');
assertSameValue('reviewed', $result['pages'][0]['effective_text_source'], 'Search should prefer reviewed text when available');
assertSameValue(2, count($result['pages'][0]['highlight_boxes']), 'Search should expose highlight boxes for exact matched words');
assertSameValue(20.0, $result['pages'][0]['highlight_boxes'][0]['x'], 'Search should keep box coordinates from layout data');
assertTrueValue(strpos($result['pages'][1]['effective_text_html'], 'Casario') !== false, 'Search should still expose non-matching page text');
assertTrueValue(strpos($result['pages'][1]['effective_text_html'], '<mark>') === false, 'Search should not highlight partial-word matches');
assertSameValue(1, $rawFallbackResult['match_count'], 'Search should fallback to raw text when a page has not been manually reviewed');
assertSameValue(4, $rawFallbackResult['matches'][0]['page_number'], 'Search should find the raw-text-only page');
assertSameValue('raw', $rawFallbackResult['pages'][3]['effective_text_source'], 'Search should mark raw text fallback in the page model');
assertSameValue(1, count($rawFallbackResult['pages'][3]['highlight_boxes']), 'Search should expose raw-layout highlights when raw text matched');
assertSameValue(1, $approximateFallbackResult['match_count'], 'Search should still match reviewed-only words');
assertSameValue(1, $approximateFallbackResult['matches'][0]['page_number'], 'Search should keep the reviewed page for approximate fallback');
assertSameValue(1, count($approximateFallbackResult['pages'][0]['highlight_boxes']), 'Search should create one approximate box when no exact word box exists');
assertSameValue(true, $approximateFallbackResult['pages'][0]['highlight_boxes'][0]['approximate'], 'Search should flag approximate fallback boxes');
assertSameValue('approx-line', $approximateFallbackResult['pages'][0]['highlight_boxes'][0]['box_source'], 'Search should mark the fallback box source');
assertSameValue(74.0, $approximateFallbackResult['pages'][0]['highlight_boxes'][0]['y'], 'Search should map the approximate highlight to the closest layout line');
assertSameValue('Casa grande.', $hydratedJob['pages'][0]['raw_text'], 'Search should hydrate missing raw text from the worker response');
assertSameValue('Casa grande.', $hydratedJob['pages'][0]['layout_data']['text'], 'Search should hydrate missing layout data from the worker response');
assertSameValue(0, $emptyQuery['match_count'], 'Search should not return matches for an empty query');
assertSameValue(1, $emptyQuery['initial_page_number'], 'Search should fallback to the first page when there is no query');
assertSameValue(4, count($emptyQuery['pages']), 'Search should still expose indexed pages without a query');

echo 'search_test: ok' . PHP_EOL;
