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

$url = Admin::buildProcessUrl(77, 'nonce-value', 'http://example.test/wp-admin/');
$editUrl = Admin::buildEditUrl(77, [
    'chao_manuscript_worker_notice' => 'success',
], 'http://example.test/wp-admin/');
$requestData = Admin::extractProcessRequestData([
    'post_id' => '77',
    'chao_manuscript_worker_nonce' => 'nonce-value',
]);
$notice = Admin::getNoticeConfig('success');

assertTrueValue(strpos($url, 'admin-post.php') !== false, 'Process URL should target admin-post.php');
assertTrueValue(strpos($url, 'action=chao_process_manuscript_pdf') !== false, 'Process URL should include the action');
assertTrueValue(strpos($url, 'post_id=77') !== false, 'Process URL should include the post id');
assertTrueValue(strpos($url, 'chao_manuscript_worker_nonce=nonce-value') !== false, 'Process URL should include the nonce');
assertTrueValue(strpos($editUrl, 'post.php') !== false, 'Edit URL should target post.php');
assertTrueValue(strpos($editUrl, 'post=77') !== false, 'Edit URL should include the post id');
assertTrueValue(strpos($editUrl, 'action=edit') !== false, 'Edit URL should keep edit action');
assertTrueValue(strpos($editUrl, 'chao_manuscript_worker_notice=success') !== false, 'Edit URL should include worker notice status');
assertTrueValue($requestData['post_id'] === 77, 'Request parser should read post_id from GET-style payloads');
assertTrueValue($requestData['nonce'] === 'nonce-value', 'Request parser should read nonce from GET-style payloads');
assertTrueValue(is_array($notice), 'Notice config should return an array for known statuses');
assertTrueValue($notice['type'] === 'success', 'Success notice should use success type');
assertTrueValue(strlen($notice['message']) > 0, 'Success notice should include a message');

echo 'admin_process_url_test: ok' . PHP_EOL;
