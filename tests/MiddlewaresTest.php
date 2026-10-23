<?php

namespace Tests;

use Dragon\Application;
use Dragon\Config;
use PHPUnit\Framework\TestCase;

class MiddlewaresTest extends TestCase
{
    private array $serverBackup;

    public static function setUpBeforeClass(): void
    {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        if (!defined('CORE_PATH')) {
            define('CORE_PATH', dirname(__DIR__, 2) . DS . 'src');
        }
        if (!defined('APP_PATH')) {
            define('APP_PATH', dirname(__DIR__));
        }
        if (!defined('DRAGON_DEBUG')) {
            define('DRAGON_DEBUG', false);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBackup = $_SERVER;

        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['HTTP_HOST'] = 'example.test';
        $_SERVER['SERVER_NAME'] = 'example.test';
        $_SERVER['REQUEST_URI'] = '/';

        Config::gi()->set('project_host', 'http://example.test');
        Config::gi()->set('defaultMethod', 'index');
        Config::gi()->set('routes', []);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }



    public function testControllerWithClassAndMethodAttribute(): void
    {
        Config::gi()->set('defaultController', 'Tests/support/controllers/ControllerWithClassAndMethodAttribute');

        ob_start();
        new Application()->run();
        $output = ob_get_clean();

        $this->assertEquals(
            'middleware1 hit' . PHP_EOL
            . 'constructor hit' . PHP_EOL
            . 'middleware2 hit' . PHP_EOL
            . 'method hit' . PHP_EOL, $output
        );
    }

    public function testControllerWithClassAttribute(): void
    {
        Config::gi()->set('defaultController', 'Tests/support/controllers/ControllerWithClassAttribute');

        ob_start();
        new Application()->run();
        $output = ob_get_clean();

        $this->assertEquals(
            'middleware1 hit' . PHP_EOL
            . 'constructor hit' . PHP_EOL
            . 'method hit' . PHP_EOL, $output
        );
    }

    public function testControllerWithMiddlewareDuplicate(): void
    {
        Config::gi()->set('defaultController', 'Tests/support/controllers/ControllerWithMiddlewareDuplicate');

        ob_start();
        new Application()->run();
        $output = ob_get_clean();

        $this->assertEquals(
            'middleware1 hit' . PHP_EOL
            . 'constructor hit' . PHP_EOL
            . 'method hit' . PHP_EOL, $output
        );
    }

    public function testControllerWithMiddlewareMethod(): void
    {
        Config::gi()->set('defaultController', 'Tests/support/controllers/ControllerWithMiddlewareMethod');

        ob_start();
        new Application()->run();
        $output = ob_get_clean();

        $this->assertEquals(
            'constructor hit' . PHP_EOL
            . 'middleware1 hit' . PHP_EOL
            . 'method hit' . PHP_EOL, $output);
    }

    public function testControllerWithMiddlewareMethodAndClassAttribute(): void
    {
        Config::gi()->set('defaultController', 'Tests/support/controllers/ControllerWithMiddlewareMethodAndClassAttribute');

        ob_start();
        new Application()->run();
        $output = ob_get_clean();

        $this->assertEquals(
            'middleware2 hit' . PHP_EOL
            . 'constructor hit' . PHP_EOL
            . 'middleware1 hit' . PHP_EOL
            . 'method hit' . PHP_EOL, $output);
    }
}
