<?php

namespace App\Manuscripts;

$mockFields = [
    'manuscript_zip_file' => 321,
];

$mockAttachmentUrls = [
    321 => 'http://example.test/uploads/venancia.zip',
];

$mockMeta = [
    77 => [
        'chao_manuscript_import_last_error' => '',
        'chao_manuscript_page_review' => [
            1 => [
                'status' => 'revisado',
                'transcription' => 'Texto revisto',
                'notes' => 'Observacao editorial',
            ],
        ],
    ],
];

function get_field($fieldName, $postId, $formatValue = false)
{
    global $mockFields;

    return isset($mockFields[$fieldName]) ? $mockFields[$fieldName] : 0;
}

function get_post_meta($postId, $key, $single)
{
    global $mockMeta;

    return isset($mockMeta[$postId][$key]) ? $mockMeta[$postId][$key] : '';
}

function wp_get_attachment_url($attachmentId)
{
    global $mockAttachmentUrls;

    return isset($mockAttachmentUrls[$attachmentId]) ? $mockAttachmentUrls[$attachmentId] : '';
}

function wp_create_nonce($action)
{
    return 'zip-nonce';
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

function esc_textarea($value)
{
    return (string) $value;
}

function wp_nonce_field($action, $name)
{
    echo '<input type="hidden" name="' . $name . '" value="' . $action . '">';
}

function selected($selectedValue, $currentValue, $display)
{
    return $selectedValue === $currentValue ? ' selected="selected"' : '';
}

function wp_json_encode($value, $flags = 0)
{
    return json_encode($value, $flags);
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

$GLOBALS['mockMeta'][77]['chao_manuscript_import_last_snapshot'] = $snapshot;

$rows = Admin::buildImportedPageRows($snapshot, $GLOBALS['mockMeta'][77]['chao_manuscript_page_review']);

assertSameValue('venancia.zip', $snapshot['zip_name'], 'Snapshot should keep ZIP filename');
assertSameValue(2, $snapshot['page_count'], 'Snapshot should keep page count');
assertSameValue('venanciaCaderno_0051B', $snapshot['pages'][0]['basename'], 'Snapshot should keep basename');
assertSameValue('imported', $rows[0]['import_status'], 'Rows should keep import status');
assertSameValue('aproximado', $rows[0]['highlight_status'], 'Rows should mark pages without exact word geometry as approximate');

$post = (object) [
    'ID' => 77,
];

ob_start();
Admin::renderMetabox($post);
$metaboxMarkup = ob_get_clean();

assertTrueValue(strpos($metaboxMarkup, 'Pacote ZIP do manuscrito') !== false, 'Metabox should describe the manuscript ZIP package');
assertTrueValue(strpos($metaboxMarkup, 'venancia.zip') !== false, 'Metabox should show the attached ZIP filename');
assertTrueValue(strpos($metaboxMarkup, 'Importar pacote ZIP') !== false, 'Metabox should expose the ZIP import action');
assertTrueValue(strpos($metaboxMarkup, 'action=chao_import_manuscript_zip') !== false, 'Metabox button should point to the ZIP import action');
assertTrueValue(strpos($metaboxMarkup, 'pdf-worker') === false, 'Metabox should no longer mention the PDF worker flow');

ob_start();
Admin::renderPagesMetabox($post);
$pagesMarkup = ob_get_clean();

assertTrueValue(strpos($pagesMarkup, 'Resumo das paginas importadas do pacote ZIP') !== false, 'Pages metabox should describe imported ZIP pages');
assertTrueValue(strpos($pagesMarkup, 'venanciaCaderno_0051B.jpg') !== false, 'Pages metabox should render imported page filenames');
assertTrueValue(strpos($pagesMarkup, 'aproximado') !== false, 'Pages metabox should render the highlight status from imported rows');
assertTrueValue(strpos($pagesMarkup, 'imported') !== false, 'Pages metabox should render the import status from imported rows');
assertTrueValue(strpos($pagesMarkup, 'Texto revisto') !== false, 'Pages metabox should merge saved review transcription for imported pages');
assertTrueValue(strpos($pagesMarkup, 'Job HTR') === false, 'Pages metabox should no longer render worker-specific HTR columns');

$_GET = [
    'post' => '77',
    'action' => 'edit',
    'chao_manuscript_import_notice' => 'missing_zip',
];

ob_start();
Admin::renderAdminNotice();
$noticeMarkup = ob_get_clean();

assertTrueValue(strpos($noticeMarkup, 'Nao foi possivel importar: envie um pacote ZIP do manuscrito.') !== false, 'Import notice should use the import notice key and ZIP-specific copy');

echo 'admin_zip_import_test: ok' . PHP_EOL;
