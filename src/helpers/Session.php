<?php

namespace Dragon\helpers;

/**
 * Session
 * Static helper for working with PHP sessions.
 * Session must be started explicitly (e.g. via middleware) before use.
 *
 * @package Dragon\helpers
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class Session
{
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function start(): void
    {
        if (!self::isActive()) {
            session_start();
        }
    }

    public static function destroy(): void
    {
        if (!self::isActive()) {
            throw new \RuntimeException('Cannot destroy session: no active session found.');
        }
        session_unset();
        session_destroy();
    }

    public static function regenerate(): void
    {
        if (!self::isActive()) {
            throw new \RuntimeException('Cannot regenerate session ID: no active session found.');
        }
        session_regenerate_id(true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::isActive()) {
            throw new \RuntimeException('Cannot get session value: no active session found.');
        }
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        if (!self::isActive()) {
            throw new \RuntimeException('Cannot set session value: no active session found.');
        }
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        if (!self::isActive()) {
            throw new \RuntimeException('Cannot check session key: no active session found.');
        }
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        if (!self::isActive()) {
            throw new \RuntimeException('Cannot remove session key: no active session found.');
        }
        unset($_SESSION[$key]);
    }

    public static function all(): array
    {
        if (!self::isActive()) {
            throw new \RuntimeException('Cannot get all session data: no active session found.');
        }
        return $_SESSION;
    }

    public static function clear(): void
    {
        if (!self::isActive()) {
            throw new \RuntimeException('Cannot clear session data: no active session found.');
        }
        session_unset();
    }
}
