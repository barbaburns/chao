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

function esc_html($value)
{
    return (string) $value;
}

function esc_url($value)
{
    return (string) $value;
}

function wp_kses_post($value)
{
    return '[safe]' . (string) $value;
}

function get_permalink($postId)
{
    return $postId === 501 ? 'https://example.test/manuscritos/caderno-azul/' : '';
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

$GLOBALS['product_extra_manuscript_fields'][10]['manuscript_text'] = '';
assertSameValue(false, ProductExtraHarness::getProductManuscript(10), 'Empty manuscript text should return false');

$GLOBALS['product_extra_manuscript_fields'][11]['manuscript_text'] = '<p>Primeira <strong>pagina</strong> com <a href="https://example.test">link</a>.</p>';
$GLOBALS['product_extra_manuscript_fields'][11]['manuscrito_link'] = (object) ['ID' => 501];

$manuscript = ProductExtraHarness::getProductManuscript(11);

assertTrueValue(is_array($manuscript), 'Valid manuscript text should return an array');
assertSameValue('[safe]<p>Primeira <strong>pagina</strong> com <a href="https://example.test">link</a>.</p>', $manuscript['text'], 'The helper should expose the sanitized manuscript rich text');
assertSameValue('https://example.test/manuscritos/caderno-azul/', $manuscript['url'], 'The helper should expose the permalink of the selected manuscript post');

$GLOBALS['product_extra_manuscript_fields'][12]['manuscript_text'] = '<p>Texto sem link.</p>';
$GLOBALS['product_extra_manuscript_fields'][12]['manuscrito_link'] = (object) ['ID' => 999];

$manuscriptWithoutUrl = ProductExtraHarness::getProductManuscript(12);

assertSameValue('', $manuscriptWithoutUrl['url'], 'The helper should return an empty URL when the manuscript link is invalid');

echo 'product_extra_manuscript_test: ok' . PHP_EOL;
