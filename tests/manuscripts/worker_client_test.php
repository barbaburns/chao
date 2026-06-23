<?php

namespace App\Manuscripts;

function home_url($path = '')
{
    return 'http://chao.local' . $path;
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

class TestWorkerClient extends WorkerClient
{
    protected function portSpeaksHttp($homeHost, $port)
    {
        return in_array((int) $port, [10020, 10024], true);
    }

    protected function portLooksLikeWordPress($homeHost, $port)
    {
        return (int) $port === 10024;
    }
}

class PreferredHttpWorkerClient extends WorkerClient
{
    protected function portSpeaksHttp($homeHost, $port)
    {
        return in_array((int) $port, [10020, 10023], true);
    }

    protected function portLooksLikeWordPress($homeHost, $port)
    {
        return in_array((int) $port, [10020, 10023], true);
    }
}

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$client = new WorkerClient('http://127.0.0.1:8002/');
$testClient = new TestWorkerClient('http://127.0.0.1:8002/');
$preferredHttpClient = new PreferredHttpWorkerClient('http://127.0.0.1:8002/');
$payload = $client->buildJobPayload(42, 'http://chao.local/uploads/manuscrito.pdf');
$relativePayload = $client->buildJobPayload(43, '/wp-content/uploads/manuscrito.pdf');
putenv('MANUSCRIPT_SOURCE_BASE_URL=http://host.docker.internal:10023');
$dockerPayload = $client->buildJobPayload(44, 'http://chao.local/wp-content/uploads/manuscrito.pdf');
putenv('MANUSCRIPT_SOURCE_BASE_URL');
$localSiteConfig = tempnam(sys_get_temp_dir(), 'manuscript-local-site-');
file_put_contents($localSiteConfig, json_encode([
    'services' => [
        'nginx' => [
            'ports' => [
                'HTTP' => [10020],
            ],
        ],
        'mysql' => [
            'ports' => [
                'MYSQL' => [10023],
            ],
        ],
        'mailhog' => [
            'ports' => [
                'WEB' => [10020],
            ],
        ],
        'actual_http' => [
            'ports' => [
                'ALT' => [10024],
            ],
        ],
    ],
]));
putenv('MANUSCRIPT_LOCAL_SITE_JSON=' . $localSiteConfig);
$autoDockerPayload = $testClient->buildJobPayload(45, 'http://chao.local/wp-content/uploads/auto.pdf');
putenv('MANUSCRIPT_LOCAL_SITE_JSON');
@unlink($localSiteConfig);

$preferredLocalSiteConfig = tempnam(sys_get_temp_dir(), 'manuscript-local-site-preferred-');
file_put_contents($preferredLocalSiteConfig, json_encode([
    'services' => [
        'mailhog' => [
            'ports' => [
                'WEB' => [10020],
            ],
        ],
        'nginx' => [
            'role' => 'http',
            'ports' => [
                'HTTP' => [10023],
            ],
        ],
    ],
]));
putenv('MANUSCRIPT_LOCAL_SITE_JSON=' . $preferredLocalSiteConfig);
$preferredPayload = $preferredHttpClient->buildJobPayload(46, 'http://chao.local/wp-content/uploads/preferred.pdf');
putenv('MANUSCRIPT_LOCAL_SITE_JSON');
@unlink($preferredLocalSiteConfig);

assertSameValue('http://127.0.0.1:8002', $client->getBaseUrl(), 'Worker base URL should be normalized');
assertSameValue(42, $payload['manuscript_id'], 'Payload should keep manuscript id');
assertSameValue('http://chao.local/uploads/manuscrito.pdf', $payload['pdf_url'], 'Payload should keep PDF URL');
assertSameValue(43, $relativePayload['manuscript_id'], 'Payload should keep manuscript id for relative paths');
assertSameValue('http://chao.local/wp-content/uploads/manuscrito.pdf', $relativePayload['pdf_url'], 'Payload should normalize relative PDF URLs');
assertSameValue(44, $dockerPayload['manuscript_id'], 'Payload should keep manuscript id for docker rewrite');
assertSameValue('http://host.docker.internal:10023/wp-content/uploads/manuscrito.pdf', $dockerPayload['pdf_url'], 'Payload should rewrite local site URLs for docker worker access');
assertSameValue(45, $autoDockerPayload['manuscript_id'], 'Payload should keep manuscript id for automatic Local rewrite');
assertSameValue('http://host.docker.internal:10024/wp-content/uploads/auto.pdf', $autoDockerPayload['pdf_url'], 'Payload should auto-detect the actual HTTP port even when Local labels are inconsistent');
assertSameValue(46, $preferredPayload['manuscript_id'], 'Payload should keep manuscript id when preferring the declared HTTP service');
assertSameValue('http://host.docker.internal:10023/wp-content/uploads/preferred.pdf', $preferredPayload['pdf_url'], 'Payload should prefer the declared HTTP/nginx port before generic HTTP responders like MailHog');

echo 'worker_client_test: ok' . PHP_EOL;
