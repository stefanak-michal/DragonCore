<?php

namespace Dragon\middleware;

use Dragon\http\Request;
use Dragon\http\Response;
use Dragon\View;

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
    public function handle(Request $request, Response $response, callable $next,): Response {
        if (empty(View::gi()->getView())) {
            $this->trySetView();
        }

        $response = $next($response);

        $code = $response->getStatus();
        if (DRAGON_DEBUG && $code >= 300 && $code < 400) {
            $uri = $response->getHeaders()['Location'] ?? '';
            $response
                ->status(203)
                ->header('Location', '')
                ->html(new View('/views/elements/debug/backtrace', [
                    'bt' => debug_backtrace(),
                    'url' => $uri,
                    'code' => $code,
                    ])->render());
            return $response;
        }

        $content = View::gi()->render();
        $pos = strrpos($content, '</body>');
        if (DRAGON_DEBUG && $pos !== false) {
            $content = substr_replace($content, \Dragon\Debug::onsite(), $pos, 0);
        }
        return $response->html($content);
    }

    /**
     * Try to set view file by possible paths
     */
    private function trySetView(): void
    {
        if (!isset(\Dragon\Application::$controller, \Dragon\Application::$method)) {
            return;
        }

        $controller = ltrim(str_replace('\\', '/', \Dragon\Application::$controller::class), '/');

        $possibleViewFile = [
            $controller . '/' . \Dragon\Application::$method,
            strtolower($controller) . '/' . \Dragon\Application::$method,
            strtolower($controller . '/' . \Dragon\Application::$method),
        ];

        $snake_case = [];
        foreach (explode('/', $controller) as $part) {
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
