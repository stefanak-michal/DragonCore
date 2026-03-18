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

        if (DRAGON_DEBUG) {
            header('X-Dragon-Debug: ' . Router::gi()->getHost() . 'tmp/debug/last.html');
        }

        //finally we have something to show
        $this->loadController($cmv);

        $request = new \http\Request($cmv['vars']);
        $response = new \http\Response();

        $middlewares = method_exists(self::$controller, 'middleware') ? self::$controller->middleware() : [];

        $this->validateMiddlewareDependencies($middlewares);

        $action = function () use ($request, $response) {
            if (method_exists(self::$controller, self::$method)) {
                Debug::timer('Controller logic');
                $response = self::$controller->{self::$method}($request, $response);
                Debug::timer('Controller logic');
            }
            return $response;
        };

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function (callable $carry, \middleware\IMiddleware $middleware) use ($request, $response) {
                return function () use ($middleware, $carry, $request, $response) {
                    return $middleware->handle($request, $response, $carry);
                };
            },
            $action,
        );

        $response = $pipeline();
        $response->send();
    }

    /**
     * @param array $cmv [controller, method, vars]
     */
    private function loadController(array $cmv)
    {
        //if we have nothing to do, then quit
        if (empty($cmv) or empty($cmv['controller']) or empty($cmv['method']))
            throw new \RuntimeException('Route resolved to empty controller or method');

        self::$method = $cmv['method'];

        $last = ucfirst(array_pop($cmv['controller']));
        $className = "\\" . implode("\\", $cmv['controller']) . "\\" . $last;
        if (!class_exists($className))
            throw new \RuntimeException("Controller class $className not found");

        self::$controller = new $className();
    }

    /**
     * Validate that middleware dependencies declared via #[RequiresMiddleware]
     * are satisfied and ordered correctly in the stack.
     *
     * @param \middleware\IMiddleware[] $middlewares
     * @throws \RuntimeException
     */
    private function validateMiddlewareDependencies(array $middlewares): void
    {
        foreach ($middlewares as $index => $middleware) {
            $ref = new \ReflectionClass($middleware);
            $attributes = $ref->getAttributes(\middleware\RequiresMiddleware::class);

            foreach ($attributes as $attribute) {
                $required = $attribute->newInstance()->middleware;

                $found = false;
                for ($i = 0; $i < $index; $i++) {
                    if ($middlewares[$i] instanceof $required) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    throw new \RuntimeException(
                        get_class($middleware) . ' requires ' . $required . ' to be registered before it',
                    );
                }
            }
        }
    }

    public function __destruct()
    {
        if (DRAGON_DEBUG) {
            Debug::generate();
        }
    }
}
