# Middleware

Middleware wraps controller method execution, allowing you to run logic before and/or after the action. For the controller method signature and the role of `Request` and `Response`, see [controllers](controllers.md).

## Interface

Every middleware must implement `\Dragon\middleware\IMiddleware`:

Calling `$next()` passes control to the next middleware in the stack, or to the controller method if there are no more middleware layers. Code placed before `$next()` runs on the way in (incoming), code placed after runs on the way out (outgoing).

## Registering middleware

Preferred way is to use middleware as attribute on the class or on the method.

Another option is add method `public function middleware(): array` into controller and it has to return array of middleware instances, each implementing `IMiddleware` interface. 

Execution flow when application `run()` is called is following:

1. Middlewares set via controller attributes. 
2. Instantiating controller (which means that __construct is called).
3. Middlewares returned by `middleware()` method if it's defined.
4. Middlewares set via method attributes.

Even if a middleware is defined in the pipeline multiple times, it's called only once (on the first occurence).

## Example

```php
use Dragon\controllers\IController;
use Dragon\http\Request;
use Dragon\http\Response;

#[Session]
class Homepage implements IController
{
    #[Render]
    public function index(Request $request, Response $response): Response
    {
        \Dragon\View::gi()->set('msg', 'Hello!');

        return $response;
    }
}
```

The same `Request` and `Response` objects are passed through the pipeline into the controller action. Middleware can inspect request data before the action runs and can also modify the response returned by the controller.

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
