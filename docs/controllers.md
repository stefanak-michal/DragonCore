# Controllers

Controllers contain the main application logic for a route. After routing resolves the target controller and method, Dragon creates a `Request`, creates a `Response`, runs the configured [middleware](middleware.md), and finally calls the controller action.

## Interface

Controller classes should implement `\Dragon\controllers\IController`:

```php
use Dragon\controllers\IController;

class Homepage implements IController
{
    public function middleware(): array
    {
        return [];
    }
}
```

The `middleware()` method returns the middleware stack that wraps the controller action. Middleware is optional, but if you use it, it is executed in the order returned by this method. See [middleware](middleware.md).

## Action method signature

Controller methods are called with two arguments:

```php
public function index(Request $request, Response $response): Response
```

- `Request $request` contains the incoming HTTP request data.
- `Response $response` is the outgoing response object that you modify and return.
- The method should return the same `Response` instance after updating it.

## What is available in Request

`\Dragon\http\Request` gives access to the current HTTP request:

- `$request->get` for query string values (`$_GET`).
- `$request->post` for form/body values (`$_POST`).
- `$request->files` for uploaded files (`$_FILES`).
- `$request->cookies` for cookie values.
- `$request->headers` for parsed incoming headers.
- `$request->server` for server/environment values (`$_SERVER`).
- `$request->method` for the HTTP method.
- `$request->uri` for the raw request URI.
- `$request->body` for the raw request body.
- `$request->params` for route parameters captured by the router.

Helper methods are also available:

- `$request->input('key')` reads from POST first and then GET.
- `$request->json()` decodes a JSON request body.
- `$request->header('Content-Type')` reads a single header.
- `$request->isAjax()` checks for XMLHttpRequest requests.
- `$request->isMethod('POST')` checks the HTTP method.
- `$request->ip()` returns the client IP address.

Typical controller usage is validation, reading route parameters, reading form or JSON input, and deciding what response should be returned.

## What is available in Response

`\Dragon\http\Response` represents the outgoing HTTP response. A controller can modify it before returning it:

- `$response->status(201)` sets the status code.
- `$response->header('X-Foo', 'bar')` adds or replaces a header.
- `$response->body('plain text')` sets the raw body.
- `$response->html($html)` sets HTML output and the `Content-Type` header.
- `$response->json($data)` encodes JSON and sets the `Content-Type` header.
- `$response->redirect('/login')` sends a redirect response immediately.

In practice, controller methods usually return a modified response, for example JSON for an API endpoint or an HTML response produced by middleware such as `\Dragon\middleware\Render`.

## Example

```php
use Dragon\controllers\IController;
use Dragon\http\Request;
use Dragon\http\Response;
use Dragon\middleware\Render;

class Homepage implements IController
{
    public function index(Request $request, Response $response): Response
    {
        $name = $request->input('name', 'guest');

        \Dragon\View::gi()->set('name', $name);

        return $response
            ->status(200)
            ->header('X-Controller', 'Homepage');
    }

    public function apiUser(Request $request, Response $response): Response
    {
        return $response->json([
            'id' => $request->params[0] ?? null,
            'ajax' => $request->isAjax(),
        ]);
    }

    public function middleware(): array
    {
        return [
            new Render(),
        ];
    }
}
```

In this example:

- `index()` reads input from the request, prepares view data, and returns the modified response.
- `Render` middleware runs around the controller action and converts the view into the final HTML response.
- `apiUser()` returns JSON directly, so it does not need view rendering logic in the action itself.
