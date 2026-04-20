# Emails

For sending email you can use facade `\Dragon\components\Email` class. It's using popular PHPMailer which is included in vendor directory.

## Templates

Method `send` accepts instance of `\Dragon\View`. You should place your email templates (views) somewhere in custom directory (ex. templates) and call it this way:
```php
$email->send(new View('/templates/registration'));
```

## Pictures
Pictures in your email has to have `cid:` prefix (`<img src="cid:image.jpg">`). Email class will be looking for these files in directory with same name as template next to template file `/templates/registration/image.jpg`.

## Config

If you want to configure PHPMailer, you can use standard config file `main.cfg.php` with key "mailer". Config autoload method will execute and set everything on PHPMailer instance.

Sample configuration:

```php
'mailer' => [
    'isSMTP',
    'Host' => '172.28.3.9',
    'Port' => 25,
    'SMTPAuth' => false,
    'SMTPSecure' => false,
    'SMTPAutoTLS' => false
],
```
