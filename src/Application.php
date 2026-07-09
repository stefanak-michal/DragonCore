<?php

namespace Dragon;

/**
 * Application
 * Base class of framework
 *
 * @package Dragon
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
final class Application
{
    /**
     * Instance of called controller
     *
     * @static
     * @var \Dragon\controllers\IController
     */
    public static $controller;

    /**
     * Name of called method
     *
     * @static
     * @var string
     */
    public static $method;

    public function __construct()
    {
        Debug::timer('Application');

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        if (!defined('APP_PATH')) {
            define('APP_PATH', dirname(get_included_files()[0]));
        }

        if (!defined('CORE_PATH')) {
            define('CORE_PATH', __DIR__);
        }

        if (!defined('IS_CLI')) {
            define('IS_CLI', php_sapi_name() == 'cli');
        }
        if (IS_CLI) {
            set_time_limit(0);
        }

        if (!defined('DRAGON_DEBUG')) {
            define('DRAGON_DEBUG', (bool)Config::gi()->get('debug'));
        }
    }

    /**
     * Run a project
     */
    public function run()
    {
        $cmv = Router::gi()->resolve();

        if (DRAGON_DEBUG) {
            header('X-Dragon-Debug: ' . Router::gi()->getHost() . 'tmp/debug/last.html');
        }

        $this->loadController($cmv);

        $request = new \Dragon\http\Request($cmv['vars']);
        $response = new \Dragon\http\Response();

        $middlewares = method_exists(self::$controller, 'middleware') ? self::$controller->middleware() : [];

        $this->validateMiddlewareDependencies($middlewares);

        $action = function (\Dragon\http\Response $response) use ($request) {
            if (method_exists(self::$controller, self::$method)) {
                Debug::timer('Controller');
                $response = self::$controller->{self::$method}($request, $response);
                Debug::timer('Controller');
            }
            return $response;
        };

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function (callable $carry, \Dragon\middleware\IMiddleware $middleware) use ($request) {
                return function (\Dragon\http\Response $response) use ($middleware, $carry, $request) {
                    return $middleware->handle($request, $response, $carry);
                };
            },
            $action,
        );

        $response = $pipeline($response);
        $response->send();
    }

    /**
     * @param array $cmv [controller, method, vars]
     */
    private function loadController(array $cmv)
    {
        if (empty($cmv) or empty($cmv['controller']) or empty($cmv['method'])) {
            throw new \RuntimeException('Route resolved to empty controller or method');
        }

        if (!class_exists($cmv['controller'])) {
            throw new \RuntimeException("Controller class {$cmv['controller']} not found");
        }

        self::$method = $cmv['method'];
        self::$controller = new $cmv['controller']();

        if (!method_exists(self::$controller, $cmv['method'])) {
            throw new \RuntimeException("Method {$cmv['method']} not found in controller {$cmv['controller']}");
        }
    }

    /**
     * Validate that middleware dependencies declared via #[RequiresMiddleware]
     * are satisfied and ordered correctly in the stack.
     *
     * @param \Dragon\middleware\IMiddleware[] $middlewares
     * @throws \RuntimeException
     */
    private function validateMiddlewareDependencies(array $middlewares): void
    {
        foreach ($middlewares as $index => $middleware) {
            $ref = new \ReflectionClass($middleware);
            $attributes = $ref->getAttributes(\Dragon\middleware\RequiresMiddleware::class);

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
