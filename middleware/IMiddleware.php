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
     * @param callable $next
     * @return void
     */
    public function handle(\http\Request $request, callable $next): void;
}
