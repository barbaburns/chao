<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\Admin;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$snapshot = Admin::buildImportSnapshot([
    'zip_name' => 'venancia.zip',
    'page_count' => 2,
    'pages' => [
        [
            'page_number' => 1,
            'basename' => 'venanciaCaderno_0051B',
            'filename' => 'venanciaCaderno_0051B.jpg',
            'image_url' => 'http://example.test/uploads/venanciaCaderno_0051B.jpg',
            'raw_text' => 'Os dias que passei com',
            'layout_data' => [
                'text' => 'Os dias que passei com',
                'lines' => [],
            ],
            'highlight_exact' => false,
            'import_status' => 'imported',
            'import_errors' => [],
        ],
    ],
    'errors' => [],
]);

$rows = Admin::buildImportedPageRows($snapshot, []);

assertSameValue('venancia.zip', $snapshot['zip_name'], 'Snapshot should keep ZIP filename');
assertSameValue(2, $snapshot['page_count'], 'Snapshot should keep page count');
assertSameValue('venanciaCaderno_0051B', $snapshot['pages'][0]['basename'], 'Snapshot should keep basename');
assertSameValue('imported', $rows[0]['import_status'], 'Rows should keep import status');
assertSameValue('aproximado', $rows[0]['highlight_status'], 'Rows should mark pages without exact word geometry as approximate');

echo 'admin_zip_import_test: ok' . PHP_EOL;
