# Routing

## Configuration

Routing works with specification in config with key "routes", it is good practice to place it in `/config/routes.cfg.php`. It is an array of url masks with target controller and method. You can also group multiple routes by uri prefix or by controller.

## How does it work

When resolving current route, the Router will take first found match from routes table.  
Route targets must contain the fully-qualified controller class name (with namespace) followed by the method, separated by slashes or backslashed (escaped as double backslashes). Namespace is treated as absolute. No namespace is added automatically.  
Decision about grouped routes, if it's by uri prefix or controller, is made by starting slash. Starting slash means uri prefix, otherwise it's controller.

Available placeholders are:
- %i (int)
- %d (double with dot)
- %s (string)

### Example

```php
$aConfig = [
    'routes' => [
          '/' => 'controllers/Homepage/index',

          // Mask to target: <project_host>/course/6-some-title -> controllers\Homepage->course(int $i, string $s)
          '/course/%i-%s' => 'controllers/Homepage/course',
          // you can group routes by controller (key is the namespace path, value is the method)
          'controllers/OtherController' => [
               // <project_host>/do-something -> controllers\OtherController->someMethod()
               '/do-something' => 'someMethod'
          ],
          
          '/api' => [
                // <project_host>/api/some-action -> controllers\api\Foo->someAction()
                '/some-action' => 'controllers/api/Foo/someAction'
]         ]

          // double backslash can be used too
          '/new-url' => 'controllers\\Something\\index',

          // Using ::class constant is also supported, but it's better to avoid it, because the file will be loaded by PHP
          \controllers\admin\Dashboard::class => [
               // some routes
          ]
    ]
];
```

## String placeholder

To read string from url with placeholder `%s` it uses a default regular expression `[\w\-]+`. You can specify own with config key `routeStringRegex`.

## RouteTarget

When you want to take route definition even further, you can use `\Dragon\helpers\RouteTarget` class instead of string value. It allows you to define route target with more options including HTTP methods (verbs). If RouteTarget is inside of controller group, you can omit controller class name, it will be added automatically.

```php
$aConfig = [
    'routes' => [
          '/course/%i-%s' => new \Dragon\helpers\RouteTarget('controllers/Homepage', 'course', [\Dragon\http\RequestMethod::GET]),
          'controllers/OtherController' => [
               '/do-something' => new \Dragon\helpers\RouteTarget(method: 'someMethod')
          ],
          '/api' => [
                '/some-action' => new \Dragon\helpers\RouteTarget('controllers/api/Foo', 'someAction', [\Dragon\http\RequestMethod::GET, \Dragon\http\RequestMethod::POST])
]         ]
    ]
];
```

Default accepted HTTP method is only GET method (only when using RouteTarget class, doesn't apply to string route definitions). If route is restricted to specific HTTP methods and route is called with different method, the Application will return HTTP status code 405 (Method Not Allowed) response.

## Generating

To generate url (mask version based on route definition) you can call (ex.) `\Dragon\Router::gi()->url(Homepage::class, 'index');`.

For homepage url you can use shortcut `\Dragon\Router::gi()->homepage();`. Homepage can be defined via config by setting key `project_host`, otherwise the Router will try to resolve it by itself by using $_SERVER variable.

To redirect user somewhere else you can call `\Dragon\http\Response::redirect();`. In debug mode redirecting shows custom page with more information about call.
