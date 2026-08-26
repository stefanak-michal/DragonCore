<?php

namespace Dragon\controllers;

use Dragon\http\Request;
use Dragon\http\Response;

class Missing implements IController
{

    /**
     * @inheritDoc
     */
    public function middleware(): array
    {
        return [];
    }

    public function index(Request $request, Response $response): Response
    {
        return $response->status(404)->body('This is not the page you are looking for.');
    }
}
