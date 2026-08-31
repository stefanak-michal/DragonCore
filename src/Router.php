<?php

namespace Dragon;

use Dragon\helpers\RouteTarget;

/**
 * Router
 * Work with URI
 *
 * @package Dragon
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
final class Router
{
    /**
     * Base for all URI
     */
    private string $project_host;

    /**
     * Definition of allowed routes from config file
     * @var RouteTarget[]
     */
    private array $routes = [];

    private static ?Router $instance = null;

    /**
     * Singleton
     */
    public static function gi(): Router
    {
        if (self::$instance === null) {
            self::$instance = new Router();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadRoutes();

        $this->project_host = Config::gi()->get('project_host');
        if (empty($this->project_host) && isset($_SERVER['SERVER_PORT'], $_SERVER['HTTP_HOST'])) {
            $this->project_host = ($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        }
        if (empty($this->project_host) && IS_CLI) {
            $this->project_host = 'http://' . php_uname('n');
        }
        if (empty($this->project_host)) {
            trigger_error('Not specified project host', E_USER_WARNING);
        }
        $this->project_host = rtrim($this->project_host, '/');
        Config::gi()->set('project_host', $this->project_host);
    }

    /**
     * Load routes from config file and clean up grouping
     */
    private function loadRoutes(): void
    {
        $this->routes = [];
        $this->loadRoutesRecursive(Config::gi()->get('routes', []), '');
    }

    /**
     * Recursively load routes while carrying URI prefix and last used controller grouping.
     *
     * @param array $routes
     * @param string $uriPrefix
     * @param string $lastController
     */
    private function loadRoutesRecursive(array $routes, string $uriPrefix, string $lastController = ''): void
    {
        foreach ($routes as $key => $value) {
            if (is_array($value)) {
                if (str_starts_with((string)$key, '/')) {
                    // uri prefix grouping
                    $this->loadRoutesRecursive($value, $uriPrefix . $key, $lastController);
                } elseif (is_string($key)) {
                    // controller grouping
                    $this->loadRoutesRecursive($value, $uriPrefix, trim(str_replace('\\', '/', $key), '/'));
                } else {
                    $this->loadRoutesRecursive($value, $uriPrefix, $lastController);
                }
            } elseif ($value instanceof RouteTarget) {
                if ($value->controller === null) {
                    $value->controller = $lastController;
                }
                $this->routes[$uriPrefix . $key] = $value;
            } else {
                $route = trim(str_replace('\\', '/', $value), '/');
                if (!empty($lastController)) {
                    $route = $lastController . '/' . $route;
                }
                $parts = array_filter(explode('/', $route));
                $method = array_pop($parts);
                $route = new RouteTarget(implode('/', $parts), $method, []);
                $this->routes[$uriPrefix . $key] = $route;
            }
        }
    }

    /**
     * Resolve the current request to a controller/method/vars array.
     */
    public function resolve(): RouteTarget
    {
        $path = str_replace(['//', '../'], '/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if (!empty($path)) {
            $found = $this->findRoute($path);
            if ($found !== null) {
                return $found;
            }
        }

        return new RouteTarget(
            Config::gi()->get('defaultController'),
            Config::gi()->get('defaultMethod'),
        );
    }

    /**
     * Get project host
     *
     * @return string
     */
    public function getHost(): string
    {
        return rtrim($this->project_host, '/') . '/';
    }

    /**
     * Switch to generate secured URI (https)
     *
     * @param bool $secure
     */
    public function setSecureHost(bool $secure = true): void
    {
        if ($secure) {
            if (!str_contains($this->project_host, 'https')) {
                $this->project_host = str_replace('http', 'https', $this->project_host);
            }
        } else {
            if (str_contains($this->project_host, 'https')) {
                $this->project_host = str_replace('https', 'http', $this->project_host);
            }
        }
    }

    /**
     * Generate homepage URI
     *
     * @param array $query
     * @return string
     */
    public function homepage(array $query = array()): string
    {
        $uri = $this->project_host;

        if (is_array($query) && !empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }

    /**
     * Generate URI
     *
     * @param string $controller Controller::class
     * @param string $method
     * @param array $vars
     * @param array $query
     * @return string
     * @throws \InvalidArgumentException
     */
    public function url(string $controller, string $method = 'index', array $vars = [], array $query = []): string
    {
        if (empty($controller) || empty($method)) {
            throw new \InvalidArgumentException('Missing required parameters.');
        }
        $controller = \Dragon\helpers\Utils::normalizeClassName($controller);

        $uri = '';
        foreach (array_filter(
                     $this->routes,
                     fn(RouteTarget $target) => is_string($target->controller) && $target->controller === $controller && $target->method === $method
                 ) as $mask => $_) {
            //check number of defined variables against mask
            if (count($vars) != preg_match_all('/%[dis]/', $mask)) {
                continue;
            }

            $this->replaceMaskVariables($mask, $vars);
            $uri = $this->project_host . $mask;
            break;
        }

        if (empty($uri)) {
            $uri = $this->project_host . trim(str_replace('\\', '/', $controller), '/') . '/' . $method;
            if (!empty($vars)) {
                $uri .= '/' . implode('/', array_map(function ($value) {
                        return filter_var($value, FILTER_SANITIZE_ENCODED);
                    }, $vars));
            }
        }

        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }

    private function replaceMaskVariables(string &$mask, array $vars): void
    {
        if (empty($vars)) {
            return;
        }

        $i = 0;
        while (preg_match('/%[dis]/', $mask, $match)) {
            switch ($match[0]) {
                case '%d':
                    $mask = preg_replace('/%d/', (string)floatval($vars[$i]), $mask, 1);
                    break;

                case '%i':
                    $mask = preg_replace('/%i/', (string)intval($vars[$i]), $mask, 1);
                    break;

                case '%s':
                    $mask = preg_replace('/%s/', filter_var($vars[$i], FILTER_SANITIZE_ENCODED), $mask, 1);
                    break;
            }

            $i++;
        }
    }

    /**
     * Get actual URI
     */
    public function current(bool $getParams = false): string
    {
        $uri = 'http';
        if ($_SERVER['SERVER_PORT'] != 80) {
            $uri .= 's';
        }

        $uri .= '://';
        $uri .= $_SERVER['SERVER_NAME'];

        if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
            $uri .= ':' . $_SERVER['SERVER_PORT'];
        }

        $uri .= $_SERVER['REQUEST_URI'];

        if (!$getParams and str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return $uri;
    }

    public function findRoute(string $path): ?RouteTarget
    {
        foreach ($this->routes as $mask => $route) {
            $output = $this->match($path, $mask, $route);
            if ($output !== null) {
                return $output;
            }
        }

        return null;
    }

    private function match(string $path, string $mask, RouteTarget $route): ?RouteTarget
    {
        // Capture token types in declaration order before replacing with regex patterns
        preg_match_all('/%[isd]/', $mask, $tokenMatches);
        $tokenTypes = $tokenMatches[0];

        $mask = str_replace(
            ['%i', '%s', '%d'],
            ['(-?\d+)', '(' . Config::gi()->get('routeStringRegex', '[\w\-]+') . ')', '(-?[\d\.]+)'],
            $mask,
        );

        $pattern = '/^';
        $pattern .= str_replace('/', '\/', $mask);
        $pattern .= str_ends_with($pattern, '\/') ? '?' : '\/?';
        $pattern .= '$/i';

        if (preg_match($pattern, $path, $vars)) {
            array_shift($vars);
            $vars = array_values($vars);

            foreach ($vars as $i => $var) {
                $vars[$i] = match ($tokenTypes[$i] ?? '%s') {
                    '%i' => (int)$var,
                    '%d' => (float)$var,
                    default => $var,
                };
            }

            $route->vars = $vars;
            return $route;
        }

        return null;
    }
}
