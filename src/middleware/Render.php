<?php

namespace Dragon\middleware;

/**
 * Class Render
 * Renders the view after the controller action
 * If you are planning to use any 3rd party template engine, you can create your own middleware
 * @package Dragon\middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class Render implements IMiddleware
{
    public function handle(
        \Dragon\http\Request $request,
        \Dragon\http\Response $response,
        callable $next,
    ): \Dragon\http\Response {
        $this->trySetView();

        $response = $next();

        $content = \Dragon\View::gi()->render();
        $pos = strrpos($content, '</body>');
        if (IS_WORKSPACE && $pos !== false) {
            $content = substr_replace($content, \Dragon\Debug::onsite(), $pos, 0);
        }
        return $response->html($content);
    }

    /**
     * Try to set view file by possible paths
     */
    private function trySetView(): void
    {
        $controller = array_filter(explode('/', str_replace('\\', '/', \Dragon\Application::$controller::class)));
        array_shift($controller);

        $possibleViewFile = [
            implode('/', $controller) . '/' . \Dragon\Application::$method,
            strtolower(implode('/', $controller)) . '/' . \Dragon\Application::$method,
            strtolower(implode('/', $controller) . '/' . \Dragon\Application::$method),
        ];

        $snake_case = [];
        foreach ($controller as $part) {
            $snake_case[] = \Dragon\helpers\Utils::snake_case($part);
        }
        $possibleViewFile[] = implode('/', $snake_case) . '/' . \Dragon\Application::$method;
        $possibleViewFile[] = implode('/', $snake_case) . '/' . strtolower(\Dragon\Application::$method);
        $possibleViewFile[] =
            implode('/', $snake_case) . '/' . \Dragon\helpers\Utils::snake_case(\Dragon\Application::$method);

        foreach ($possibleViewFile as $viewFile) {
            if (\Dragon\View::gi()->view($viewFile)) {
                break;
            }
        }
    }
}
