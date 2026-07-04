<?php

namespace App\Manuscripts;

$mockPostId = 77;
$mockMeta = [
    77 => [
        'chao_manuscript_import_last_snapshot' => [
            'pages' => [
                [
                    'page_number' => 1,
                    'basename' => 'venanciaCaderno_0051B',
                    'filename' => 'venanciaCaderno_0051B.jpg',
                    'image_url' => 'http://example.test/uploads/venanciaCaderno_0051B.jpg',
                    'raw_text' => 'Casa perto do rio.',
                    'layout_data' => [
                        'text' => 'Casa perto do rio.',
                        'lines' => [
                            [
                                'text' => 'Casa perto do rio.',
                                'bbox' => ['x' => 10, 'y' => 20, 'width' => 120, 'height' => 30],
                                'words' => [
                                    ['text' => 'Casa', 'bbox' => ['x' => 10, 'y' => 20, 'width' => 40, 'height' => 30]],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'chao_manuscript_page_review' => [],
        'chao_manuscript_worker_last_job' => [
            'pages' => [],
        ],
        'chao_manuscript_worker_last_response' => [
            'pages' => [],
        ],
    ],
];

function get_the_ID()
{
    global $mockPostId;

    return $mockPostId;
}

function get_post_meta($postId, $key, $single)
{
    global $mockMeta;

    return isset($mockMeta[$postId][$key]) ? $mockMeta[$postId][$key] : '';
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

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

$_GET = [
    'busca' => 'casa',
];

$data = Frontend::withSingleTemplateData([]);
$search = $data['manuscript_search'];

assertSameValue('casa', $search['query'], 'Frontend should forward the search query to the imported manuscript payload');
assertSameValue(1, $search['page_count'], 'Frontend should build search data from the imported manuscript snapshot');
assertSameValue(1, $search['match_count'], 'Frontend should search imported manuscript pages');
assertSameValue('venanciaCaderno_0051B.jpg', $search['pages'][0]['filename'], 'Frontend should expose imported page data to the viewer');
assertTrueValue($search['viewer_enabled'] === true, 'Frontend should enable the viewer when imported pages include image URLs');

echo 'frontend_test: ok' . PHP_EOL;
