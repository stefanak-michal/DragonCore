<?php

namespace Tests\support\controllers;

use Dragon\controllers\IController;
use Dragon\http\Request;
use Dragon\http\Response;
use Tests\support\middleware\Middleware1;

class ControllerWithMiddlewareMethod implements IController
{
    public function __construct()
    {
        echo 'constructor hit' . PHP_EOL;
    }

    public function middleware(): array
    {
        return [
            new Middleware1(),
        ];
    }

    public function index(Request $request, Response $response): Response
    {
        echo 'method hit' . PHP_EOL;
        return $response;
    }
}
