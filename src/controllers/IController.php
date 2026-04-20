<?php

namespace Dragon\controllers;

use Dragon\middleware\IMiddleware;

/**
 * Interface IController
 *
 * @package Dragon\controllers
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
interface IController
{
    /**
     * Return the middleware stack to be executed around the controller method
     *
     * @return IMiddleware[]
     */
    public function middleware(): array;
}
