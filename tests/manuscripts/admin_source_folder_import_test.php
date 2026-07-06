<?php

namespace App\Manuscripts;

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', '/srv/www/wp-content');
}

$mockFields = [
    'manuscript_source_subfolder' => 'venancia-caderno',
];

$mockMeta = [
    77 => [
        'chao_manuscript_import_last_error' => '',
        'chao_manuscript_import_last_errors' => [
            'Imagem sem XML correspondente: venanciaCaderno_0099A',
            'XML sem imagem correspondente: venanciaCaderno_0100A',
        ],
        'chao_manuscript_import_last_snapshot' => [],
    ],
];

function get_field($fieldName, $postId, $formatValue = false)
{
    global $mockFields;

    return isset($mockFields[$fieldName]) ? $mockFields[$fieldName] : '';
}

function get_post_meta($postId, $key, $single)
{
    global $mockMeta;

    return isset($mockMeta[$postId][$key]) ? $mockMeta[$postId][$key] : '';
}

function wp_create_nonce($action)
{
    return 'source-folder-nonce';
}

function content_url($path = '')
{
    $path = ltrim((string) $path, '/');

    return 'http://example.test/wp-content' . ($path === '' ? '' : '/' . $path);
}

function admin_url()
{
    return 'http://example.test/wp-admin/';
}

function add_query_arg($queryArgs, $url)
{
    return $url . '?' . http_build_query($queryArgs);
}

function __($message, $domain = null)
{
    return $message;
}

function esc_html__($message, $domain = null)
{
    return $message;
}

function esc_html($value)
{
    return (string) $value;
}

function esc_url($value)
{
    return (string) $value;
}

function esc_attr($value)
{
    return (string) $value;
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

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$snapshot = Admin::buildImportSnapshot([
    'source_name' => 'venancia-caderno',
    'page_count' => 2,
    'pages' => [
        [
            'page_number' => 1,
            'basename' => 'venanciaCaderno_0051B',
            'filename' => 'venanciaCaderno_0051B.jpg',
            'image_url' => 'http://example.test/wp-content/manuscripts-source/venancia-caderno/venanciaCaderno_0051B.jpg',
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
    'errors' => [
        'Imagem sem XML correspondente: venanciaCaderno_0099A',
        'XML sem imagem correspondente: venanciaCaderno_0100A',
    ],
]);

$GLOBALS['mockMeta'][77]['chao_manuscript_import_last_snapshot'] = $snapshot;

$post = (object) [
    'ID' => 77,
];

ob_start();
Admin::renderMetabox($post);
$metaboxMarkup = ob_get_clean();

assertTrueValue(strpos($metaboxMarkup, 'Subpasta fonte do manuscrito') !== false, 'Metabox should describe the manuscript source subfolder');
assertTrueValue(strpos($metaboxMarkup, 'venancia-caderno') !== false, 'Metabox should show the configured manuscript source subfolder');
assertTrueValue(strpos($metaboxMarkup, 'Importar da subpasta') !== false, 'Metabox should expose the source-folder import action');
assertTrueValue(strpos($metaboxMarkup, 'action=chao_import_manuscript_source_folder') !== false, 'Metabox button should point to the source-folder import action');
assertTrueValue(strpos($metaboxMarkup, 'Pacote ZIP do manuscrito') === false, 'Metabox should no longer mention ZIP-based manuscript imports');

ob_start();
Admin::renderPagesMetabox($post);
$pagesMarkup = ob_get_clean();

assertTrueValue(strpos($pagesMarkup, 'Resumo compacto das paginas importadas da pasta fonte') !== false, 'Pages metabox should describe the compact source-folder page summary');
assertTrueValue(strpos($pagesMarkup, 'Paginas importadas: 1') !== false, 'Pages metabox should show the imported pages count');
assertTrueValue(strpos($pagesMarkup, 'Imagens sem XML correspondente: 1') !== false, 'Pages metabox should show unmatched image count');
assertTrueValue(strpos($pagesMarkup, 'XML sem imagem correspondente: 1') !== false, 'Pages metabox should show unmatched XML count');
assertTrueValue(strpos($pagesMarkup, 'pacote ZIP') === false, 'Pages metabox should no longer mention ZIP summaries');

unset($GLOBALS['mockFields']['manuscript_source_subfolder']);
$GLOBALS['mockMeta'][77]['chao_manuscript_import_source'] = [
    'source_subfolder' => 'venancia-antiga',
    'source_path' => '/srv/www/wp-content/manuscripts-source/venancia-antiga',
    'source_url' => 'http://example.test/wp-content/manuscripts-source/venancia-antiga',
];

ob_start();
Admin::renderMetabox($post);
$staleSourceMetaboxMarkup = ob_get_clean();

assertTrueValue(strpos($staleSourceMetaboxMarkup, 'Importar da subpasta') === false, 'Metabox should not offer source-folder import when the ACF subfolder field is empty');
assertTrueValue(strpos($staleSourceMetaboxMarkup, 'venancia-antiga') === false, 'Metabox should not resurrect stale stored source-folder metadata when the field is empty');

$_GET = [
    'post' => '77',
    'action' => 'edit',
    'chao_manuscript_import_notice' => 'missing_source_folder',
];

ob_start();
Admin::renderAdminNotice();
$noticeMarkup = ob_get_clean();

assertTrueValue(strpos($noticeMarkup, 'Nao foi possivel importar: informe uma subpasta fonte valida do manuscrito.') !== false, 'Import notice should use source-folder copy');

echo 'admin_source_folder_import_test: ok' . PHP_EOL;
