<?php

namespace Dragon;

/**
 * Config
 * Read config files and hold it
 *
 * @package Dragon
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
final class Config
{
    public const string PROJECT_HOST = 'project_host';
    public const string PROJECT_EMAIL = 'project_email';
    public const string PROJECT_TITLE = 'project_title';
    public const string ROUTES = 'routes';
    public const string ROUTE_STRING_REGEX = 'routeStringRegex';
    public const string DEFAULT_CONTROLLER = 'defaultController';
    public const string DEFAULT_METHOD = 'defaultMethod';
    public const string VIEWS_EXTENSION = 'viewsExtension';
    public const string VIEWS_DIRECTORY = 'viewsDirectory';

    /**
     * Array of all config variables
     */
    private array $configVars = [];

    /**
     * Array of lookup tables
     */
    private array $lookUpTables = [];

    /**
     * Lookuptable file affix
     */
    public static string $ltAffix = '.lt.php';

    /**
     * Config file affix
     */
    public static string $cfgAffix = '.cfg.php';

    private static ?Config $instance = null;

    /**
     * Singleton
     *
     * @return Config
     */
    public static function gi(): Config
    {
        if (!self::$instance instanceof self) {
            self::$instance = new Config();

            if (file_exists(CORE_PATH . DS . 'config' . DS)) {
                foreach (glob(CORE_PATH . DS . 'config' . DS . '*' . self::$cfgAffix) as $file) {
                    self::$instance->loadConfig($file);
                }
                foreach (glob(CORE_PATH . DS . 'config' . DS . '*' . self::$ltAffix) as $file) {
                    self::$instance->loadLookupTable($file);
                }
            }

            if (file_exists(APP_PATH . DS . 'config' . DS)) {
                foreach (glob(APP_PATH . DS . 'config' . DS . '*' . self::$cfgAffix) as $file) {
                    self::$instance->loadConfig($file);
                }
                foreach (glob(APP_PATH . DS . 'config' . DS . '*' . self::$ltAffix) as $file) {
                    self::$instance->loadLookupTable($file);
                }
                foreach (glob(APP_PATH . DS . 'config' . DS . 'development' . DS . '*' . self::$cfgAffix) as $file) {
                    self::$instance->loadConfig($file);
                }
                foreach (glob(APP_PATH . DS . 'config' . DS . 'development' . DS . '*' . self::$ltAffix) as $file) {
                    self::$instance->loadLookupTable($file);
                }
            }
        }

        return self::$instance;
    }

    /**
     * Set config parameter
     */
    public function set(string $key, mixed $value): void
    {
        if (!empty($key)) {
            if (!empty($value)) {
                $this->configVars[$key] = $value;
            } else {
                unset($this->configVars[$key]);
            }
        }
    }

    /**
     * Read config parameter
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $output = $default;

        if (!empty($key) and isset($this->configVars[$key])) {
            $output = $this->configVars[$key];
        }

        return $output;
    }

    /**
     * Read lookup table value
     */
    public function lt(string $dotSeparatedKeys): mixed
    {
        if (empty($dotSeparatedKeys)) {
            return null;
        }

        $output = array();

        foreach (explode('.', $dotSeparatedKeys) as $key) {
            if (empty($output) && array_key_exists($key, $this->lookUpTables)) {
                $output = $this->lookUpTables[$key];
            } elseif (is_array($output) && array_key_exists($key, $output)) {
                $output = $output[$key];
            } else {
                return null;
            }
        }

        return $output;
    }

    /**
     * Load lookup table file
     */
    public function loadLookupTable(string $filepath): bool
    {
        $merged = false;
        if (file_exists($filepath)) {
            include $filepath;
            $defined = get_defined_vars();
            foreach ($defined as $value) {
                if (is_array($value)) {
                    $this->lookUpTables = array_replace_recursive($this->lookUpTables, $value);
                    $merged = true;
                    break;
                }
            }
        }
        return $merged;
    }

    /**
     * Load config file
     */
    public function loadConfig(string $filepath): bool
    {
        $merged = false;
        if (file_exists($filepath)) {
            include $filepath;
            $defined = get_defined_vars();
            foreach ($defined as $value) {
                if (is_array($value)) {
                    $this->configVars = array_replace_recursive($this->configVars, $value);
                    $merged = true;
                    break;
                }
            }
        }
        return $merged;
    }

    /**
     * Apply config settings by key on object
     * @param string $configKey
     * @param $object
     */
    public static function apply(string $configKey, $object): void
    {
        $c = self::gi()->get($configKey);
        if (!empty($c) && is_array($c)) {
            foreach ($c as $key => $value) {
                if (is_int($key) && method_exists($object, $value)) {
                    call_user_func([$object, $value]);
                } elseif (property_exists($object, $key)) {
                    if (is_object($object))
                        $object->{$key} = $value;
                    else
                        $object::$$key = $value;
                }
            }
        }
    }
}
