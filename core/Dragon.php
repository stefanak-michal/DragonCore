<?php

namespace core;

/**
 * Framework
 * Base class of MVC framework
 *
 * @package core
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
final class Dragon
{

    /**
     * Called controller
     *
     * @static
     * @var \controllers\IController
     */
    public static $controller;

    /**
     * Called method
     *
     * @static
     * @var string
     */
    public static $method;

    /**
     * Run a project
     */
    public function run()
    {
        $cmv = Router::gi()->resolve();

        //must be defined before view->render, sorry for hardcode
        if (DRAGON_DEBUG) {
            header('X-Dragon-Debug: ' . Router::gi()->getHost() . 'tmp/debug/last.html');
        }

        //finally we have something to show
        $this->loadController($cmv);

        $request = new \http\Request($cmv['vars']);

        $middlewares = method_exists(self::$controller, 'middleware')
            ? self::$controller->middleware()
            : [];

        $action = function () use ($request) {
            if (method_exists(self::$controller, self::$method)) {
                Debug::timer('Controller logic');
                self::$controller->{self::$method}($request);
                Debug::timer('Controller logic');
            }
        };

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function (callable $carry, \middleware\IMiddleware $middleware) use ($request) {
                return function () use ($middleware, $carry, $request) {
                    $middleware->handle($request, $carry);
                };
            },
            $action
        );

        $pipeline();
    }

    /**
     * @param array $cmv [controller, method, vars]
     */
    private function loadController(array $cmv)
    {
        //if we have nothing to do, then quit
        if (empty($cmv) or empty($cmv['controller']) or empty($cmv['method']))
            trigger_error('Unresolved controller->method action', E_USER_ERROR);

        $this->trySetView($cmv);

        self::$method = $cmv['method'];

        $last = ucfirst(array_pop($cmv['controller']));
        $className = "\\" . implode("\\", $cmv['controller']) . "\\" . $last;
        if (!class_exists($className))
            trigger_error('Missing class ' . $className, E_USER_ERROR);

        self::$controller = new $className();
    }

    /**
     * Try to set view file by possible paths
     * @param array $cmv
     */
    private function trySetView(array $cmv)
    {
        array_shift($cmv['controller']);
        
        $possibleViewFile = [
            implode('/', $cmv['controller']) . '/' . $cmv['method'],
            strtolower(implode('/', $cmv['controller'])) . '/' . $cmv['method'],
            strtolower(implode('/', $cmv['controller']) . '/' . $cmv['method']),
        ];

        $snake_case = [];
        foreach ($cmv['controller'] as $part)
            $snake_case[] = \helpers\Utils::snake_case($part);
        $possibleViewFile[] = implode('/', $snake_case) . '/' . $cmv['method'];
        $possibleViewFile[] = implode('/', $snake_case) . '/' . strtolower($cmv['method']);
        $possibleViewFile[] = implode('/', $snake_case) . '/' . \helpers\Utils::snake_case($cmv['method']);

        foreach ($possibleViewFile as $viewFile) {
            if (View::gi()->view($viewFile))
                break;
        }
    }

    public function __destruct()
    {
        if (DRAGON_DEBUG) {
            Debug::generate();
        }
    }

}
