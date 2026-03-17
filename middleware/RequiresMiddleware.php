<?php

namespace middleware;

/**
 * RequiresMiddleware
 * Attribute to declare that a middleware depends on another middleware
 * being registered before it in the middleware stack.
 * Validated at runtime by Dragon before pipeline assembly.
 *
 * Usage:
 *   #[RequiresMiddleware(SessionMiddleware::class)]
 *   class CsrfMiddleware implements IMiddleware { ... }
 *
 * @package middleware
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class RequiresMiddleware
{
    /**
     * @param string $middleware Fully qualified class name of the required middleware
     */
    public function __construct(
        public readonly string $middleware,
    ) {}
}
