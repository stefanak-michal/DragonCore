<?php

namespace Tests;

use Dragon\Application;
use Dragon\Config;
use Dragon\Router;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * ApplicationTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ApplicationTest extends TestCase
{
    private array $serverBackup;

    public static function setUpBeforeClass(): void
    {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        if (!defined('CORE_PATH')) {
            define('CORE_PATH', dirname(__DIR__) . DS . 'src');
        }
        if (!defined('APP_PATH')) {
            define('APP_PATH', __DIR__);
        }
        if (!defined('DRAGON_DEBUG')) {
            define('DRAGON_DEBUG', false);
        }

        $ref = new ReflectionClass(Router::class);
        $ref->getProperty('instance')->setValue(null, null);

        $ref = new ReflectionClass(Config::class);
        $ref->getProperty('instance')->setValue(null, null);
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
        Config::gi()->set('defaultController', 'Tests/support/controllers/AppTestHome');
        Config::gi()->set('defaultMethod', 'index');
        Config::gi()->set('routes', []);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        parent::tearDown();
    }

    public function testRun(): void
    {
        ob_start();
        new Application()->run();
        $output = ob_get_clean();

        $this->assertSame('hello from app test', $output);
        $this->assertEquals('Tests\support\controllers\AppTestHome', Application::$controller::class);
        $this->assertEquals('index', Application::$method);
    }
}
