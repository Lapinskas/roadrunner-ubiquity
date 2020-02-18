<?php

namespace RoadRunnerUbiquity;

use Spiral\RoadRunner\HttpClient;
use Spiral\RoadRunner\Worker;
use Spiral\Goridge\StreamRelay;

class Request
{
    // Soft limit 100 Mb
    const MEMORY_SOFT_LIMIT = 100 * 1024 * 1024;

    /** @var HttpClient */
    private $httpClient;

    private $originalServer = [];

    public function __construct()
    {
        $this->httpClient = new HttpClient(
            new Worker(
                new StreamRelay(
                    defined('STDIN') ? STDIN : fopen("php://stdin", "r"),
                    defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w')
                )
            )
        );

        $this->originalServer = $_SERVER;
    }

    /**
     * @return Worker
     */
    public function getWorker(): Worker
    {
        return $this->httpClient->getWorker();
    }

    /**
     * @return boolean|null
     */
    public function acceptRequest()
    {
        $rawRequest = $this->httpClient->acceptRequest();
        if ($rawRequest === null) {
            return null;
        }

        // Prepare all superglobals
        $_SERVER = $this->configureServer($rawRequest['ctx']);
        parse_str($_SERVER['QUERY_STRING'], $_GET);
        $_POST = $this->decodePost($rawRequest);
        $_COOKIE = $rawRequest['ctx']['cookies'] ?? [];

        ob_start();
        return true;
    }

    /**
     * Send response to the application server.
     *
     * @param int $status Http status code
     * @param string $body Body of response
     * @param string[][] $headers An associative array of the message's headers. Each
     *                            key MUST be a header name, and each value MUST be an array of strings
     *                            for that header.
     */
    public function sendResponse(int $status = 200, string $body = null, array $headers = [])
    {
        if (null === $body) {
            $body = ob_get_contents();
        }

        foreach (headers_list() as $header) {
            list($key, $value) = explode(':', $header);
            $headers[$key] = [$value];
        }

        $this->httpClient->respond($status, $body, $headers);

        ob_end_clean();

        return $this;
    }

    public function ubiquityRoute()
    {
        $uri = \ltrim(\urldecode(\parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');

        return $_GET['c'] = ($uri !== 'favicon.ico' && ($uri == null || !\file_exists(__DIR__ . '/' . $uri))) ? $uri : '';
    }

    public function garbageCollect($softLimit = self::MEMORY_SOFT_LIMIT)
    {
        if ($softLimit <= memory_get_usage()) {
            gc_collect_cycles();
        };
    }

    /**
     * Returns altered copy of _SERVER variable. Sets ip-address,
     * request-time and other values.
     *
     * @param array $ctx
     * @return array
     */
    protected function configureServer(array $ctx): array
    {
        $server = $this->originalServer;

        $server['REQUEST_TIME'] = time();
        $server['REQUEST_TIME_FLOAT'] = microtime(true);
        $server['REMOTE_ADDR'] = $ctx['attributes']['ipAddress'] ?? $ctx['remoteAddr'] ?? '127.0.0.1';

        $server['HTTP_USER_AGENT'] = '';
        foreach ($ctx['headers'] as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if (\in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $server[$key] = implode(', ', $value);
            } else {
                $server['HTTP_' . $key] = implode(', ', $value);
            }
        }

        $server['REQUEST_METHOD'] = $ctx['method'] ?? null;

        if (false !== $parts = parse_url($ctx['uri'])) {
            $server['QUERY_STRING'] = $parts['query'] ?? null;
            $server['REQUEST_URI'] = $parts['path'] ?? null;
            $server['SERVER_PORT'] = $parts['port'] ?? null;
        };

        return $server;
    }

    protected function decodePost(array $rawRequest): array
    {
        $post = $_POST ?? [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (false !== strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded')) {
                $post = array_merge(
                    $post,
                    (array) json_decode($rawRequest['body'])
                );
            }
        }

        return $post;
    }
}