<?php

namespace middleware;

/**
 * Class Render
 * Renders the view after the controller action
 * If you are planning to use any 3rd party template engine, you can create your own middleware
 * @package middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
class Render implements IMiddleware
{
    public function handle(\http\Request $request, \http\Response $response, callable $next): \http\Response
    {
        $this->trySetView();

        $response = $next();

        $content = \core\View::gi()->render();
        $pos = strrpos($content, '</body>');
        if (IS_WORKSPACE && $pos !== false)
            $content = substr_replace($content, \core\Debug::onsite(), $pos, 0);
        return $response->html($content);
    }

    /**
     * Try to set view file by possible paths
     */
    private function trySetView(): void
    {
        $controller = array_filter(explode('/', str_replace('\\', '/', \core\Dragon::$controller::class)));
        array_shift($controller);

        $possibleViewFile = [
            implode('/', $controller) . '/' . \core\Dragon::$method,
            strtolower(implode('/', $controller)) . '/' . \core\Dragon::$method,
            strtolower(implode('/', $controller) . '/' . \core\Dragon::$method),
        ];

        $snake_case = [];
        foreach ($controller as $part)
            $snake_case[] = \helpers\Utils::snake_case($part);
        $possibleViewFile[] = implode('/', $snake_case) . '/' . \core\Dragon::$method;
        $possibleViewFile[] = implode('/', $snake_case) . '/' . strtolower(\core\Dragon::$method);
        $possibleViewFile[] = implode('/', $snake_case) . '/' . \helpers\Utils::snake_case(\core\Dragon::$method);

        foreach ($possibleViewFile as $viewFile) {
            if (\core\View::gi()->view($viewFile))
                break;
        }
    }
}
