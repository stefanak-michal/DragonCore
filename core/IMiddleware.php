<?php

namespace core;

/**
 * Interface IMiddleware
 *
 * @package core
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
interface IMiddleware
{
    /**
     * Handle the request, optionally passing control to the next middleware or action
     *
     * @param callable $next
     * @return void
     */
    public function handle(callable $next): void;
}
