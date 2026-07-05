<?php

namespace App\Manuscripts {
    $mockPostId = 88;
    $mockMeta = [
        88 => [
            'chao_manuscript_import_last_snapshot' => [
                'pages' => [
                    [
                        'page_number' => 1,
                        'basename' => 'noAcfFixture',
                        'filename' => 'noAcfFixture.jpg',
                        'image_url' => 'http://example.test/uploads/noAcfFixture.jpg',
                        'raw_text' => 'Casa perto de Paraíso.',
                        'layout_data' => [
                            'text' => 'Casa perto de Paraíso.',
                            'lines' => [],
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

    $_GET = [
        'busca' => 'casa',
    ];

    $data = Frontend::withSingleTemplateData([]);

    assertSameValue([], $data['related_books'], 'Frontend should normalize related books to an empty array when ACF is unavailable');
    assertSameValue('casa', $data['manuscript_search']['query'], 'Frontend should continue to expose manuscript search data when ACF is unavailable');

    echo 'frontend_no_acf_test: ok' . PHP_EOL;
}
