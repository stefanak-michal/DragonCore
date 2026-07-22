# Config

Configuration is held at `/config` directory. Config files directly placed in this directory are respectively merged (and overwritten) by workspace files in subdirectory `development`.

Config supports 2 types of configuration files - config and lookup table. These are php files with array variable with any name. If the file contains more than one variable, first array variable is used and everything else is ignored.

System is auto loading all files in config directory.

## Class Config

Is available with singleton method `\Dragon\Config::gi()`.

File extensions for config files can be changed on static properties:
- cfgAffix - default `.cfg.php`
- ltAffix - default `.lt.php`

<br/>

| Name | Arguments | Description |
|---|---|---|
| set | string $key, mixed $value | Set custom config variable |
| get | string $key | Get config variable provided by `loadConfig` or `set` |
| lt | string $dotSeparatedKeys | Get value from lookup table |

## Look up table

It's for specific access to array. To store custom enumerations or required structures. Access to content of it is through dot separated keys.

Example (of course I know you can call `pi()`):

```php
//main.lt.php
<?php
$lookUpTable = [
    'math' => [
        'pi' => 3.14159
    ]
];
```

```php
//usage somewhere
$pi = \Dragon\Config::gi()->lt('math.pi');
```

## Auto apply config

Config class contains static method `apply`. This method accepts first argument instance of any class and second argument key from loaded configuration. Main purpose is to call on class, where you want to set up different properties. 

Look into [Email](emails.md), and you can see usage of it in `\Dragon\components\Email->_send()`.
