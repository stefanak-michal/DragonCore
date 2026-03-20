<?php

namespace Dragon\http;

/**
 * RequestMethod
 * Backed enum representing standard HTTP request methods.
 *
 * @package Dragon\http
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
enum RequestMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
}
