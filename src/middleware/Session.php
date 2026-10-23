<?php

namespace Dragon\middleware;

use Attribute;

/**
 * Class Session
 * Starts the session at the beginning of the request
 * @package Dragon\middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Session implements IMiddleware
{
    public function handle(
        \Dragon\http\Request $request,
        \Dragon\http\Response $response,
        callable $next,
    ): \Dragon\http\Response {
        \Dragon\helpers\Session::start();
        return $next($response);
    }
}
