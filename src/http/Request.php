<?php

namespace Dragon\http;

/**
 * Request
 * Encapsulates the current HTTP request (GET, POST, FILES, cookies, headers, body, etc.)
 * An instance is created once per request and passed through the middleware pipeline
 * and into the controller method as the first argument.
 *
 * @package Dragon\http
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class Request
{
    /**
     * Query string parameters ($_GET)
     *
     * @var array
     */
    public array $get;

    /**
     * Form / body parameters ($_POST)
     *
     * @var array
     */
    public array $post;

    /**
     * Uploaded files ($_FILES)
     *
     * @var array
     */
    public array $files;

    /**
     * Cookie values ($_COOKIE)
     *
     * @var array
     */
    public array $cookies;

    /**
     * Incoming request headers, normalised to Title-Case keys
     *
     * @var array
     */
    public array $headers;

    /**
     * Server / environment variables ($_SERVER)
     *
     * @var array
     */
    public array $server;

    /**
     * HTTP request method (GET, POST, PUT, PATCH, DELETE, …)
     *
     * @var RequestMethod
     */
    public RequestMethod $method;

    /**
     * Raw request URI including query string (/path?foo=bar)
     *
     * @var string
     */
    public string $uri;

    /**
     * Raw request body (php://input).
     * Populated for POST/PUT/PATCH with non-form content types (e.g. application/json).
     *
     * @var string
     */
    public string $body;

    /**
     * Route parameters captured from the URI by the router (positional).
     * e.g. for route '/user/%i' matched against '/user/42', params = [42]
     *
     * @var array
     */
    public array $params;

    public function __construct(array $params = [])
    {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->files = $_FILES ?? [];
        $this->cookies = $_COOKIE ?? [];
        $this->server = $_SERVER ?? [];
        $this->headers = $this->parseHeaders();
        $this->method =
            RequestMethod::tryFrom(strtoupper($this->server['REQUEST_METHOD'] ?? 'GET')) ?? RequestMethod::GET;
        $this->uri = $this->server['REQUEST_URI'] ?? '/';
        $this->body = file_get_contents('php://input') ?: '';
        $this->params = $params;
    }

    /**
     * Parse incoming request headers in a server-agnostic way.
     * Prefers getallheaders() when available (Apache / php-fpm with FastCGI),
     * otherwise reconstructs from $_SERVER HTTP_* keys.
     *
     * @return array  Associative array with Title-Case header names as keys
     */
    private function parseHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $raw = getallheaders();
            // Normalise keys to Title-Case regardless of what the SAPI returns
            $headers = [];
            foreach ($raw as $name => $value) {
                $headers[$this->normaliseHeaderName($name)] = $value;
            }
            return $headers;
        }

        // Fallback: reconstruct from $_SERVER
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = substr($key, 5); // strip HTTP_ prefix
                $headers[$this->normaliseHeaderName($name)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headers[$this->normaliseHeaderName($key)] = $value;
            }
        }
        return $headers;
    }

    /**
     * Convert a raw header key (HTTP_ACCEPT_ENCODING, accept-encoding, …)
     * to Title-Case (Accept-Encoding).
     *
     * @param string $name
     * @return string
     */
    private function normaliseHeaderName(string $name): string
    {
        return ucwords(strtolower(str_replace('_', '-', $name)), '-');
    }

    /**
     * Retrieve a value from POST, falling back to GET, then to $default.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    /**
     * Decode the raw request body as JSON.
     * Returns the decoded value or null on failure / empty body.
     *
     * @param bool $assoc  When true, objects are returned as associative arrays
     * @return mixed
     */
    public function json(bool $assoc = true): mixed
    {
        if (empty($this->body) || !json_validate($this->body)) {
            return null;
        }
        return json_decode($this->body, $assoc);
    }

    /**
     * Get a single request header by name (case-insensitive).
     *
     * @param string $key  e.g. 'Content-Type', 'authorization'
     * @return string|null
     */
    public function header(string $key): ?string
    {
        $normalised = $this->normaliseHeaderName($key);
        return $this->headers[$normalised] ?? null;
    }

    /**
     * Determine whether the request is an XMLHttpRequest (AJAX).
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * Determine whether the request uses the given HTTP method.
     *
     * @param RequestMethod|string $method  e.g. RequestMethod::GET, 'GET', 'post'
     * @return bool
     */
    public function isMethod(RequestMethod|string $method): bool
    {
        if ($method instanceof RequestMethod) {
            return $this->method === $method;
        }
        return $this->method->value === strtoupper($method);
    }

    /**
     * Get the real client IP address.
     * Delegates to \Dragon\helpers\Utils::realIp() which checks proxy headers.
     *
     * @return string
     */
    public function ip(): string
    {
        return \Dragon\helpers\Utils::realIp();
    }
}
