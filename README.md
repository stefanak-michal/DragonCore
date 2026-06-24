<img width="1348" height="633" alt="DragonCore" src="https://github.com/user-attachments/assets/d746aa0a-c8ee-48e8-9a92-0a5731070131" />

# Dragon PHP Framework

* Easy deployment and setup
* Config as php files with support of lookup tables
* Simple file names conventions
* One core for multiple projects
* Autoloader with vendor support
* CLI support
* PHP >= 8.5

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/stefanak-michal/DragonCore)
[![](https://img.shields.io/packagist/dt/stefanak-michal/dragoncore)](https://packagist.org/packages/stefanak-michal/dragoncore/stats)
[![](https://img.shields.io/github/v/release/stefanak-michal/DragonCore)](https://github.com/stefanak-michal/DragonCore/releases)
[![](https://img.shields.io/github/commits-since/stefanak-michal/DragonCore/latest)](https://github.com/stefanak-michal/DragonCore/releases/latest)
[![](https://img.shields.io/github/stars/stefanak-michal/DragonCore)](https://github.com/stefanak-michal/DragonCore/stargazers)

## How to start

You have three options how to start using this framework:
1. Install (clone) the framework to own directory and add into your project composer PSR-4 autoloader path `"Dragon": "path/to/DragonCore"`.
2. Require this framework as a dependency in your project `composer require stefanak-michal/dragoncore`.
3. Use composer to create project with framework `composer create-project stefanak-michal/dragoncore` and add your project files right into it.

After that you just have to call constructor of `\Dragon\Application` in your project start file (usually index.php):
```php
require_once __DIR__ . '/vendor/autoload.php';

$application = new Dragon\Application();
$application->run();
```

Check [documentation](docs/home.md) for more informations.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/Z8Z5ABMLW)
