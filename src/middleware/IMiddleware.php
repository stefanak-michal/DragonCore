<?php

namespace Dragon\middleware;

/**
 * Interface IMiddleware
 *
 * @package Dragon\middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
interface IMiddleware
{
    /**
     * Handle the request, optionally passing control to the next middleware or action
     *
     * @param \Dragon\http\Request $request
     * @param \Dragon\http\Response $response
     * @param callable $next
     * @return \Dragon\http\Response
     */
    public function handle(
        \Dragon\http\Request $request,
        \Dragon\http\Response $response,
        callable $next,
    ): \Dragon\http\Response;
}
