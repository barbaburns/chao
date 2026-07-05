<?php

namespace App\Manuscripts {
    $sourceFolderImportVirtualDirectory = [
        'active' => false,
        'path' => '',
        'entries' => [],
        'directories' => [],
        'files' => [],
    ];

    function source_folder_import_virtual_directory_set(array $config)
    {
        $GLOBALS['sourceFolderImportVirtualDirectory'] = $config + [
            'active' => false,
            'path' => '',
            'entries' => [],
            'directories' => [],
            'files' => [],
        ];
    }

    function source_folder_import_virtual_directory_clear()
    {
        source_folder_import_virtual_directory_set([
            'active' => false,
            'path' => '',
            'entries' => [],
            'directories' => [],
            'files' => [],
        ]);
    }

    function scandir($directory, $sortingOrder = SCANDIR_SORT_ASCENDING)
    {
        $virtual = isset($GLOBALS['sourceFolderImportVirtualDirectory']) ? $GLOBALS['sourceFolderImportVirtualDirectory'] : null;

        if (is_array($virtual) && !empty($virtual['active']) && $directory === $virtual['path']) {
            return $virtual['entries'];
        }

        return \scandir($directory, $sortingOrder);
    }

    function is_dir($path)
    {
        $virtual = isset($GLOBALS['sourceFolderImportVirtualDirectory']) ? $GLOBALS['sourceFolderImportVirtualDirectory'] : null;

        if (is_array($virtual) && !empty($virtual['active']) && array_key_exists($path, $virtual['directories'])) {
            return $virtual['directories'][$path];
        }

        return \is_dir($path);
    }

    function file_get_contents($filename)
    {
        $virtual = isset($GLOBALS['sourceFolderImportVirtualDirectory']) ? $GLOBALS['sourceFolderImportVirtualDirectory'] : null;

        if (is_array($virtual) && !empty($virtual['active']) && array_key_exists($filename, $virtual['files'])) {
            return $virtual['files'][$filename];
        }

        return \file_get_contents($filename);
    }
}

namespace {

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\SourceFolderImport;
use function App\Manuscripts\source_folder_import_virtual_directory_clear;
use function App\Manuscripts\source_folder_import_virtual_directory_set;

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

$sourceDir = sys_get_temp_dir() . '/manuscript-source-folder-import-' . uniqid('', true);
mkdir($sourceDir, 0777, true);
mkdir($sourceDir . '/nested', 0777, true);

$altoPageTemplate = function ($word, $x, $y, $width, $height) {
    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="100" HEIGHT="200">'
        . '<PrintSpace><TextBlock><TextLine HPOS="' . $x . '" VPOS="' . $y . '" WIDTH="' . $width . '" HEIGHT="' . $height . '">'
        . '<String CONTENT="' . $word . '" HPOS="' . $x . '" VPOS="' . $y . '" WIDTH="' . $width . '" HEIGHT="' . $height . '"/>'
        . '</TextLine></TextBlock></PrintSpace></Page></Layout></alto>';
};

file_put_contents($sourceDir . '/folio10.jpg', 'image-ten');
file_put_contents($sourceDir . '/folio10.xml', $altoPageTemplate('Dez', 10, 20, 30, 40));
file_put_contents($sourceDir . '/folio2.png', 'image-two');
file_put_contents($sourceDir . '/folio2.xml', $altoPageTemplate('Dois', 1, 2, 3, 4));
file_put_contents($sourceDir . '/folio20.webp', 'orphan-image');
file_put_contents($sourceDir . '/folio30.xml', $altoPageTemplate('Orfa', 5, 6, 7, 8));
file_put_contents($sourceDir . '/folio40.jpg', 'image-forty');
file_put_contents($sourceDir . '/folio40.png', 'image-forty-duplicate');
file_put_contents($sourceDir . '/folio40.xml', $altoPageTemplate('Quarenta', 11, 12, 13, 14));
file_put_contents($sourceDir . '/notes.txt', 'unsupported');
file_put_contents($sourceDir . '/nested/inside.xml', $altoPageTemplate('Nested', 1, 1, 1, 1));

$result = SourceFolderImport::fromDirectory($sourceDir, 'http://example.test/manuscripts/venancia');

assertSameValue(basename($sourceDir), $result['source_name'], 'Source-folder import should keep the source directory name');
assertSameValue(3, $result['page_count'], 'Source-folder import should count only valid image/XML pairs');
assertSameValue('folio2', $result['pages'][0]['basename'], 'Source-folder import should sort basenames naturally');
assertSameValue('folio10', $result['pages'][1]['basename'], 'Source-folder import should keep later naturally sorted basenames after earlier ones');
assertSameValue(1, $result['pages'][0]['page_number'], 'Source-folder import should assign page numbers from the sorted valid pairs');
assertSameValue(3, $result['pages'][2]['page_number'], 'Source-folder import should keep sequential page numbering');
assertSameValue('folio2.png', $result['pages'][0]['filename'], 'Source-folder import should keep the original image filename');
assertSameValue($sourceDir . '/folio2.png', $result['pages'][0]['image_path'], 'Source-folder import should point image_path to the original source image');
assertSameValue('http://example.test/manuscripts/venancia/folio2.png', $result['pages'][0]['image_url'], 'Source-folder import should build image URLs directly from the source folder URL');
assertSameValue('Dois', $result['pages'][0]['raw_text'], 'Source-folder import should parse searchable text from ALTO XML');
assertSameValue('Dois', $result['pages'][0]['layout_data']['text'], 'Source-folder import should keep parsed layout text');
assertSameValue(100, $result['pages'][0]['layout_data']['page']['width'], 'Source-folder import should keep parsed page metadata');
assertSameValue(true, $result['pages'][0]['highlight_exact'], 'Source-folder import should detect exact word geometry');
assertSameValue('imported', $result['pages'][0]['import_status'], 'Source-folder import should mark valid pages as imported');
assertSameValue([], $result['pages'][0]['import_errors'], 'Source-folder import should keep valid pages free of per-page errors');
assertTrueValue(in_array('Duplicate image for folio40', $result['errors'], true), 'Source-folder import should warn about duplicate image candidates for the same basename');
assertTrueValue(in_array('Imagem sem XML correspondente: folio20', $result['errors'], true), 'Source-folder import should warn about orphan images');
assertTrueValue(in_array('XML sem imagem correspondente: folio30', $result['errors'], true), 'Source-folder import should warn about orphan XML files');
assertTrueValue(in_array('Unsupported file type: notes.txt', $result['errors'], true), 'Source-folder import should warn about unsupported files');
assertTrueValue(in_array('Subdirectories are not supported: nested', $result['errors'], true), 'Source-folder import should reject nested directories');

unlink($sourceDir . '/folio10.jpg');
unlink($sourceDir . '/folio10.xml');
unlink($sourceDir . '/folio2.png');
unlink($sourceDir . '/folio2.xml');
unlink($sourceDir . '/folio20.webp');
unlink($sourceDir . '/folio30.xml');
unlink($sourceDir . '/folio40.jpg');
unlink($sourceDir . '/folio40.png');
unlink($sourceDir . '/folio40.xml');
unlink($sourceDir . '/notes.txt');
unlink($sourceDir . '/nested/inside.xml');
rmdir($sourceDir . '/nested');
rmdir($sourceDir);

$virtualSourceDir = '/virtual/manuscript-source';
source_folder_import_virtual_directory_set([
    'active' => true,
    'path' => $virtualSourceDir,
    'entries' => ['.', '..', 'folio50.jpg', 'folio50.xml', 'folio50.XML'],
    'directories' => [
        $virtualSourceDir . '/folio50.jpg' => false,
        $virtualSourceDir . '/folio50.xml' => false,
        $virtualSourceDir . '/folio50.XML' => false,
    ],
    'files' => [
        $virtualSourceDir . '/folio50.xml' => $altoPageTemplate('Cinquenta', 21, 22, 23, 24),
        $virtualSourceDir . '/folio50.XML' => $altoPageTemplate('Cinquenta duplicado', 21, 22, 23, 24),
    ],
]);

$virtualResult = SourceFolderImport::fromDirectory($virtualSourceDir, 'http://example.test/manuscripts/virtual');

assertTrueValue(in_array('Duplicate XML for folio50', $virtualResult['errors'], true), 'Source-folder import should warn about duplicate XML candidates for the same basename');

source_folder_import_virtual_directory_clear();

echo 'source_folder_import_test: ok' . PHP_EOL;
}
