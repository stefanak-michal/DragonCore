<?php

namespace Tests\support\controllers;

use Dragon\http\Request;
use Dragon\http\Response;
use Dragon\controllers\IController;

class HomeCtrl implements IController
{
    public function myAction(Request $request, Response $response): Response
    {
        return $response;
    }
}
