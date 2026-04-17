<?php

namespace controllers {

    use Dragon\controllers\IController;
    use Dragon\http\Request;
    use Dragon\http\Response;

    class AppTestHome implements IController
    {
        public function middleware(): array
        {
            return [];
        }

        public function index(Request $request, Response $response): Response
        {
            return $response->body('hello from app test');
        }
    }
}

namespace {

    use Dragon\Application;
    use Dragon\Config;
    use Dragon\Router;
    use PHPUnit\Framework\TestCase;

    /**
     * ApplicationTest
     *
     * @author Michal Stefanak
     * @link https://github.com/stefanak-michal/DragonCore
     */
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
            Config::gi()->set('defaultController', 'AppTestHome');
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
            (new Application())->run();
            $output = ob_get_clean();

            $this->assertSame('hello from app test', $output);
            $this->assertEquals('controllers\AppTestHome', Application::$controller::class);
            $this->assertEquals('index', Application::$method);
        }
    }
}
