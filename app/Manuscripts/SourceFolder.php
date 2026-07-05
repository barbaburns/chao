<?php

namespace App\Manuscripts;

class SourceFolder
{
    public static function resolve($subfolder)
    {
        $subfolder = trim((string) $subfolder);

        if ($subfolder === '') {
            throw new \InvalidArgumentException('Manuscript source subfolder is required.');
        }

        if (strpos($subfolder, '..') !== false || preg_match('/[\\\\\\/]/', $subfolder)) {
            throw new \InvalidArgumentException('Invalid manuscript source subfolder.');
        }

        return [
            'subfolder' => $subfolder,
            'path' => static::basePath() . '/' . $subfolder,
            'url' => static::baseUrl() . '/' . $subfolder,
        ];
    }

    public static function basePath()
    {
        return WP_CONTENT_DIR . '/manuscripts-source';
    }

    public static function baseUrl()
    {
        return content_url('manuscripts-source');
    }
}
