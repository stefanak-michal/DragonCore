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
    /**
     * Array of all config variables
     *
     * @var array
     */
    private $configVars = array();

    /**
     * Array of lookup tables
     *
     * @var array
     */
    private $lookUpTables = array();

    /**
     * Lookuptable file affix
     *
     * @var string
     */
    public static $ltAffix = '.lt.php';

    /**
     * Config file affix
     *
     * @var string
     */
    public static $cfgAffix = '.cfg.php';

    /**
     * @var Config
     */
    private static $instance;

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
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value)
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
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $output = $default;

        if (!empty($key) and isset($this->configVars[$key])) {
            $output = $this->configVars[$key];
        }

        return $output;
    }

    /**
     * Read lookup table value
     *
     * @param string $dotSeparatedKeys
     * @return mixed
     */
    public function lt(string $dotSeparatedKeys)
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
     *
     * @param string $filepath
     * @return bool
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
     *
     * @param string $filepath
     * @return bool
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
    public static function apply(string $configKey, $object)
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
