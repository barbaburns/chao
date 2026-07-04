<?php

namespace App\Manuscripts;

class ZipImport
{
    protected static $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    public static function fromZipPath($zipPath, array $options = [])
    {
        $zip = new \ZipArchive();

        if ($zip->open((string) $zipPath) !== true) {
            throw new \RuntimeException('Unable to open manuscript ZIP.');
        }

        $entries = [];
        $errors = [];

        for ($index = 0; $index < $zip->numFiles; $index += 1) {
            $name = (string) $zip->getNameIndex($index);

            if (static::shouldIgnoreEntry($name)) {
                continue;
            }

            if (strpos($name, '/') !== false) {
                $errors[] = 'Subdirectories are not supported: ' . $name;
                continue;
            }

            $basename = (string) pathinfo($name, PATHINFO_FILENAME);
            $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

            if ($basename === '') {
                continue;
            }

            if (!isset($entries[$basename])) {
                $entries[$basename] = [
                    'image' => null,
                    'xml' => null,
                ];
            }

            if (in_array($extension, static::$imageExtensions, true)) {
                if ($entries[$basename]['image'] !== null) {
                    $errors[] = 'Duplicate image for ' . $basename;
                    continue;
                }

                $entries[$basename]['image'] = $name;
                continue;
            }

            if ($extension === 'xml') {
                if ($entries[$basename]['xml'] !== null) {
                    $errors[] = 'Duplicate XML for ' . $basename;
                    continue;
                }

                $entries[$basename]['xml'] = $name;
                continue;
            }

            $errors[] = 'Unsupported file type: ' . $name;
        }

        ksort($entries, SORT_NATURAL);

        $pages = [];

        foreach ($entries as $basename => $entry) {
            if ($entry['image'] === null || $entry['xml'] === null) {
                $errors[] = 'Missing pair for ' . $basename;
                continue;
            }

            try {
                $layout = AltoXml::parse((string) $zip->getFromName($entry['xml']));
            } catch (\InvalidArgumentException $exception) {
                $errors[] = 'Invalid XML for ' . $basename . ': ' . $exception->getMessage();
                continue;
            }

            $imageAsset = static::extractImageAsset($zip, $entry['image'], $options);

            $pages[] = [
                'page_number' => count($pages) + 1,
                'basename' => $basename,
                'filename' => basename($entry['image']),
                'image_path' => $imageAsset['path'],
                'image_url' => $imageAsset['url'],
                'raw_text' => isset($layout['text']) ? (string) $layout['text'] : '',
                'layout_data' => [
                    'text' => isset($layout['text']) ? (string) $layout['text'] : '',
                    'lines' => isset($layout['lines']) && is_array($layout['lines']) ? $layout['lines'] : [],
                    'page' => isset($layout['page']) && is_array($layout['page']) ? $layout['page'] : [],
                ],
                'highlight_exact' => static::hasExactWordGeometry(isset($layout['lines']) && is_array($layout['lines']) ? $layout['lines'] : []),
                'import_status' => 'imported',
                'import_errors' => [],
            ];
        }

        $zip->close();

        return [
            'zip_name' => basename((string) $zipPath),
            'page_count' => count($pages),
            'pages' => $pages,
            'errors' => array_values($errors),
        ];
    }

    protected static function extractImageAsset(\ZipArchive $zip, $imageEntryName, array $options = [])
    {
        $filename = basename((string) $imageEntryName);
        $outputDir = isset($options['image_output_dir']) ? rtrim((string) $options['image_output_dir'], DIRECTORY_SEPARATOR) : '';
        $outputUrl = isset($options['image_output_url']) ? rtrim((string) $options['image_output_url'], '/') : '';

        if ($outputDir === '' || $outputUrl === '') {
            return [
                'path' => '',
                'url' => '',
            ];
        }

        static::ensureDirectory($outputDir);

        $imageContents = $zip->getFromName((string) $imageEntryName);

        if ($imageContents === false) {
            throw new \RuntimeException('Unable to extract manuscript image: ' . $filename);
        }

        $outputPath = $outputDir . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($outputPath, $imageContents) === false) {
            throw new \RuntimeException('Unable to persist manuscript image: ' . $filename);
        }

        return [
            'path' => $outputPath,
            'url' => $outputUrl . '/' . rawurlencode($filename),
        ];
    }

    protected static function hasExactWordGeometry(array $lines)
    {
        foreach ($lines as $line) {
            $words = isset($line['words']) && is_array($line['words']) ? $line['words'] : [];

            foreach ($words as $word) {
                $bbox = isset($word['bbox']) && is_array($word['bbox']) ? $word['bbox'] : [];

                if (
                    isset($bbox['x'], $bbox['y'], $bbox['width'], $bbox['height']) &&
                    (float) $bbox['width'] > 0 &&
                    (float) $bbox['height'] > 0
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function ensureDirectory($path)
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create manuscript image directory.');
        }
    }

    protected static function shouldIgnoreEntry($name)
    {
        $name = ltrim((string) $name, '/');

        if ($name === '' || substr($name, -1) === '/') {
            return true;
        }

        $segments = explode('/', $name);
        $firstSegment = isset($segments[0]) ? $segments[0] : '';
        $lastSegment = end($segments);

        return $firstSegment === '__MACOSX' || ($lastSegment !== false && strpos((string) $lastSegment, '.') === 0);
    }
}
