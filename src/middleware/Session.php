<?php

namespace Dragon\middleware;

/**
 * Class Session
 * Starts the session at the beginning of the request
 * @package Dragon\middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class Session implements IMiddleware
{
    public function handle(
        \Dragon\http\Request $request,
        \Dragon\http\Response $response,
        callable $next,
    ): \Dragon\http\Response {
        \Dragon\helpers\Session::start();
        return $next();
    }
}
