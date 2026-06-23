<?php

namespace App\Manuscripts;

class WorkerClient
{
    /** @var string */
    protected $baseUrl;

    public function __construct($baseUrl = null)
    {
        $defaultBaseUrl = getenv('MANUSCRIPT_WORKER_URL') ?: 'http://127.0.0.1:8002';
        $this->baseUrl = rtrim($baseUrl ?: $defaultBaseUrl, '/');
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function buildJobPayload($manuscriptId, $pdfUrl)
    {
        return [
            'manuscript_id' => (int) $manuscriptId,
            'pdf_url' => $this->normalizePdfUrl($pdfUrl),
        ];
    }

    public function submitPdfJob($manuscriptId, $pdfUrl)
    {
        $response = wp_remote_post($this->baseUrl . '/jobs/pdf', [
            'timeout' => 120,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($this->buildJobPayload($manuscriptId, $pdfUrl)),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($statusCode < 200 || $statusCode >= 300) {
            return new \WP_Error(
                'manuscript_worker_request_failed',
                __('O worker local retornou um erro.', 'chao'),
                [
                    'status_code' => $statusCode,
                    'body' => $body,
                ]
            );
        }

        return is_array($body) ? $body : [];
    }

    protected function normalizePdfUrl($pdfUrl)
    {
        $pdfUrl = (string) $pdfUrl;
        $sourceBaseUrl = $this->getSourceBaseUrl();

        if ($pdfUrl === '' || preg_match('~^[a-z][a-z0-9+\-.]*://~i', $pdfUrl)) {
            if ($sourceBaseUrl && $this->shouldRewriteAbsolutePdfUrl($pdfUrl)) {
                return $sourceBaseUrl . $this->extractUrlPathAndQuery($pdfUrl);
            }

            return $pdfUrl;
        }

        $normalizedPath = strpos($pdfUrl, '/') === 0 ? $pdfUrl : '/' . ltrim($pdfUrl, '/');

        if ($sourceBaseUrl) {
            return $sourceBaseUrl . $normalizedPath;
        }

        if (function_exists(__NAMESPACE__ . '\\home_url')) {
            return home_url($normalizedPath);
        }

        if (function_exists('home_url')) {
            return home_url($normalizedPath);
        }

        return $pdfUrl;
    }

    protected function getSourceBaseUrl()
    {
        $explicitBaseUrl = rtrim((string) getenv('MANUSCRIPT_SOURCE_BASE_URL'), '/');

        if ($explicitBaseUrl !== '') {
            return $explicitBaseUrl;
        }

        return $this->detectLocalSourceBaseUrl();
    }

    protected function detectLocalSourceBaseUrl()
    {
        $homeUrl = '';

        if (function_exists(__NAMESPACE__ . '\\home_url')) {
            $homeUrl = (string) home_url('/');
        } elseif (function_exists('home_url')) {
            $homeUrl = (string) home_url('/');
        }

        $homeParts = $this->parseUrl($homeUrl);
        $homeHost = !empty($homeParts['host']) ? strtolower($homeParts['host']) : '';

        if ($homeHost === '' || substr($homeHost, -6) !== '.local') {
            return '';
        }

        $localSiteConfigPath = $this->getLocalSiteConfigPath();

        if ($localSiteConfigPath === '' || !is_readable($localSiteConfigPath)) {
            return '';
        }

        $config = json_decode((string) file_get_contents($localSiteConfigPath), true);
        $httpPort = $this->detectLocalHttpPort($config, $homeHost);

        if (!$httpPort) {
            return '';
        }

        return 'http://host.docker.internal:' . $httpPort;
    }

    protected function detectLocalHttpPort(array $config, $homeHost)
    {
        $candidatePorts = [];

        foreach ((array) ($config['services'] ?? []) as $serviceConfig) {
            foreach ((array) ($serviceConfig['ports'] ?? []) as $ports) {
                foreach ((array) $ports as $port) {
                    $port = (int) $port;

                    if ($port > 0) {
                        $candidatePorts[] = $port;
                    }
                }
            }
        }

        $candidatePorts = array_values(array_unique($candidatePorts));
        $preferredPorts = $this->detectPreferredHttpPorts($config);
        $remainingPorts = array_values(array_diff($candidatePorts, $preferredPorts));

        foreach ($preferredPorts as $port) {
            if ($this->portLooksLikeWordPress($homeHost, $port)) {
                return $port;
            }
        }

        foreach ($remainingPorts as $port) {
            if ($this->portLooksLikeWordPress($homeHost, $port)) {
                return $port;
            }
        }

        foreach ($preferredPorts as $port) {
            if ($this->portSpeaksHttp($homeHost, $port)) {
                return $port;
            }
        }

        foreach ($remainingPorts as $port) {
            if ($this->portSpeaksHttp($homeHost, $port)) {
                return $port;
            }
        }

        return isset($config['services']['nginx']['ports']['HTTP'][0]) ? (int) $config['services']['nginx']['ports']['HTTP'][0] : 0;
    }

    protected function detectPreferredHttpPorts(array $config)
    {
        $preferredPorts = [];

        foreach ((array) ($config['services'] ?? []) as $serviceName => $serviceConfig) {
            $role = strtolower((string) ($serviceConfig['role'] ?? ''));
            $name = strtolower((string) ($serviceConfig['name'] ?? $serviceName));
            $ports = (array) ($serviceConfig['ports'] ?? []);
            $looksLikeHttpService = $role === 'http'
                || $name === 'nginx'
                || array_key_exists('HTTP', $ports);

            if (!$looksLikeHttpService) {
                continue;
            }

            foreach ($ports as $servicePorts) {
                foreach ((array) $servicePorts as $port) {
                    $port = (int) $port;

                    if ($port > 0) {
                        $preferredPorts[] = $port;
                    }
                }
            }
        }

        return array_values(array_unique($preferredPorts));
    }

    protected function getLocalSiteConfigPath()
    {
        $configuredPath = (string) getenv('MANUSCRIPT_LOCAL_SITE_JSON');

        if ($configuredPath !== '') {
            return $configuredPath;
        }

        if (defined('ABSPATH')) {
            return dirname(dirname($this->untrailingslashit(ABSPATH))) . '/local-site.json';
        }

        return '';
    }

    protected function shouldRewriteAbsolutePdfUrl($pdfUrl)
    {
        $homeUrl = '';

        if (function_exists(__NAMESPACE__ . '\\home_url')) {
            $homeUrl = (string) home_url('/');
        } elseif (function_exists('home_url')) {
            $homeUrl = (string) home_url('/');
        }

        if ($homeUrl === '') {
            return false;
        }

        $pdfParts = $this->parseUrl($pdfUrl);
        $homeParts = $this->parseUrl($homeUrl);

        if (empty($pdfParts['host']) || empty($homeParts['host'])) {
            return false;
        }

        return strtolower($pdfParts['host']) === strtolower($homeParts['host']);
    }

    protected function extractUrlPathAndQuery($pdfUrl)
    {
        $parts = $this->parseUrl($pdfUrl);
        $path = !empty($parts['path']) ? $parts['path'] : '';
        $query = !empty($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = !empty($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }

    protected function parseUrl($url)
    {
        if (function_exists('wp_parse_url')) {
            return (array) wp_parse_url($url);
        }

        return (array) parse_url($url);
    }

    protected function untrailingslashit($value)
    {
        if (function_exists('untrailingslashit')) {
            return untrailingslashit($value);
        }

        return rtrim((string) $value, '/\\');
    }

    protected function portSpeaksHttp($homeHost, $port)
    {
        return $this->probeHttpStatus($homeHost, $port, '/') > 0;
    }

    protected function portLooksLikeWordPress($homeHost, $port)
    {
        $status = $this->probeHttpStatus($homeHost, $port, '/wp-login.php');

        return in_array($status, [200, 301, 302, 403, 405], true);
    }

    protected function probeHttpStatus($homeHost, $port, $path)
    {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . (int) $port, $errno, $errstr, 0.5);

        if (!$socket) {
            return 0;
        }

        stream_set_timeout($socket, 1);
        fwrite($socket, "HEAD {$path} HTTP/1.0\r\nHost: {$homeHost}\r\nConnection: close\r\n\r\n");
        $response = fgets($socket, 64);
        fclose($socket);

        if (!is_string($response) || strpos($response, 'HTTP/') !== 0) {
            return 0;
        }

        if (!preg_match('~^HTTP/\d+(?:\.\d+)?\s+(\d{3})~', $response, $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }
}
