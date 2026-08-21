<?php

namespace Dragon\middleware;

/**
 * Class Cors
 * Handles Cross-Origin Resource Sharing headers.
 * Short-circuits preflight OPTIONS requests with a 204 response.
 *
 * @package Dragon\middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
readonly class Cors implements IMiddleware
{
    /**
     * @param string|string[] $allowedOrigin
     * @param string[] $allowedMethods
     * @param string[] $allowedHeaders
     * @param int $maxAge  Preflight cache duration in seconds
     * @param bool $allowCredentials
     */
    public function __construct(
        private string|array $allowedOrigin = '*',
        private array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        private array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'],
        private int $maxAge = 86400,
        private bool $allowCredentials = false
    ) {}

    public function handle(
        \Dragon\http\Request $request,
        \Dragon\http\Response $response,
        callable $next,
    ): \Dragon\http\Response {
        $origin = $this->resolveOrigin($request);

        $response
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods))
            ->header('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders))
            ->header('Access-Control-Max-Age', (string) $this->maxAge);

        if ($origin !== '*') {
            $response->header('Vary', 'Origin');

            if ($this->allowCredentials) {
                $response->header('Access-Control-Allow-Credentials', 'true');
            }
        }

        if ($request->method === \Dragon\http\RequestMethod::OPTIONS) {
            return $response->status(204);
        }

        return $next($response);
    }

    private function resolveOrigin(\Dragon\http\Request $request): string
    {
        if ($this->allowedOrigin === '*') {
            return $this->allowedOrigin;
        }

        $origin = $request->header('Origin');
        if (is_array($this->allowedOrigin) && in_array($origin, $this->allowedOrigin, true)) {
            return $origin;
        }

        if (is_string($this->allowedOrigin) && $this->allowedOrigin === $origin) {
            return $origin;
        }

        return '';
    }
}
