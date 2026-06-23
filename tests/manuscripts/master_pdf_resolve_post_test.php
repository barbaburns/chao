<?php

namespace App\Manuscripts;

$mockPostContent = '<!-- wp:file --><div class="wp-block-file"><a href="http://example.test/uploads/from-post.pdf">from-post.pdf</a></div><!-- /wp:file -->';

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
    return '';
}

function get_post_field($field, $postId)
{
    global $mockPostContent;

    return $mockPostContent;
}

function parse_blocks($content)
{
    return [];
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
$source = $resolver->resolveForPost(77);

assertSameValue('http://example.test/uploads/from-post.pdf', $source['url'], 'Resolver should read PDF URL from post content when field is empty');
assertSameValue('content', $source['source'], 'Resolver should report content fallback source for post content');

echo 'master_pdf_resolve_post_test: ok' . PHP_EOL;
