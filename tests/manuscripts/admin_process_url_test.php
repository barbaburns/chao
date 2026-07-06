<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Manuscripts\Admin;

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$url = Admin::buildImportUrl(77, 'nonce-value', 'http://example.test/wp-admin/');
$editUrl = Admin::buildEditUrl(77, [
    'chao_manuscript_import_notice' => 'success',
], 'http://example.test/wp-admin/');
$requestData = Admin::extractImportRequestData([
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
    'subfolder' => 'venancia-request',
    'response_format' => 'json',
]);
$defaultRequestData = Admin::extractImportRequestData([
    'post_id' => '77',
    'chao_manuscript_import_nonce' => 'nonce-value',
]);
$notice = Admin::getNoticeConfig('success', 'import');
$missingSourceNotice = Admin::getNoticeConfig('missing_source_folder', 'import');
$importUnavailableNotice = Admin::getNoticeConfig('import_unavailable', 'import');
$legacyZipNotice = Admin::getNoticeConfig('missing_zip', 'import');

assertTrueValue(strpos($url, 'admin-post.php') !== false, 'Import URL should target admin-post.php');
assertTrueValue(strpos($url, 'action=chao_import_manuscript_source_folder') !== false, 'Import URL should include the source-folder action');
assertTrueValue(strpos($url, 'post_id=77') !== false, 'Import URL should include the post id');
assertTrueValue(strpos($url, 'chao_manuscript_import_nonce=nonce-value') !== false, 'Import URL should include the import nonce');
assertTrueValue(strpos($editUrl, 'post.php') !== false, 'Edit URL should target post.php');
assertTrueValue(strpos($editUrl, 'post=77') !== false, 'Edit URL should include the post id');
assertTrueValue(strpos($editUrl, 'action=edit') !== false, 'Edit URL should keep edit action');
assertTrueValue(strpos($editUrl, 'chao_manuscript_import_notice=success') !== false, 'Edit URL should include import notice status');
assertTrueValue($requestData['post_id'] === 77, 'Import request parser should read post_id');
assertTrueValue($requestData['nonce'] === 'nonce-value', 'Import request parser should read the import nonce');
assertTrueValue($requestData['subfolder'] === 'venancia-request', 'Import request parser should read the typed subfolder value');
assertTrueValue($requestData['response_format'] === 'json', 'Import request parser should read the JSON response format');
assertTrueValue($defaultRequestData['subfolder'] === '', 'Import request parser should default subfolder to an empty string');
assertTrueValue($defaultRequestData['response_format'] === 'redirect', 'Import request parser should default response_format to redirect');
assertTrueValue($notice['type'] === 'success', 'Success notice should use success type');
assertTrueValue($notice['message'] === 'Pasta fonte do manuscrito importada com sucesso.', 'Success notice should use source-folder copy');
assertTrueValue($missingSourceNotice['type'] === 'error', 'Missing source-folder notice should use error type');
assertTrueValue($missingSourceNotice['message'] === 'Nao foi possivel importar: informe uma subpasta fonte valida do manuscrito.', 'Missing source-folder notice should explain that the manuscript source subfolder is required');
assertTrueValue($importUnavailableNotice['type'] === 'warning', 'Unavailable import notice should use warning type');
assertTrueValue($importUnavailableNotice['message'] === 'A importacao da pasta fonte do manuscrito ainda nao esta disponivel neste ambiente.', 'Unavailable import notice should use source-folder wording');
assertTrueValue($legacyZipNotice === null, 'Legacy ZIP notice config should be removed in the source-folder-only flow');

echo 'admin_process_url_test: ok' . PHP_EOL;
