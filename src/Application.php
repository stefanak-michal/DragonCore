<?php

namespace Dragon;

use Dragon\controllers\IController;
use Dragon\helpers\RouteTarget;
use Dragon\http\Request;
use Dragon\http\RequestMethod;
use Dragon\http\Response;
use Dragon\middleware\IMiddleware;
use Dragon\middleware\RequiresMiddleware;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

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
        $this->validateTarget($target);

        $request = new Request($target->vars);
        $response = new Response();

        if (DRAGON_DEBUG) {
            $response->header('X-Dragon-Debug', Router::gi()->getHost() . 'tmp/debug/last.html');
        }

        if (!empty($target->verbs) && !in_array($request->method, $target->verbs, true)) {
            $response
                ->status(405)
                ->header('Allow', implode(', ', array_map(fn(RequestMethod $verb) => $verb->value, $target->verbs)))
                ->send();
            return;
        }

        $refClass = new ReflectionClass($target->controller);
        $actions = $refClass->getAttributes(IMiddleware::class, ReflectionAttribute::IS_INSTANCEOF);
        $actions[] = '--controller--';
        $refMethod = new ReflectionMethod($target->controller, $target->method);
        $actions = array_merge($actions, $refMethod->getAttributes(IMiddleware::class, ReflectionAttribute::IS_INSTANCEOF));
        $actions[] = '--method--';
        $this->executePipeline($request, $response, array_unique($actions), $target);
    }

    private function validateTarget(RouteTarget $target): void
    {
        if (empty($target->controller) or empty($target->method)) {
            throw new RuntimeException('Route resolved to empty controller or method');
        }
        if (!class_exists($target->controller)) {
            throw new RuntimeException("Controller class {$target->controller} not found");
        }
        if (!method_exists($target->controller, $target->method)) {
            throw new RuntimeException("Controller method {$target->controller}->{$target->method} not found");
        }
    }

    /**
     * @param <ReflectionAttribute|IMiddleware|string>[] $actions
     */
    private function executePipeline(Request $request, Response $response, array $actions, RouteTarget $target): void
    {
        $executedMiddlewares = [];

        $executeNextInPipeline = function (Response $response) use (
            &$executeNextInPipeline,
            &$actions,
            $request,
            $target,
            &$executedMiddlewares
        ): Response {
            $nextAction = array_shift($actions);
            if ($nextAction === '--controller--') {
                self::$controller = new $target->controller();
                self::$method = $target->method;

                if (method_exists(self::$controller, 'middleware')) {
                    foreach (array_reverse(self::$controller->middleware()) as $middleware) {
                        if (
                            $middleware instanceof IMiddleware
                            && !in_array($middleware, $actions)
                            && !in_array($middleware::class, $executedMiddlewares)
                        ) {
                            array_unshift($actions, $middleware);
                        }
                    }
                }

                return $executeNextInPipeline($response);
            }
            if ($nextAction === '--method--') {
                return self::$controller->{self::$method}($request, $response);
            }
            if ($nextAction instanceof ReflectionAttribute) {
                $middleware = $nextAction->newInstance();
                if ($middleware instanceof IMiddleware) {
                    $this->validateMiddlewareDependencies($nextAction->getName(), $executedMiddlewares);
                    $executedMiddlewares[] = $nextAction->getName();
                    return $middleware->handle($request, $response, $executeNextInPipeline);
                }
            }
            if ($nextAction instanceof IMiddleware) {
                $this->validateMiddlewareDependencies($nextAction::class, $executedMiddlewares);
                $executedMiddlewares[] = $nextAction::class;
                return $nextAction->handle($request, $response, $executeNextInPipeline);
            }
            throw new RuntimeException("Action {$nextAction} not found");
        };

        $response = $executeNextInPipeline($response);
        $response->send();
    }

    private function validateMiddlewareDependencies(string $middleware, array $executedMiddlewares): void
    {
        $ref = new ReflectionClass($middleware);
        foreach ($ref->getAttributes(RequiresMiddleware::class) as $attr) {
            $required = $attr->newInstance()->middleware;
            if (!in_array($required, $executedMiddlewares, true)) {
                throw new RuntimeException($middleware . ' requires ' . $required . ' to be called before it');
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
