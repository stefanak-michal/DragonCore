<?php
/**
 * Project initialization script
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('APP_PATH')) {
    define('APP_PATH', dirname(get_included_files()[0]));
}

if (!defined('CORE_PATH')) {
    define('CORE_PATH', __DIR__ . DS . 'src');
}

if (file_exists(APP_PATH . DS . 'vendor' . DS . 'autoload.php')) {
    require_once APP_PATH . DS . 'vendor' . DS . 'autoload.php';
}
if (file_exists(__DIR__ . DS . 'vendor' . DS . 'autoload.php')) {
    require_once __DIR__ . DS . 'vendor' . DS . 'autoload.php';
}

// Register autoloader for app classes
spl_autoload_register(function (string $class) {
    if (empty($class)) {
        return;
    }
    $file = APP_PATH . DS . str_replace(['\\', '/'], DS, $class) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

if (!defined('IS_WORKSPACE')) {
    $workspace = false;
    if (
        file_exists(APP_PATH . DS . 'config' . DS . 'development' . DS)
        || in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])
    ) {
        $workspace = true;
    }
    define('IS_WORKSPACE', $workspace);
}

if (!defined('IS_CLI')) {
    define('IS_CLI', php_sapi_name() == 'cli');
}
if (IS_CLI) {
    set_time_limit(0);
}

if (!defined('DRAGON_DEBUG')) {
    if (\Dragon\Config::gi()->get('debug') !== null) {
        $debug = !empty(\Dragon\Config::gi()->get('debug'));
    } else {
        $debug = IS_WORKSPACE;
    }
    define('DRAGON_DEBUG', $debug);
}

/**
 * Dragon debug - simple alias for \Dragon\Debug::var_dump()
 * @param mixed ...$vars
 */
function dump(...$vars)
{
    \Dragon\Debug::var_dump(...$vars);
}

$autorun = $autorun ?? true;

//Execute project
$app = new \Dragon\Application();
if (!IS_CLI && $autorun) {
    $app->run();
}
