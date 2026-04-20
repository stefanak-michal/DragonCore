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
     * @param string $allowedOrigin
     * @param string[] $allowedMethods
     * @param string[] $allowedHeaders
     * @param int $maxAge  Preflight cache duration in seconds
     */
    public function __construct(
        private string $allowedOrigin = '*',
        private array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        private array $allowedHeaders = ['Content-Type', 'Authorization'],
        private int $maxAge = 86400,
    ) {}

    public function handle(
        \Dragon\http\Request $request,
        \Dragon\http\Response $response,
        callable $next,
    ): \Dragon\http\Response {
        $response
            ->header('Access-Control-Allow-Origin', $this->allowedOrigin)
            ->header('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods))
            ->header('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders))
            ->header('Access-Control-Max-Age', (string) $this->maxAge);

        if ($request->method === \Dragon\http\RequestMethod::OPTIONS) {
            return $response->status(204);
        }

        return $next($response);
    }
}
