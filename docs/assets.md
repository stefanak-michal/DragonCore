# Assets

## Usage

You can make a call to register assets. Each argument has to be a path to asset file relative to assets directory. 
```php
\Dragon\helpers\Assets::add('css/default.css');
``` 

_Helper class does supports css and js files._

To render html tags with registered assets, you can place in `<head>` in layout file following call:
```php
<?= \Dragon\helpers\Assets::draw() ?>
```

## Image url

When you need image url (for ex. `<img src="" />`), you can use method `generateUrl()`.

This method accepts relative path and try multiple locations to find requested file. It also adds extra GET parameter for versioning based of last file modification time.

## Versioning

System is automatically putting file update time as a version of it. There is no need to manually update or specify it.

## Minimize

There is no automatic minize system, but it supports minimized versions. For example if you have asset file `default.js` and `default.min.js`, system will automatically use `default.min.js` in production.
