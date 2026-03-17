<?php

namespace http;

/**
 * Response
 * Encapsulates the outgoing HTTP response (status code, headers, body).
 * Created once per request by Dragon and passed through the middleware pipeline
 * alongside the Request.
 *
 * @package http
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
class Response
{

    /**
     * HTTP status code
     *
     * @var int
     */
    private int $status = 200;

    /**
     * Response headers as name => value pairs
     *
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * Response body
     *
     * @var string
     */
    private string $body = '';

    /**
     * Set the HTTP status code.
     *
     * @param int $code
     * @return static
     */
    public function status(int $code): static
    {
        $this->status = $code;
        return $this;
    }

    /**
     * Set a response header.
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set the raw response body.
     *
     * @param string $content
     * @return static
     */
    public function body(string $content): static
    {
        $this->body = $content;
        return $this;
    }

    /**
     * Set the response body as HTML content.
     * Sets Content-Type to text/html.
     *
     * @param string $content
     * @return static
     */
    public function html(string $content): static
    {
        $this->headers['Content-Type'] = 'text/html';
        $this->body = $content;
        return $this;
    }

    /**
     * Set the response body as JSON.
     * Sets Content-Type to application/json.
     *
     * @param mixed $data
     * @param int $flags  JSON encoding flags
     * @return static
     */
    public function json(mixed $data, int $flags = 0): static
    {
        $this->headers['Content-Type'] = 'application/json';
        $this->body = json_encode($data, $flags);
        return $this;
    }

    /**
     * Send a redirect response and terminate execution.
     * In debug mode, renders a backtrace view instead of redirecting.
     *
     * @param string $uri
     * @param int $code
     * @return never
     */
    public function redirect(string $uri, int $code = 302): never
    {
        if (DRAGON_DEBUG) {
            header('Content-Type: text/html');
            echo (new \core\View('/views/elements/debug/backtrace', [
                'bt' => debug_backtrace(),
                'url' => $uri,
                'code' => $code,
            ]))->render();
            exit;
        }

        http_response_code($code);
        header('Location: ' . $uri);
        exit;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get the response body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get all response headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Emit the response: status code, headers, and body.
     * Does not call exit().
     *
     * @return void
     */
    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }

}
