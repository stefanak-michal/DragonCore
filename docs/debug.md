# Debug

Development environment has automatically turned on debug system. You can turn it off with setting up `"debug" => 0` in config file.

Core is automatically generating report with loaded files, timers, queries (if configured) and dumps. It's stored as html in directory `<project_root>/tmp/debug/`. Url to this file is also available in response header as `X-Dragon-Debug`.  
This report is also visible during redirect (development environment).

## Debug class offers main 4 static methods:

### var_dump

```php
\core\Debug::var_dump(...$args);
```

Write any variable to log.  
This method has global alias `dump()`.

### timer

```php
\core\Debug::timer($key);
```

Measure execution time of script part. Don't forget call it second time to stop the timer.  
All open timers are automatically closed before generating report.

### files

```php
\core\Debug::files($file);
```

Log loaded file. Used by core to log loaded classes and views.

### query

```php
\core\Debug::query($query, $hidden = [], $otherColumns = []);
``` 

# Toolbar

You can also generate report as toolbar which can be inserted into website.

```php
\core\Debug::onsite()
```

This toolbar is automatically inserted if you will use `\Dragon\middleware\Render`.
