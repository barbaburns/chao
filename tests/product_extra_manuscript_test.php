<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$GLOBALS['product_extra_manuscript_fields'] = [];

function get_field($field, $id)
{
    return $GLOBALS['product_extra_manuscript_fields'][$id][$field] ?? null;
}

function wp_get_attachment_image($attachmentId, $size)
{
    return sprintf('<img data-id="%d" data-size="%s" />', $attachmentId, $size);
}

function get_permalink($postId)
{
    return $postId === 501 ? 'https://example.test/manuscritos/caderno-azul/' : '';
}

function esc_html($value)
{
    return (string) $value;
}

function esc_url($value)
{
    return (string) $value;
}

require_once dirname(__DIR__) . '/app/Controllers/Partials/ProductExtra.php';

use App\Controllers\Partials\ProductExtra;

class ProductExtraHarness
{
    use ProductExtra;
}

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

$GLOBALS['product_extra_manuscript_fields'][10]['manuscript_gallery'] = [];
assertSameValue(false, ProductExtraHarness::getProductManuscript(10), 'Empty manuscript galleries should return false');

$GLOBALS['product_extra_manuscript_fields'][11]['manuscript_gallery'] = [
    [
        'ID' => 101,
        'caption' => 'Primeira pagina',
    ],
    [
        'ID' => 202,
        'caption' => '',
    ],
    [
        'caption' => 'Sem ID',
    ],
];
$GLOBALS['product_extra_manuscript_fields'][11]['manuscrito'] = 501;

$manuscript = ProductExtraHarness::getProductManuscript(11);

assertTrueValue(is_array($manuscript), 'A valid manuscript gallery should return an array');
assertSameValue(2, count($manuscript['images']), 'The helper should ignore gallery items without attachment IDs');
assertSameValue('<img data-id="101" data-size="large" />', $manuscript['images'][0]['image'], 'The helper should build the image HTML from each gallery item');
assertSameValue('Primeira pagina', $manuscript['images'][0]['caption'], 'The helper should expose the attachment caption when available');
assertSameValue('', $manuscript['images'][1]['caption'], 'The helper should keep empty captions as empty strings');
assertSameValue('https://example.test/manuscritos/caderno-azul/', $manuscript['url'], 'The helper should expose the permalink of the selected manuscript post');

echo 'product_extra_manuscript_test: ok' . PHP_EOL;
