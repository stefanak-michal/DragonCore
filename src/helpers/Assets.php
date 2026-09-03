<?php

namespace Dragon\helpers;

/**
 * Assets
 * Helper to manage css/js assets files
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 * @package Dragon\helpers
 */
class Assets
{
    /**
     * Type css
     */
    const string TYPE_CSS = 'css';
    /**
     * Type js
     */
    const string TYPE_JS = 'js';

    /**
     * Assets to load on page
     */
    private static array $toLoad = array();

    /**
     * Reset list of assets to load
     */
    public static function reset(): void
    {
        self::$toLoad = array();
    }

    /**
     * Add assets to load
     * @param string ...$names relative path to css/js asset file in assets directory
     */
    public static function add(...$names): void
    {
        foreach ($names as $name) {
            $type = pathinfo($name, PATHINFO_EXTENSION);
            if (!in_array($type, [self::TYPE_CSS, self::TYPE_JS])) {
                \Dragon\Debug::var_dump('Unsupported asset type "' . $type . '" for asset file "' . $name . '"');
                continue;
            }

            if (!isset(self::$toLoad[$type][$name])) {
                $assetUrl = self::generateUrl($name);
                if (!empty($assetUrl)) {
                    self::$toLoad[$type][$name] = $assetUrl;
                } else {
                    \Dragon\Debug::var_dump('Asset file "' . $name . '" not found');
                }
            }
        }
    }

    /**
     * Remove assets from load
     * @param string ...$names relative path to css/js asset file in assets directory
     */
    public static function remove(string ...$names): void
    {
        foreach ($names as $name) {
            $type = pathinfo($name, PATHINFO_EXTENSION);
            if (array_key_exists($type, self::$toLoad) && array_key_exists($name, self::$toLoad[$type])) {
                unset(self::$toLoad[$type][$name]);
            }
        }
    }

    /**
     * Generate asset url
     *
     * @param string $name
     * @return string
     */
    public static function generateUrl(string $name): string
    {
        $possiblePaths = [
            APP_PATH . DS . str_replace(['/', '\\'], DS, $name),
            dirname(get_included_files()[0]) . DS . str_replace(['/', '\\'], DS, $name),
            CORE_PATH . DS . str_replace(['/', '\\'], DS, $name),
            APP_PATH . DS . 'assets' . DS . str_replace(['/', '\\'], DS, $name),
            dirname(get_included_files()[0]) . DS . 'assets' . DS . str_replace(['/', '\\'], DS, $name),
            CORE_PATH . DS . 'assets' . DS . str_replace(['/', '\\'], DS, $name),
        ];

        $key = array_find_key($possiblePaths, function (string $path) {
            return file_exists($path);
        });

        if ($key === null) {
            return '';
        }

        return \Dragon\Router::gi()->getHost() . ($key > 2 ? 'assets/' : '') . $name . '?v=' . filemtime($possiblePaths[$key]);
    }

    /**
     * Render assets on page
     *
     * @return string
     */
    public static function draw(): string
    {
        $output = array();

        if (!empty(self::$toLoad[self::TYPE_CSS])) {
            foreach (self::$toLoad[self::TYPE_CSS] as $file) {
                $output[] = '<link rel="stylesheet" href="' . $file . '" />';
            }
        }
        if (!empty(self::$toLoad[self::TYPE_JS])) {
            foreach (self::$toLoad[self::TYPE_JS] as $file) {
                $output[] = '<script src="' . $file . '" ></script>';
            }
        }

        return implode(PHP_EOL, $output);
    }
}
