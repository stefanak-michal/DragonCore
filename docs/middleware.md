# Middleware

Middleware wraps controller method execution, allowing you to run logic before and/or after the action.

## Interface

Every middleware must implement `\Dragon\middleware\IMiddleware`:

Calling `$next()` passes control to the next middleware in the stack, or to the controller method if there are no more middleware layers. Code placed before `$next()` runs on the way in (incoming), code placed after runs on the way out (outgoing).

## Registering middleware

Controllers declare their middleware stack by implementing `\Dragon\controllers\IController`:

```php
class Homepage implements \controllers\IController
{
    public function index()
    {
        \core\View::gi()->set('msg', 'Hello!');
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

## Middleware dependency

It is possible to define that one middleware depends on another one. For that reason attribute `RequireMiddleware` has to be used. Then if we want to use middleware with dependency, before it the dependency has to be added. Otherwise it will generate runtime exception.

### Example

```php
// Middleware definition

#[RequiresMiddleware(Session::class)]
class Csrf implements IMiddleware {
    // ...content
}

// Inside of controller
public function middleware(): array
{
    return [
        new \Dragon\middleware\Session(),
        new \Dragon\middleware\Csrf()
    ];
}

```