# Global constants

**DS** - shortcut for const DIRECTORY_SEPARATOR

**BASE_PATH** - path to root of project (without directory separator at end)  

**DRAGON_PATH** - root directory of framework (without directory separator at end)

**IS_WORKSPACE** - if it's running on working platform  
This is identified by ip localhost or 127.0.0.1, or if directory `<app>/config/development` exists.

**IS_CLI** - if it's running as script

**DRAGON_DEBUG** - if it's debug system turned on

## Customization

You can define (overwrite) any of these by yourself before including `init.php` file.
```php
define('BASE_PATH', __DIR__);
```
