<?php

namespace Dragon\middleware;

/**
 * Class Csrf
 * Validates CSRF token on state-changing requests (POST, PUT, PATCH, DELETE).
 * Token is stored in the session and must be submitted as a form field (_csrf_token)
 * or request header (X-CSRF-Token).
 *
 * @package Dragon\middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
#[RequiresMiddleware(Session::class)]
class Csrf implements IMiddleware
{
    /**
     * Session key for the CSRF token
     *
     * @var string
     */
    private const TOKEN_KEY = '_csrf_token';

    public function handle(
        \Dragon\http\Request $request,
        \Dragon\http\Response $response,
        callable $next,
    ): \Dragon\http\Response {
        if (in_array($request->method, [
            \Dragon\http\RequestMethod::POST,
            \Dragon\http\RequestMethod::PUT,
            \Dragon\http\RequestMethod::PATCH,
            \Dragon\http\RequestMethod::DELETE,
        ])) {
            $token = $request->input(self::TOKEN_KEY) ?? $request->header('X-CSRF-Token');
            $stored = \Dragon\helpers\Session::get(self::TOKEN_KEY);

            if ($stored === null || $stored === '' || $token === null || $token === '' || !hash_equals($stored, (string) $token)) {
                return $response->status(403)->body('CSRF token mismatch');
            }
        }

        if (!\Dragon\helpers\Session::has(self::TOKEN_KEY)) {
            \Dragon\helpers\Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }

        return $next();
    }

    /**
     * Get the current CSRF token value
     *
     * @return string
     */
    public static function token(): string
    {
        return \Dragon\helpers\Session::get(self::TOKEN_KEY, '');
    }

    /**
     * Generate an HTML hidden input field with the CSRF token
     *
     * @return string
     */
    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="' . self::TOKEN_KEY . '" value="' . $token . '">';
    }
}
