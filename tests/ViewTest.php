<?php

use Dragon\View;
use PHPUnit\Framework\TestCase;

/**
 * ViewTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class ViewTest extends TestCase
{
    private static string $viewsDir;

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

        self::$viewsDir = APP_PATH . DS . 'views';
        if (!is_dir(self::$viewsDir)) {
            mkdir(self::$viewsDir, 0777, true);
        }

        file_put_contents(self::$viewsDir . DS . 'test.phtml', 'hello view');
        file_put_contents(self::$viewsDir . DS . 'layout.phtml', '<layout><?= $content ?></layout>');
        file_put_contents(self::$viewsDir . DS . 'vars.phtml', '<?= $name ?>');

        $ref = new ReflectionClass(View::class);
        $ref->getProperty('instance')->setValue(null, null);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$viewsDir . DS . 'test.phtml');
        @unlink(self::$viewsDir . DS . 'layout.phtml');
        @unlink(self::$viewsDir . DS . 'vars.phtml');
        @rmdir(self::$viewsDir);
    }

    protected function setUp(): void
    {
        parent::setUp();

        View::$afterRender = null;
    }

    public function testSingleton(): void
    {
        $this->assertSame(View::gi(), View::gi());
    }

    public function testView(): void
    {
        $view = new View();

        $this->assertTrue($view->view('test'));
        $this->assertFalse($view->view('nonexistent'));
    }

    public function testGetView(): void
    {
        $view = new View();

        $this->assertSame('', $view->getView());

        $view->view('test');
        $this->assertStringEndsWith('test.phtml', $view->getView());
    }

    public function testLayout(): void
    {
        $view = new View();

        $this->assertTrue($view->layout('layout'));
        $this->assertFalse($view->layout('nonexistent'));
    }

    public function testGetLayout(): void
    {
        $view = new View();

        $this->assertSame('', $view->getLayout());

        $view->layout('layout');
        $this->assertStringEndsWith('layout.phtml', $view->getLayout());
    }

    public function testSet(): void
    {
        $view = new View();

        $this->assertSame($view, $view->set('key', 'value'));
    }

    public function testVars(): void
    {
        $view = new View();

        $this->assertSame($view, $view->vars(['a' => 1, 'b' => 2]));
    }

    public function testRenderEmpty(): void
    {
        $view = new View();

        $this->assertSame('', $view->render());
    }

    public function testRender(): void
    {
        $view = new View('test');

        $this->assertSame('hello view', $view->render());
    }

    public function testRenderVars(): void
    {
        $view = new View('vars', ['name' => 'Dragon']);

        $this->assertSame('Dragon', $view->render());
    }

    public function testRenderLayout(): void
    {
        $view = new View('test', [], 'layout');

        $this->assertSame('<layout>hello view</layout>', $view->render());
    }

    public function testAfterRender(): void
    {
        View::$afterRender = fn(string $file, string $content) => strtoupper($content);

        $view = new View('test');

        $this->assertSame('HELLO VIEW', $view->render());
    }

    public function testConstructor(): void
    {
        $view = new View('test', ['name' => 'Dragon'], 'layout');

        $this->assertStringEndsWith('test.phtml', $view->getView());
        $this->assertStringEndsWith('layout.phtml', $view->getLayout());
    }

    public function testViewNull(): void
    {
        $view = new View('test');
        $view->view(null);

        $this->assertSame('', $view->getView());
    }
}
