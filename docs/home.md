# Home

## Basic informations

Main goal is to keep it simple and lightweight. You can use framework project structure how it is and add your files directly into it. Or you can keep this framework somewhere else and keep your application clean. Also this way you can have multiple projects using the same core. You only need to include `init.php` file to make it work.

If you need to just load the framework without executing controllers logic, write `$autorun = false;` before `init.php` include.

## Topics

- [Controllers](controllers.md)
- [Middleware](middleware.md)
- [Routing](routing.md)
- [Views and elements](views-and-elements.md)
- [Config](config.md)
- [Debug](debug.md)

## Architecture

This framework can be used in any architecture (MVC, DDD, etc.), thanks to the support for absolute paths for Controllers (native) and Views (optional).

### Framework requires:
- short_open_tag = On
