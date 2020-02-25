<?php

namespace RoadRunnerUbiquity;

use Spiral\RoadRunner\HttpClient;
use Spiral\RoadRunner\Worker;
use Spiral\Goridge\StreamRelay;

/**
 * Class Request
 * @package RoadRunnerUbiquity
 */
class Request
{
    /**
     * Soft memory limit set to 100 Mb
     */
    const MEMORY_SOFT_LIMIT = 100 * 1024 * 1024;

    private $httpClient;
    private $originalServer = [];
    private $rawRequest = [];

    /**
     * Request constructor.
     *      Opens stream relay with RoadRunner
     *      Stores original $_SERVER variable
     */
    public function __construct()
    {
        // If STDIN or STDOUT are not defined, let's open it
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
     * @return boolean
     */
    public function acceptRequest(): bool
    {
        if (null === $this->rawRequest = $this->httpClient->acceptRequest()) {
            return false;
        }

        // Prepare all superglobals
        $_SERVER = $this->prepareServer();
        $_GET = $this->prepareGet();
        $_POST = $this->preparePost();
        $_COOKIE = $this->prepareCookie();
        $_FILES = $this->prepareFiles();
        $_REQUEST = $this->prepareRequest();

        // Start output buffering
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
            header_remove($key);
        }

        $this->httpClient->respond($status, $body, $headers);

        // Finish output buffering and clean the buffer
        ob_end_clean();

        return $this;
    }

    /**
     * Ubiquity - specific routing
     *
     * @return string
     */
    public function ubiquityRoute()
    {
        $uri = \ltrim(\urldecode(\parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');

        return $_GET['c'] = ($uri !== 'favicon.ico' && ($uri == null || !\file_exists(__DIR__ . '/' . $uri))) ? $uri : '';
    }

    /**
     * Force garbage collection if memory usage exceeds soft memory limit
     * Should be used under heavy load
     *
     * @param float|int $softLimit
     */
    public function garbageCollect($softLimit = self::MEMORY_SOFT_LIMIT)
    {
        if ($softLimit <= memory_get_usage()) {
            gc_collect_cycles();
        };
    }

    /**
     * @return Worker
     */
    public function getWorker(): Worker
    {
        return $this->httpClient->getWorker();
    }

    /**
     * Returns altered copy of _SERVER variable. Sets ip-address,
     * request-time and other values.
     *
     * @return array
     */
    protected function prepareServer(): array
    {
        $ctx = $this->rawRequest['ctx'];

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

    /**
     * Returns parsed query string.
     *
     * @return array
     */
    protected function prepareGet(): array
    {
        parse_str($_SERVER['QUERY_STRING'], $get);

        return $get;
    }

    /**
     * Returns decoded request body.
     *
     * @return array
     */
    protected function preparePost(): array
    {
        $post = $_POST ?? [];

        if (
            isset($_SERVER['CONTENT_TYPE']) && (
                false !== strpos($_SERVER['CONTENT_TYPE'], 'application/json') ||
                false !== strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') ||
                false !== strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data')
            )
        ) {
            $post = array_merge(
                $post,
                json_decode($this->rawRequest['body'], true)
            );
        }

        return $post;
    }

    /**
     * Returns cookies.
     *
     * @return array
     */
    protected function prepareCookie(): array
    {
        return $this->rawRequest['ctx']['cookies'] ?? [];
    }

    /**
     * Returns uploaded files array.
     *
     * @return array
     */
    protected function prepareFiles(): array
    {
        $ctx = $this->rawRequest['ctx'];

        if (!isset($ctx['uploads'])) {
            return [];
        }

        foreach ($ctx['uploads'] as &$upload) {
            $upload['type'] = $upload['mime'] ?? null;
        }

        return $ctx['uploads'];
    }

    /**
     * Returns request as a merge of respective arrays.
     *
     * @return array
     */
    protected function prepareRequest(): array
    {
        return array_merge(
            $_GET,
            $_POST,
            $_COOKIE
        );
    }
}