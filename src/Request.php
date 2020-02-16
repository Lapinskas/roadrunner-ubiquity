<?php

namespace RoadRunnerUbiquity;

use Spiral\RoadRunner\HttpClient;
use Spiral\RoadRunner\Worker;

class Request
{
    /** @var HttpClient */
    private $httpClient;

    private $originalServer = [];

    /**
     * @param Worker $worker
     */
    public function __construct(Worker $worker) {
        $this->httpClient = new HttpClient($worker);
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
        $_COOKIE = $rawRequest['ctx']['cookies'] ?? [];

        ob_start();
        return true;
    }

    /**
     * Send response to the application server.
     *
     * @param int        $status  Http status code
     * @param string     $body    Body of response
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
            list($key, $value) = explode(':',$header);
            $headers[$key] = [$value];
        }

        $this->httpClient->respond($status, $body, $headers);
        ob_end_clean();
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
        };

        return $server;
    }
}