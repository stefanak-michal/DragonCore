# Directory structure

## src/assets
- Contains css/js/media files.

## src/components
- User defined classes for specific block logic.
- Available Email component as PHPMailer facade.

## src/config
- All configuration files.

## src/controllers
- Main application logic.
- Controller classes has to implement `\Dragon\controllers\IController` interface. See [controllers](controllers.md).

## src/helpers
- Classes with usually helping static methods.

## src/middleware
- Classes to be used as middleware.
- Middleware classes has to implement `\Dragon\middleware\IMiddleware` interface. See [middleware](middleware.md).

## src/views
- View, layout and element files.

## scripts
- Cron/shell scripts.

## vendor
- Directory for 3rd classes.
