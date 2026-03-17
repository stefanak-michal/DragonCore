<?php

namespace middleware;

/**
 * Class Render
 * Renders the view after the controller action
 * @package middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
class Render implements IMiddleware
{

    public function handle(\http\Request $request, \http\Response $response, callable $next): \http\Response
    {
        $response = $next();

        $content = \core\View::gi()->render();
        $pos = strrpos($content, "</body>");
        if (IS_WORKSPACE && $pos !== false)
            $content = substr_replace($content, \core\Debug::onsite(), $pos, 0);
        return $response->html($content);
    }
    
}
