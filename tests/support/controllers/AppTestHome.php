<?php

namespace Tests\support\controllers;

use Dragon\controllers\IController;
use Dragon\http\Request;
use Dragon\http\Response;

class AppTestHome implements IController
{
    public function middleware(): array
    {
        return [];
    }

    public function index(Request $request, Response $response): Response
    {
        return $response->body('hello from app test');
    }
}
