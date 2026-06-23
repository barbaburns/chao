<?php

namespace App\Manuscripts;

function get_field($fieldName, $postId, $formatValue)
{
    return 0;
}

function get_post_meta($postId, $key, $single)
{
    return 0;
}

function wp_get_attachment_url($attachmentId)
{
    if ($attachmentId === 321) {
        return 'http://example.test/uploads/attached.pdf';
    }

    return '';
}

function get_post_field($field, $postId)
{
    return '';
}

function get_attached_media($type, $postId)
{
    return [
        321 => (object) ['ID' => 321],
    ];
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

$resolver = new MasterPdfResolver();
$source = $resolver->resolveForPost(88);

assertSameValue(321, $source['id'], 'Resolver should use attached PDF id when field and content are empty');
assertSameValue('http://example.test/uploads/attached.pdf', $source['url'], 'Resolver should use attached PDF URL when field and content are empty');
assertSameValue('attachment', $source['source'], 'Resolver should identify attachment fallback source');

echo 'master_pdf_attachment_fallback_test: ok' . PHP_EOL;
