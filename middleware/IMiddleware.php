<?php

namespace middleware;

/**
 * Interface IMiddleware
 *
 * @package middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
interface IMiddleware
{
    /**
     * Handle the request, optionally passing control to the next middleware or action
     *
     * @param \http\Request $request
     * @param \http\Response $response
     * @param callable $next
     * @return \http\Response
     */
    public function handle(\http\Request $request, \http\Response $response, callable $next): \http\Response;
}
