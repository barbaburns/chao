<?php

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', '/srv/www/wp-content');
}

if (!function_exists('content_url')) {
    function content_url($path = '')
    {
        $path = ltrim((string) $path, '/');

        return 'http://example.test/wp-content' . ($path === '' ? '' : '/' . $path);
    }
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\SourceFolder;

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function assertThrows($expectedMessage, callable $callback, $message)
{
    try {
        $callback();
    } catch (\InvalidArgumentException $exception) {
        if ($exception->getMessage() !== $expectedMessage) {
            fwrite(STDERR, $message . PHP_EOL);
            fwrite(STDERR, 'Expected: ' . var_export($expectedMessage, true) . PHP_EOL);
            fwrite(STDERR, 'Actual:   ' . var_export($exception->getMessage(), true) . PHP_EOL);
            exit(1);
        }

        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected exception: ' . var_export($expectedMessage, true) . PHP_EOL);
    exit(1);
}

$resolved = SourceFolder::resolve('venancia-caderno');

assertSameValue('venancia-caderno', $resolved['subfolder'], 'SourceFolder should return the provided valid subfolder unchanged');
assertSameValue('/srv/www/wp-content/manuscripts-source/venancia-caderno', $resolved['path'], 'SourceFolder should build the manuscript source path');
assertSameValue('http://example.test/wp-content/manuscripts-source/venancia-caderno', $resolved['url'], 'SourceFolder should build the manuscript source URL');
assertSameValue('/srv/www/wp-content/manuscripts-source', SourceFolder::basePath(), 'SourceFolder should expose the manuscript source base path');
assertSameValue('http://example.test/wp-content/manuscripts-source', SourceFolder::baseUrl(), 'SourceFolder should expose the manuscript source base URL');

assertThrows('Manuscript source subfolder is required.', function () {
    SourceFolder::resolve('');
}, 'SourceFolder should require a subfolder name');

assertThrows('Invalid manuscript source subfolder.', function () {
    SourceFolder::resolve('../venancia');
}, 'SourceFolder should reject directory traversal input');

assertThrows('Invalid manuscript source subfolder.', function () {
    SourceFolder::resolve('venancia/caderno');
}, 'SourceFolder should reject forward slash path separators');

assertThrows('Invalid manuscript source subfolder.', function () {
    SourceFolder::resolve('venancia\\caderno');
}, 'SourceFolder should reject backslash path separators');

echo 'source_folder_test: ok' . PHP_EOL;
