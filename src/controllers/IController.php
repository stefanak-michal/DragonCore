<?php

namespace Dragon\controllers;

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
     * @return \middleware\IMiddleware[]
     */
    public function middleware(): array;
}
