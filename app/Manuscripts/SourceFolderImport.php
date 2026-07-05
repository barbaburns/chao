<?php

namespace App\Manuscripts;

class SourceFolderImport
{
    protected static $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    public static function fromDirectory($sourceDir, $sourceUrl)
    {
        $sourceDir = rtrim((string) $sourceDir, DIRECTORY_SEPARATOR);
        $sourceUrl = rtrim((string) $sourceUrl, '/');
        $entries = @scandir($sourceDir);

        if ($entries === false) {
            throw new \RuntimeException('Unable to read manuscript source directory.');
        }

        $pairs = [];
        $errors = [];

        foreach ($entries as $entryName) {
            if ($entryName === '.' || $entryName === '..') {
                continue;
            }

            $entryPath = $sourceDir . DIRECTORY_SEPARATOR . $entryName;

            if (is_dir($entryPath)) {
                $errors[] = 'Subdirectories are not supported: ' . $entryName;
                continue;
            }

            $basename = (string) pathinfo($entryName, PATHINFO_FILENAME);
            $extension = strtolower((string) pathinfo($entryName, PATHINFO_EXTENSION));

            if (!isset($pairs[$basename])) {
                $pairs[$basename] = [
                    'image' => null,
                    'xml' => null,
                ];
            }

            if (in_array($extension, static::$imageExtensions, true)) {
                if ($pairs[$basename]['image'] !== null) {
                    $errors[] = 'Duplicate image for ' . $basename;
                    continue;
                }

                $pairs[$basename]['image'] = $entryName;
                continue;
            }

            if ($extension === 'xml') {
                if ($pairs[$basename]['xml'] !== null) {
                    $errors[] = 'Duplicate XML for ' . $basename;
                    continue;
                }

                $pairs[$basename]['xml'] = $entryName;
                continue;
            }

            $errors[] = 'Unsupported file type: ' . $entryName;
        }

        ksort($pairs, SORT_NATURAL);

        $pages = [];

        foreach ($pairs as $basename => $pair) {
            if ($pair['image'] === null && $pair['xml'] !== null) {
                $errors[] = 'XML sem imagem correspondente: ' . $basename;
                continue;
            }

            if ($pair['xml'] === null && $pair['image'] !== null) {
                $errors[] = 'Imagem sem XML correspondente: ' . $basename;
                continue;
            }

            if ($pair['image'] === null || $pair['xml'] === null) {
                continue;
            }

            try {
                $layout = AltoXml::parse((string) file_get_contents($sourceDir . DIRECTORY_SEPARATOR . $pair['xml']));
            } catch (\InvalidArgumentException $exception) {
                $errors[] = 'Invalid XML for ' . $basename . ': ' . $exception->getMessage();
                continue;
            }

            $lines = isset($layout['lines']) && is_array($layout['lines']) ? $layout['lines'] : [];

            $pages[] = [
                'page_number' => count($pages) + 1,
                'basename' => $basename,
                'filename' => $pair['image'],
                'image_path' => $sourceDir . DIRECTORY_SEPARATOR . $pair['image'],
                'image_url' => $sourceUrl . '/' . rawurlencode($pair['image']),
                'raw_text' => isset($layout['text']) ? (string) $layout['text'] : '',
                'layout_data' => [
                    'text' => isset($layout['text']) ? (string) $layout['text'] : '',
                    'lines' => $lines,
                    'page' => isset($layout['page']) && is_array($layout['page']) ? $layout['page'] : [],
                ],
                'highlight_exact' => static::hasExactWordGeometry($lines),
                'import_status' => 'imported',
                'import_errors' => [],
            ];
        }

        return [
            'source_name' => basename($sourceDir),
            'page_count' => count($pages),
            'pages' => $pages,
            'errors' => array_values($errors),
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
}
