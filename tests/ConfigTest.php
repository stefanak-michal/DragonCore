<?php

use PHPUnit\Framework\TestCase;
use Dragon\Config;

/**
 * ConfigTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class ConfigTest extends TestCase
{
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

        $ref = new ReflectionClass(Config::class);
        $ref->getProperty('instance')->setValue(null, null);
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up any test configuration files if they exist
        @unlink('test' . Config::$ltAffix);
        @unlink('test' . Config::$cfgAffix);
    }

    public function testGet(): void
    {
        //This way we verify that the default config file is loaded and accessible
        $this->assertEquals('Dragon Core', Config::gi()->get('project_title'));
    }

    public function testSetGet(): void
    {
        Config::gi()->set('testKey', 'testValue');
        $this->assertEquals('testValue', Config::gi()->get('testKey'));
    }

    public function testGetNonExistentKey(): void
    {
        $this->assertNull(Config::gi()->get('nonExistentKey'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('defaultValue', Config::gi()->get('nonExistentKey', 'defaultValue'));
    }

    public function testSingleton(): void
    {
        $this->assertSame(Config::gi(), Config::gi());
    }

    public function testLt(): void
    {
        file_put_contents('test' . Config::$ltAffix, "<?php \$lt = ['greeting' => ['say' => 'Hello']];");
        Config::gi()->loadLookupTable('test' . Config::$ltAffix);
        $this->assertEquals('Hello', Config::gi()->lt('greeting.say'));
        unlink('test' . Config::$ltAffix);
    }

    public function testConfigFileLoading(): void
    {
        file_put_contents('test' . Config::$cfgAffix, "<?php \$cfg = ['appName' => 'TestApp'];");
        Config::gi()->loadConfig('test' . Config::$cfgAffix);
        $this->assertEquals('TestApp', Config::gi()->get('appName'));
        unlink('test' . Config::$cfgAffix);
    }

    public function testApply(): void
    {
        $obj = new class {
            public string $name;
            public string $version;
            public function updateVersion()
            {
                $this->version = '2.0';
            }
        };

        Config::gi()->set('app', ['name' => 'MyApp', 'updateVersion']);
        Config::gi()->apply('app', $obj);
        $this->assertEquals('MyApp', $obj->name);
        $this->assertEquals('2.0', $obj->version);
    }
}
