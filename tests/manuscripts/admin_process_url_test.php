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
]);
$notice = Admin::getNoticeConfig('success', 'import');
$missingZipNotice = Admin::getNoticeConfig('missing_zip', 'import');
$importUnavailableNotice = Admin::getNoticeConfig('import_unavailable', 'import');

assertTrueValue(strpos($url, 'admin-post.php') !== false, 'Import URL should target admin-post.php');
assertTrueValue(strpos($url, 'action=chao_import_manuscript_zip') !== false, 'Import URL should include the ZIP action');
assertTrueValue(strpos($url, 'post_id=77') !== false, 'Import URL should include the post id');
assertTrueValue(strpos($url, 'chao_manuscript_import_nonce=nonce-value') !== false, 'Import URL should include the ZIP nonce');
assertTrueValue(strpos($editUrl, 'post.php') !== false, 'Edit URL should target post.php');
assertTrueValue(strpos($editUrl, 'post=77') !== false, 'Edit URL should include the post id');
assertTrueValue(strpos($editUrl, 'action=edit') !== false, 'Edit URL should keep edit action');
assertTrueValue(strpos($editUrl, 'chao_manuscript_import_notice=success') !== false, 'Edit URL should include ZIP import notice status');
assertTrueValue($requestData['post_id'] === 77, 'Import request parser should read post_id');
assertTrueValue($requestData['nonce'] === 'nonce-value', 'Import request parser should read ZIP nonce');
assertTrueValue($notice['type'] === 'success', 'ZIP success notice should use success type');
assertTrueValue($notice['message'] === 'Pacote ZIP do manuscrito importado com sucesso.', 'ZIP success notice should use ZIP-specific copy');
assertTrueValue($missingZipNotice['type'] === 'error', 'Missing ZIP notice should use error type');
assertTrueValue($missingZipNotice['message'] === 'Nao foi possivel importar: envie um pacote ZIP do manuscrito.', 'Missing ZIP notice should explain that the manuscript ZIP is required');
assertTrueValue($importUnavailableNotice['type'] === 'warning', 'Unavailable ZIP import notice should use warning type');
assertTrueValue($importUnavailableNotice['message'] === 'A importacao do pacote ZIP ainda nao esta disponivel neste ambiente.', 'Unavailable ZIP import notice should explain that the ZIP pipeline is not ready yet');

echo 'admin_process_url_test: ok' . PHP_EOL;
