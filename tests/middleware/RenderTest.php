<?php

namespace controllers {

    use Dragon\http\Request;
    use Dragon\http\Response;
    use Dragon\controllers\IController;

    class HomeCtrl implements IController
    {
        public function middleware(): array
        {
            return [];
        }

        public function myAction(Request $request, Response $response): Response
        {
            return $response;
        }
    }
}

namespace {

    use Dragon\Application;
    use Dragon\http\Request;
    use Dragon\http\Response;
    use Dragon\middleware\Render;
    use Dragon\View;
    use PHPUnit\Framework\TestCase;

    /**
     * RenderTest
     *
     * @author Michal Stefanak
     * @link https://github.com/stefanak-michal/DragonCore
     */
    class RenderTest extends TestCase
    {
        private static string $viewsDir;

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
            if (!defined('IS_WORKSPACE')) {
                define('IS_WORKSPACE', false);
            }

            self::$viewsDir = APP_PATH . DS . 'views';
        }

        protected function setUp(): void
        {
            parent::setUp();

            $this->serverBackup = $_SERVER;
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/';

            $ref = new ReflectionClass(View::class);
            $ref->getProperty('instance')->setValue(null, null);
            View::$afterRender = null;

            Application::$controller = new \controllers\HomeCtrl();
            Application::$method = 'myAction';

            $this->removeDirectory(self::$viewsDir);
        }

        protected function tearDown(): void
        {
            $this->removeDirectory(self::$viewsDir);
            $_SERVER = $this->serverBackup;

            parent::tearDown();
        }

        private function removeDirectory(string $dir): void
        {
            if (!file_exists($dir)) {
                return;
            }
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
            }
            rmdir($dir);
        }

        /**
         * Creates a view file at the given path relative to the views directory,
         * tracking it for cleanup in tearDown.
         */
        private function createViewFile(string $relativePath, string $content = 'view content'): void
        {
            $fullPath = self::$viewsDir . DS . str_replace('/', DS, $relativePath) . '.phtml';
            $dir = dirname($fullPath);
            mkdir($dir, 0777, true);

            file_put_contents($fullPath, $content);
        }

        private function makeNext(): callable
        {
            return function (Response $response): Response {
                return $response;
            };
        }

        // -------------------------------------------------------------------------
        // handle() — core behaviour
        // -------------------------------------------------------------------------

        public function testHandleCallsNext(): void
        {
            $nextCalled = false;
            $next = function (Response $response) use (&$nextCalled): Response {
                $nextCalled = true;
                return $response;
            };

            (new Render())->handle(new Request(), new Response(), $next);

            $this->assertTrue($nextCalled);
        }

        public function testHandleSetsContentTypeToHtml(): void
        {
            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('text/html', $response->getHeaders()['Content-Type']);
        }

        public function testHandleReturnsViewContent(): void
        {
            $this->createViewFile('controllers/HomeCtrl/myAction', 'hello from view');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('hello from view', $response->getBody());
        }

        public function testHandleWithNoMatchingViewRendersEmptyBody(): void
        {
            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('', $response->getBody());
        }

        public function testHandleCallsNextBeforeRendering(): void
        {
            $order = [];
            $this->createViewFile('controllers/HomeCtrl/myAction', 'view');

            View::$afterRender = function (string $file, string $content) use (&$order): string {
                $order[] = 'render';
                return $content;
            };

            $next = function (Response $response) use (&$order): Response {
                $order[] = 'next';
                return $response;
            };

            (new Render())->handle(new Request(), new Response(), $next);

            $this->assertSame(['next', 'render'], $order);
        }

        public function testHandleUsesResponseReturnedByNext(): void
        {
            $customResponse = new Response();
            $next = fn(Response $response): Response => $customResponse;

            $result = (new Render())->handle(new Request(), new Response(), $next);

            $this->assertSame($customResponse, $result);
        }

        public function testHandleDoesNotInjectDebugWhenNotWorkspace(): void
        {
            $this->createViewFile('controllers/HomeCtrl/myAction', '<html><body><p>content</p></body></html>');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('<html><body><p>content</p></body></html>', $response->getBody());
        }

        // -------------------------------------------------------------------------
        // trySetView() — path resolution variants
        // -------------------------------------------------------------------------

        public function testTrySetViewMatchesExactCasePath(): void
        {
            $this->createViewFile('controllers/HomeCtrl/myAction', 'exact case');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('exact case', $response->getBody());
        }

        public function testTrySetViewMatchesLowercaseControllerPath(): void
        {
            $this->createViewFile('controllers/homectrl/myAction', 'lowercase controller');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('lowercase controller', $response->getBody());
        }

        public function testTrySetViewMatchesFullLowercasePath(): void
        {
            $this->createViewFile('controllers/homectrl/myaction', 'full lowercase');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('full lowercase', $response->getBody());
        }

        public function testTrySetViewMatchesSnakeCaseControllerPath(): void
        {
            $this->createViewFile('controllers/home_ctrl/myAction', 'snake_case controller');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('snake_case controller', $response->getBody());
        }

        public function testTrySetViewMatchesSnakeCaseLowercaseMethodPath(): void
        {
            $this->createViewFile('controllers/home_ctrl/myaction', 'snake_case controller lowercase method');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('snake_case controller lowercase method', $response->getBody());
        }

        public function testTrySetViewMatchesFullSnakeCasePath(): void
        {
            $this->createViewFile('controllers/home_ctrl/my_action', 'full snake_case');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('full snake_case', $response->getBody());
        }

        public function testTrySetViewUsesFirstMatchingPath(): void
        {
            $this->createViewFile('controllers/HomeCtrl/myAction', 'first match');
            $this->createViewFile('controllers/home_ctrl/my_action', 'last match');

            $response = (new Render())->handle(new Request(), new Response(), $this->makeNext());

            $this->assertSame('first match', $response->getBody());
        }
    }
}
