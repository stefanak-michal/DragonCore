<?php

namespace Dragon;

use Dragon\controllers\IController;
use Dragon\helpers\RouteTarget;
use Dragon\http\Request;
use Dragon\http\RequestMethod;
use Dragon\http\Response;
use Dragon\middleware\IMiddleware;
use Dragon\middleware\RequiresMiddleware;

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
     */
    public static IController $controller;

    /**
     * Name of called method
     */
    public static string $method;

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
    public function run(): void
    {
        $target = Router::gi()->resolve();

        if (DRAGON_DEBUG) {
            header('X-Dragon-Debug: ' . Router::gi()->getHost() . 'tmp/debug/last.html');
        }

        $request = new Request($target->vars);
        $response = new Response();
        $this->loadController($target);

        if (!empty($target->verbs) && !in_array($request->method, $target->verbs, true)) {
            $response
                ->status(405)
                ->header('Allow', implode(', ', array_map(fn (RequestMethod $verb) => $verb->value, $target->verbs)))
                ->send();
            return;
        }

        $middlewares = method_exists(self::$controller, 'middleware') ? self::$controller->middleware() : [];
        $this->validateMiddlewareDependencies($middlewares);

        $action = function (Response $response) use ($request) {
            if (method_exists(self::$controller, self::$method)) {
                Debug::timer('Controller');
                $response = self::$controller->{self::$method}($request, $response);
                Debug::timer('Controller');
            }
            return $response;
        };

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function (callable $carry, IMiddleware $middleware) use ($request) {
                return function (Response $response) use ($middleware, $carry, $request) {
                    return $middleware->handle($request, $response, $carry);
                };
            },
            $action,
        );

        $response = $pipeline($response);
        $response->send();
    }

    private function loadController(RouteTarget $target): void
    {
        if (empty($target->controller) or empty($target->method)) {
            throw new \RuntimeException('Route resolved to empty controller or method');
        }

        if (!class_exists($target->controller)) {
            throw new \RuntimeException("Controller class {$target->controller} not found");
        }

        self::$method = $target->method;
        self::$controller = new $target->controller();

        if (!method_exists(self::$controller, $target->method)) {
            throw new \RuntimeException("Method {$target->method} not found in controller {$target->controller}");
        }
    }

    /**
     * Validate that middleware dependencies declared via #[RequiresMiddleware]
     * are satisfied and ordered correctly in the stack.
     *
     * @param IMiddleware[] $middlewares
     * @throws \RuntimeException
     */
    private function validateMiddlewareDependencies(array $middlewares): void
    {
        foreach ($middlewares as $index => $middleware) {
            $ref = new \ReflectionClass($middleware);
            $attributes = $ref->getAttributes(RequiresMiddleware::class);

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
