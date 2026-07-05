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
                    'raw_text' => 'Casa perto de Paraíso.',
                    'layout_data' => [
                        'text' => 'Casa perto de Paraíso.',
                        'lines' => [
                            [
                                'text' => 'Casa perto de Paraíso.',
                                'bbox' => ['x' => 10, 'y' => 20, 'width' => 120, 'height' => 30],
                                'words' => [
                                    ['text' => 'Casa', 'bbox' => ['x' => 10, 'y' => 20, 'width' => 40, 'height' => 30]],
                                    ['text' => 'Paraíso.', 'bbox' => ['x' => 70, 'y' => 20, 'width' => 60, 'height' => 30]],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
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

$_GET = [
    'busca' => 'paraiso',
];

$accentData = Frontend::withSingleTemplateData([]);
$accentSearch = $accentData['manuscript_search'];

assertSameValue(1, $accentSearch['match_count'], 'Frontend should keep accent-insensitive searching available for imported pages');

echo 'frontend_test: ok' . PHP_EOL;
