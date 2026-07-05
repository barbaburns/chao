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
            $filename = basename($name);
            $basename = (string) pathinfo($filename, PATHINFO_FILENAME);
            $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

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
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        if ($imageContents === false) {
            throw new \RuntimeException('Unable to extract manuscript image: ' . $filename);
        }

        $outputPath = $outputDir . DIRECTORY_SEPARATOR . $filename;

        if (!static::persistBrowserSafeImage($imageContents, $outputPath, $extension, $options) && file_put_contents($outputPath, $imageContents) === false) {
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

    protected static function persistBrowserSafeImage($imageContents, $outputPath, $extension, array $options = [])
    {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }

        if (!in_array($extension, static::$imageExtensions, true)) {
            return false;
        }

        $previousMemoryLimit = static::raiseImageMemoryLimit();
        $image = @imagecreatefromstring($imageContents);

        if ($image === false) {
            static::restoreImageMemoryLimit($previousMemoryLimit);
            return false;
        }

        $normalizedImage = static::resizeImageForViewer($image, $options);
        $saved = false;

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($normalizedImage, $outputPath, 92);
                break;
            case 'png':
                imagesavealpha($normalizedImage, true);
                $saved = imagepng($normalizedImage, $outputPath, 6);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $saved = imagewebp($normalizedImage, $outputPath, 90);
                }
                break;
        }

        if ($normalizedImage !== $image) {
            imagedestroy($normalizedImage);
        }

        imagedestroy($image);
        static::restoreImageMemoryLimit($previousMemoryLimit);

        return $saved;
    }

    protected static function resizeImageForViewer($image, array $options = [])
    {
        $maxDimension = isset($options['image_max_dimension']) ? (int) $options['image_max_dimension'] : 3200;

        if ($maxDimension <= 0 || !function_exists('imagescale')) {
            return $image;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $largestSide = max($width, $height);

        if ($largestSide <= $maxDimension) {
            return $image;
        }

        if ($width >= $height) {
            $targetWidth = $maxDimension;
            $targetHeight = max(1, (int) round(($height / $width) * $maxDimension));
        } else {
            $targetHeight = $maxDimension;
            $targetWidth = max(1, (int) round(($width / $height) * $maxDimension));
        }

        $scaled = imagescale($image, $targetWidth, $targetHeight, IMG_BICUBIC_FIXED);

        if ($scaled === false) {
            return $image;
        }

        if (function_exists('imagealphablending')) {
            imagealphablending($scaled, false);
        }

        if (function_exists('imagesavealpha')) {
            imagesavealpha($scaled, true);
        }

        return $scaled;
    }

    protected static function raiseImageMemoryLimit()
    {
        if (!function_exists('ini_get') || !function_exists('ini_set')) {
            return null;
        }

        $currentLimit = ini_get('memory_limit');
        $currentBytes = static::parseIniSize($currentLimit);

        if ($currentBytes === -1) {
            return null;
        }

        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('image');
            $currentLimit = ini_get('memory_limit');
            $currentBytes = static::parseIniSize($currentLimit);

            if ($currentBytes === -1) {
                return null;
            }
        }

        $targetBytes = 512 * 1024 * 1024;

        if ($currentBytes >= $targetBytes) {
            return null;
        }

        if (@ini_set('memory_limit', static::formatIniSize($targetBytes)) === false) {
            return null;
        }

        return $currentLimit;
    }

    protected static function restoreImageMemoryLimit($previousMemoryLimit)
    {
        if ($previousMemoryLimit === null || !function_exists('ini_set')) {
            return;
        }

        @ini_set('memory_limit', (string) $previousMemoryLimit);
    }

    protected static function parseIniSize($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        if ($value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        switch ($unit) {
            case 'g':
                return (int) round($number * 1024 * 1024 * 1024);
            case 'm':
                return (int) round($number * 1024 * 1024);
            case 'k':
                return (int) round($number * 1024);
            default:
                return (int) round($number);
        }
    }

    protected static function formatIniSize($bytes)
    {
        return (string) max(1, (int) ceil(((int) $bytes) / (1024 * 1024))) . 'M';
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
