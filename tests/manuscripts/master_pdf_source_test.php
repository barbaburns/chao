<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\MasterPdfResolver;

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

$source = $resolver->extractSourceFromBlocks([
    [
        'blockName' => 'core/paragraph',
        'attrs' => [],
    ],
    [
        'blockName' => 'core/file',
        'attrs' => [
            'id' => 88,
            'href' => 'http://example.test/uploads/barbosa.pdf',
        ],
    ],
]);

assertSameValue(88, $source['id'], 'Resolver should use attachment id from first file block');
assertSameValue('http://example.test/uploads/barbosa.pdf', $source['url'], 'Resolver should use file block URL');
assertSameValue('block', $source['source'], 'Resolver should identify block origin');

$contentSource = $resolver->extractSourceFromContent(
    '<!-- wp:file --><div class="wp-block-file"><a href="http://example.test/uploads/fallback.pdf">fallback.pdf</a></div><!-- /wp:file -->'
);

assertSameValue(0, $contentSource['id'], 'Fallback resolver should keep empty id when only URL is available');
assertSameValue('http://example.test/uploads/fallback.pdf', $contentSource['url'], 'Fallback resolver should detect PDF URL from saved content');
assertSameValue('content', $contentSource['source'], 'Fallback resolver should identify content origin');

echo 'master_pdf_source_test: ok' . PHP_EOL;
