<?php

namespace http;

/**
 * RequestMethod
 * Backed enum representing standard HTTP request methods.
 *
 * @package http
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
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
