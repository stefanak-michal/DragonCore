# Routing

## Configuration

Routing works with specification in config with key "routes", usually specified in file `/config/routes.cfg.php`. It is a array of url masks with target controller and method. You can also group multiple routes from one controller into a subarray.

## How does it works

Router will take first match from routes table.  
Route targets must contain the fully-qualified controller class name (with namespace) followed by the method, separated by slashes or backslashed (escaped as double backslashes). Namespace is treated as absolute even when initial slash is missing. No namespace is added automatically.

Available placeholders are:
- %i (int)
- %d (double with dot)
- %s (string)

### Example

```php
$aConfig = [
    'routes' => [
          // No mask, direct controller/method: <project_host>/controllers/Homepage/index -> controllers\Homepage->index()
          'controllers/Homepage/index',
          // Mask to target: <project_host>/course/6-some-title -> controllers\Homepage->course(int $i, string $s)
          'course/%i-%s' => 'controllers/Homepage/course',
          // you can group routes by controller (key is the namespace path, value is the method)
          'controllers/OtherController' => [
               // <project_host>/do-something -> controllers\OtherController->someMethod()
               'do-something' => 'someMethod'
          ],
          // nested controller
          'controllers/admin/Dashboard' => [
               // <project_host>/adm/board -> controllers\admin\Dashboard->index()
               'adm/board' => 'index'
          ],

          // double backslash can be used too
          'new-url' => 'controllers\\Something\\index',

          // Using ::class constant is also supported (causes the file to be loaded and executed by PHP)
          \controllers\admin\Dashboard::class => [
               // some routes
          ]
    ]
];
```

## String placeholder

To read string from url with placeholder `%s` it uses a default regular expression `[\w\-]+`. You can specify own with config key `routeStringRegex`.

## Generating

To generate url (mask version based on route definition) you can call (ex.) `\Dragon\Router::gi()->url(Homepage::class, 'index');`.

For homepage url you can use shortcut `\Dragon\Router::gi()->homepage();`. What is homepage is specified by [config](config.md).

To redirect user somewhere else you can call `\Dragon\http\Response::redirect();`. In debug mode redirecting shows custom page with more information about call.
