# CLI

## Creating new script

At the beginning of your script file you can include your `index.php` file which contains include of core `init.php` file. After this line of code you can write your script how you would like.

Then to run your script you can just directly call:
```
php <path to script file in project scripts folder>
```

_Autorun of core Application for processing http request is automatically disabled in CLI environment._

## Existing scripts

Dragon framework contains following scripts.

### create-app.php

To generate application directory structure with basic files.  

When you are in framework directory, you can execute command in any terminal:

```
php scripts/create-app.php <path where to put application>
```

_Path where to put application has to lead to empty directory or non-existing directory._
