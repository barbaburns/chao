<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\ZipImport;

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

$zipPath = tempnam(sys_get_temp_dir(), 'manuscript-zip-') . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('testesMetsALTO/venanciaCaderno_0052A.jpg', 'fake-image-two');
$zip->addFromString('testesMetsALTO/venanciaCaderno_0052A.xml', '<?xml version="1.0" encoding="UTF-8"?><alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="10" HEIGHT="20"><PrintSpace><TextBlock><TextLine HPOS="3" VPOS="4" WIDTH="5" HEIGHT="6"><String CONTENT="Margem" HPOS="3" VPOS="4" WIDTH="5" HEIGHT="6"/></TextLine></TextBlock></PrintSpace></Page></Layout></alto>');
$zip->addFromString('testesMetsALTO/venanciaCaderno_0051B.jpg', 'fake-image-one');
$zip->addFromString('testesMetsALTO/venanciaCaderno_0051B.xml', '<?xml version="1.0" encoding="UTF-8"?><alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="10" HEIGHT="20"><PrintSpace><TextBlock><TextLine HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"><String CONTENT="Casa" HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"/></TextLine></TextBlock></PrintSpace></Page></Layout></alto>');
$zip->addFromString('testesMetsALTO/venanciaCaderno_0099A.jpg', 'orphan-image');
$zip->close();

$imageOutputDir = sys_get_temp_dir() . '/manuscript-zip-import-test';
@mkdir($imageOutputDir, 0777, true);

$result = ZipImport::fromZipPath($zipPath, [
    'image_output_dir' => $imageOutputDir,
    'image_output_url' => 'http://example.test/uploads/manuscripts',
]);

assertSameValue(basename($zipPath), $result['zip_name'], 'ZIP import should keep the source ZIP filename');
assertSameValue(2, $result['page_count'], 'ZIP import should count only valid image/XML pairs');
assertSameValue('venanciaCaderno_0051B', $result['pages'][0]['basename'], 'ZIP import should sort valid basenames naturally');
assertSameValue('venanciaCaderno_0052A', $result['pages'][1]['basename'], 'ZIP import should keep later basenames after sorting');
assertSameValue(1, $result['pages'][0]['page_number'], 'ZIP import should assign sequential page numbers from sorted basenames');
assertSameValue(2, $result['pages'][1]['page_number'], 'ZIP import should keep sequential numbering across valid pairs');
assertSameValue('Casa', $result['pages'][0]['raw_text'], 'ZIP import should parse searchable text from ALTO XML');
assertSameValue('Margem', $result['pages'][1]['layout_data']['text'], 'ZIP import should persist parsed layout text');
assertSameValue('http://example.test/uploads/manuscripts/venanciaCaderno_0051B.jpg', $result['pages'][0]['image_url'], 'ZIP import should expose extracted image URLs for the viewer');
assertTrueValue(is_file($result['pages'][0]['image_path']), 'ZIP import should persist extracted images to disk');
assertSameValue(true, $result['pages'][0]['highlight_exact'], 'ZIP import should detect exact word geometry when word boxes are present');
assertSameValue([], $result['pages'][0]['import_errors'], 'ZIP import should keep valid pages free of per-page errors');
assertTrueValue(in_array('Missing pair for venanciaCaderno_0099A', $result['errors'], true), 'ZIP import should report orphan files by basename');
assertTrueValue(!in_array('Subdirectories are not supported: testesMetsALTO/venanciaCaderno_0051B.jpg', $result['errors'], true), 'ZIP import should accept page files stored inside a root folder');

@unlink($result['pages'][0]['image_path']);
@unlink($result['pages'][1]['image_path']);
@rmdir($imageOutputDir);
@unlink($zipPath);

$sourceJpegPath = sys_get_temp_dir() . '/manuscript-source-normalize.jpg';
$sourceImage = imagecreatetruecolor(8, 6);
$background = imagecolorallocate($sourceImage, 240, 220, 180);
imagefill($sourceImage, 0, 0, $background);
imagejpeg($sourceImage, $sourceJpegPath, 92);
imagedestroy($sourceImage);
file_put_contents($sourceJpegPath, file_get_contents($sourceJpegPath) . 'MANUSCRIPT_TRAILING_BYTES');

$zipPath = tempnam(sys_get_temp_dir(), 'manuscript-zip-normalize-') . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFile($sourceJpegPath, 'normalize/venanciaCaderno_0101A.jpg');
$zip->addFromString('normalize/venanciaCaderno_0101A.xml', '<?xml version="1.0" encoding="UTF-8"?><alto xmlns="http://www.loc.gov/standards/alto/ns-v4#"><Layout><Page WIDTH="8" HEIGHT="6"><PrintSpace><TextBlock><TextLine HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"><String CONTENT="Teste" HPOS="1" VPOS="2" WIDTH="3" HEIGHT="4"/></TextLine></TextBlock></PrintSpace></Page></Layout></alto>');
$zip->close();

$imageOutputDir = sys_get_temp_dir() . '/manuscript-zip-normalize-test';
@mkdir($imageOutputDir, 0777, true);

$originalMemoryLimit = ini_get('memory_limit');
ini_set('memory_limit', '64M');

$normalizedResult = ZipImport::fromZipPath($zipPath, [
    'image_output_dir' => $imageOutputDir,
    'image_output_url' => 'http://example.test/uploads/manuscripts-normalized',
    'image_max_dimension' => 4,
]);

assertSameValue(1, $normalizedResult['page_count'], 'ZIP import should import the normalization fixture');
assertTrueValue(md5_file($normalizedResult['pages'][0]['image_path']) !== md5_file($sourceJpegPath), 'ZIP import should normalize extracted JPEGs instead of copying the original binary untouched');
assertSameValue('64M', ini_get('memory_limit'), 'ZIP import should restore the original PHP memory limit after normalizing a page image');
assertSameValue([4, 3], array_slice(getimagesize($normalizedResult['pages'][0]['image_path']), 0, 2), 'ZIP import should downscale oversized viewer images to a browser-safer dimension');

ini_set('memory_limit', $originalMemoryLimit);

@unlink($normalizedResult['pages'][0]['image_path']);
@rmdir($imageOutputDir);
@unlink($zipPath);
@unlink($sourceJpegPath);

echo 'zip_import_test: ok' . PHP_EOL;
