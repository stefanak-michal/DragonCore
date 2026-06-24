<?php

namespace Dragon;

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
     *
     * @var string
     */
    private $project_host;

    /**
     * Definition of allowed routes from config file
     *
     * @var array
     */
    private $routes = [];

    /**
     * @var array
     */
    private $masksCache = [];

    /**
     * @var Router
     */
    private static $instance;

    /**
     * Singleton
     *
     * @return Router
     */
    public static function gi(): Router
    {
        if (self::$instance == null) {
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
        $this->project_host = rtrim($this->project_host, '/') . '/';
        Config::gi()->set('project_host', $this->project_host);
    }

    /**
     * Load routes from config file and clean up grouping
     */
    private function loadRoutes()
    {
        foreach (Config::gi()->get('routes', []) as $key => $value) {
            if (is_array($value)) {
                $controller = str_replace('\\', '/', $key);
                foreach ($value as $mask => $route) {
                    $this->routes[$mask] = $controller . '/' . $route;
                }
            } else {
                $this->routes[$key] = str_replace('\\', '/', $value);
            }
        }
    }

    /**
     * Resolve the current request to a controller/method/vars array.
     *
     * @return array
     */
    public function resolve(): array
    {
        $cmv = [
            'controller' => '',
            'method' => '',
            'vars' => [],
        ];

        $path = str_replace(['//', '../'], '/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if (!empty($path)) {
            $found = $this->findRoute($path);
            if (!empty($found)) {
                return $found;
            }
        }

        return[
            'controller' => str_replace('/', '\\', Config::gi()->get('defaultController')),
            'method' => Config::gi()->get('defaultMethod'),
            'vars' => [],
        ];
    }

    /**
     * Get project host
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->project_host;
    }

    /**
     * Switch to generate secured URI (https)
     *
     * @param bool $secure
     */
    public function setSecureHost(bool $secure = true)
    {
        if ($secure) {
            if (strpos($this->project_host, 'https') === false) {
                $this->project_host = str_replace('http', 'https', $this->project_host);
            }
        } else {
            if (strpos($this->project_host, 'https') !== false) {
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
     * @param string $controller className
     * @param string $method
     * @param array $vars
     * @param array $query
     * @return string
     * @throws \InvalidArgumentException
     */
    public function url(string $controller, string $method = 'index', array $vars = [], array $query = []): string
    {
        if (empty($controller) || empty($method) || !class_exists($controller)) {
            throw new \InvalidArgumentException('Missing required parameters.');
        }

        $uri = '';
        $controller = str_replace('\\', '/', $controller);
        foreach ($this->getMasks($controller, $method) as $mask) {
            //check number of defined variables against mask
            if (count($vars) != preg_match_all('/%[dis]/', $mask)) {
                continue;
            }

            $this->replaceMaskVariables($mask, $vars);
            $uri = $this->project_host . $mask;
            break;
        }

        if (empty($uri)) {
            $uri = $this->project_host . $controller . '/' . $method;
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

    /**
     * @param string $mask
     * @param array $vars
     */
    private function replaceMaskVariables(string &$mask, array $vars)
    {
        if (empty($vars)) {
            return;
        }

        $i = 0;
        while (preg_match('/%[dis]/', $mask, $match)) {
            switch ($match[0]) {
                case '%d':
                    $mask = preg_replace('/%d/', (string) floatval($vars[$i]), $mask, 1);
                    break;

                case '%i':
                    $mask = preg_replace('/%i/', (string) intval($vars[$i]), $mask, 1);
                    break;

                case '%s':
                    $mask = preg_replace('/%s/', filter_var($vars[$i], FILTER_SANITIZE_ENCODED), $mask, 1);
                    break;
            }

            $i++;
        }
    }

    /**
     * Get cached masks for requested controller/method
     * Method url can be called so many times and caching this improves performance
     * @param string $controller
     * @param string $method
     * @return array
     */
    private function getMasks(string $controller, string $method)
    {
        if (empty($this->masksCache)) {
            foreach ($this->routes as $mask => $value) {
                $this->masksCache[$value][] = $mask;
            }
        }

        return $this->masksCache[$controller . '/' . $method] ?? [];
    }

    /**
     * Get actual URI
     *
     * @param bool $getParams
     * @return string
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

        if (!$getParams and strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return $uri;
    }

    /**
     * Find route
     *
     * @param string $path
     * @return array
     */
    public function findRoute(string $path): array
    {
        $output = array();

        foreach ($this->routes as $mask => $route) {
            $output = $this->match($path, $mask, $route);
            if (!empty($output)) {
                break;
            }
        }

        return $output;
    }

    /**
     * Match specific route
     *
     * @param string $path
     * @param string|int $mask
     * @param string $route
     * @return array
     */
    private function match(string $path, string|int $mask, string $route): array
    {
        $output = [];

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
        $pattern .= '$/i';

        if (preg_match($pattern, $path, $vars)) {
            $parts = array_filter(explode('/', str_replace('\\', '/', $route)));
            $method = array_pop($parts);
            array_shift($vars);
            $vars = array_values($vars);

            foreach ($vars as $i => $var) {
                $vars[$i] = match ($tokenTypes[$i] ?? '%s') {
                    '%i' => (int) $var,
                    '%d' => (float) $var,
                    default => $var,
                };
            }

            $output = [
                'controller' => '\\' . implode('\\', $parts),
                'method' => $method,
                'vars' => $vars,
            ];
        }

        return $output;
    }
}
