<?php

namespace controllers;

/**
 * Interface IController
 *
 * @package controllers
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
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
