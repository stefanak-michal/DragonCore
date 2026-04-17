# Routing

## Configuration

Routing works with specification in config with key "routes", usually specified in file `/config/routes.cfg.php`. It is a array of url masks with target controller and method. You can also group multiple routes from one controller into a subarray.

## How does it works

Router will take first match from routes table.  
Namespace word `controllers` is automatically prefixed. 

Available placeholders are:
- %i (int)
- %d (double with dot)
- %s (string)

### Example

```php
$aConfig = [
    'routes' => [
        // No mask, direct controller/method: <project_host>/homepage/index -> controllers/homepage->index()
        'homepage/index',
        // Mask to target: <project_host>/course/6-some-title -> controllers/Homepage->course(int $i, string $s)
        'course/%i-%s' => 'Homepage/course',
        // you can group routes by controller
        'OtherController' => [
             // <project_host>/do-something -> controllers/OtherController->someMethod()
             'do-something' => 'someMethod'
        ],
        // nested controller
        'admin/Dashboard' => [
             // <project_host>/admin/dashboard -> controllers/admin/Dashboard->index()
             'admin/dashboard' => 'index'
        ],

        //You can also use this style, but that will cause php interpretor to load and execute the file
        \controllers\admin\Dashboard::class => [
             //some routes
        ]
    ]
];
```

## String placeholder

To read string from url with placeholder `%s` it uses a default regular expression `[\w\-]+`. You can specify own with config key `routeStringRegex`.

## Generating

To generate url (mask version based on route definition) you can call (ex.) `\Dragon\Router::gi()->url(Homepage::class, 'index');`.

For homepage url you can use shortcut ```\Dragon\Router::gi()->homepage();```. What is homepage is specified by [config](config.md).

To redirect user somewhere else you can call `\Dragon\http\Response::redirect();`. It supports message which is passed as session flash message. In debug mode redirecting shows custom page with more information about call.
