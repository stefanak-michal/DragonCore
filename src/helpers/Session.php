<?php

namespace Dragon\helpers;

/**
 * Session
 * Static helper for working with PHP sessions, including flash messages.
 * Session must be started explicitly (e.g. via middleware) before use.
 * All methods trigger E_USER_WARNING if called without an active session.
 *
 * @package Dragon\helpers
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class Session
{
    /**
     * Key used to store flash messages in $_SESSION
     *
     * @var string
     */
    private const FLASH_KEY = '_flash';

    /**
     * Check if a session is currently active.
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Start the session if not already active.
     *
     * @return void
     */
    public static function start(): void
    {
        if (!self::isActive()) {
            session_start();
        }
    }

    /**
     * Destroy the current session.
     *
     * @return void
     */
    public static function destroy(): void
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return;
        }
        session_unset();
        session_destroy();
    }

    /**
     * Regenerate the session ID.
     * Should be called after login/logout to prevent session fixation.
     *
     * @return void
     */
    public static function regenerate(): void
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return;
        }
        session_regenerate_id(true);
    }

    /**
     * Get a value from the session.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return $default;
        }
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a value in the session.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return;
        }
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a key exists in the session.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return false;
        }
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a key from the session.
     *
     * @param string $key
     * @return void
     */
    public static function remove(string $key): void
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return;
        }
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data.
     *
     * @return array
     */
    public static function all(): array
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return [];
        }
        return $_SESSION;
    }

    /**
     * Remove all data from the session.
     *
     * @return void
     */
    public static function clear(): void
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return;
        }
        session_unset();
    }

    /**
     * Store a flash message for the next request.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function flash(string $key, mixed $value): void
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return;
        }
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    /**
     * Read and remove a flash message.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function consumeFlash(string $key, mixed $default = null): mixed
    {
        if (!self::isActive()) {
            trigger_error('Session is not started', E_USER_WARNING);
            return $default;
        }
        if (!isset($_SESSION[self::FLASH_KEY][$key])) {
            return $default;
        }
        $value = $_SESSION[self::FLASH_KEY][$key];
        unset($_SESSION[self::FLASH_KEY][$key]);
        return $value;
    }
}
