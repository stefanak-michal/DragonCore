# Middleware

Middleware wraps controller method execution, allowing you to run logic before and/or after the action. For the controller method signature and the role of `Request` and `Response`, see [controllers](controllers.md).

## Interface

Every middleware must implement `\Dragon\middleware\IMiddleware`:

Calling `$next()` passes control to the next middleware in the stack, or to the controller method if there are no more middleware layers. Code placed before `$next()` runs on the way in (incoming), code placed after runs on the way out (outgoing).

## Registering middleware

Controllers declare their middleware stack by implementing `\Dragon\controllers\IController`:

```php
use Dragon\controllers\IController;
use Dragon\http\Request;
use Dragon\http\Response;

class Homepage implements IController
{
    public function index(Request $request, Response $response): Response
    {
        \Dragon\View::gi()->set('msg', 'Hello!');

        return $response;
    }

    public function middleware(): array
    {
        return [
            new \Dragon\middleware\Render()
        ];
    }
}
```

_Middleware is executed in the order it is returned._

The same `Request` and `Response` objects are passed through the middleware chain and into the controller action. Middleware can inspect request data before the action runs and can also modify the response returned by the controller.

## Middleware dependency

It is possible to define that one middleware depends on another one. For that reason attribute `RequiresMiddleware` has to be used. Then if we want to use middleware with dependency, before it the dependency has to be added. Otherwise it will generate runtime exception.

### Example

```php
// Middleware definition

use Dragon\middleware\RequiresMiddleware;
use Dragon\middleware\Session;
use Dragon\middleware\IMiddleware;

#[RequiresMiddleware(Session::class)]
class Csrf implements IMiddleware {
    // ...content
}

// Inside of controller
public function middleware(): array
{
    return [
        new \Dragon\middleware\Session(), // session has to be first, because it's required by Csrf
        new \Dragon\middleware\Csrf()
    ];
}

```