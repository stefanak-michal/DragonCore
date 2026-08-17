<?php

namespace Dragon\helpers;

/**
 * Validation helper functions
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 * @package Dragon\helpers
 */
class Validation
{
    /**
     * Check email
     *
     * @param string $email
     * @return boolean
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check url
     *
     * @param string $url
     * @return boolean
     */
    public static function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Sanitize values to proper data types
     * @param array $data
     */
    public static function sanitize(array &$data): void
    {
        foreach ($data as &$entry) {
            if (is_string($entry)) {
                $entry = trim($entry);
                if (strlen($entry) === 0) {
                    $entry = null;
                    continue;
                }

                if (strtolower($entry) === 'true') {
                    $entry = true;
                    continue;
                }
                if (strtolower($entry) === 'false') {
                    $entry = false;
                    continue;
                }

                $result = filter_var($entry, FILTER_VALIDATE_INT);
                if ($result !== false) {
                    $entry = $result;
                    continue;
                }

                $result = filter_var($entry, FILTER_VALIDATE_FLOAT);
                if ($result !== false) {
                    $entry = $result;
                    continue;
                }
            } elseif (is_array($entry)) {
                self::sanitize($entry);
            }
        }
    }
}
