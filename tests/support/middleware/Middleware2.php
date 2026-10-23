<?php

namespace Tests\support\middleware;

use Dragon\http\Request;
use Dragon\http\Response;
use Dragon\middleware\IMiddleware;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Middleware2 implements IMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        echo 'middleware2 hit' . PHP_EOL;
        return $next($response);
    }
}
