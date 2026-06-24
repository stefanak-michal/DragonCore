# Home

## Basic informations

Main goal is to keep it simple and lightweight. You can use framework project structure how it is and add your files directly into it. Or you can keep this framework somewhere else and keep your application clean. Also this way you can have multiple projects using the same core. Third option is to require this project via composer.

Project is initialized by creating instance of `\Dragon\Application` class and optionally calling its `run()` method (for processing HTTP request).

## Topics

- [Controllers](controllers.md)
- [Middleware](middleware.md)
- [Routing](routing.md)
- [Views and elements](views-and-elements.md)
- [Config](config.md)
- [Debug](debug.md)

## Architecture

This framework can be used in any architecture (MVC, DDD, etc.), thanks to the support for absolute paths for Controllers (native) and Views (optional).
