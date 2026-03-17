<?php

namespace middleware;

/**
 * Class Session
 * Starts the session at the beginning of the request
 * @package middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
class Session implements IMiddleware
{

    public function handle(\http\Request $request, \http\Response $response, callable $next): \http\Response
    {
        \helpers\Session::start();
        return $next();
    }
    
}
