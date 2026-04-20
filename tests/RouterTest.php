<?php

namespace controllers {

    class RouterTestHome
    {
        public function index() {}
        public function about() {}
        public function profile() {}
        public function show() {}
        public function view() {}
        public function list() {}
    }
}

namespace {

    use Dragon\Config;
    use Dragon\Router;
    use PHPUnit\Framework\TestCase;

    /**
     * RouterTest
     *
     * @author Michal Stefanak
     * @link https://github.com/stefanak-michal/DragonCore
     */
    class RouterTest extends TestCase
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
            if (!defined('IS_CLI')) {
                define('IS_CLI', false);
            }
            if (!defined('DRAGON_DEBUG')) {
                define('DRAGON_DEBUG', false);
            }

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
            Config::gi()->set('defaultController', 'controllers\RouterTestHome');
            Config::gi()->set('defaultMethod', 'index');
            Config::gi()->set('routes', []);

            $ref = new ReflectionClass(Router::class);
            $ref->getProperty('instance')->setValue(null, null);
        }

        protected function tearDown(): void
        {
            $_SERVER = $this->serverBackup;
            parent::tearDown();
        }

        public function testSingleton(): void
        {
            $this->assertSame(Router::gi(), Router::gi());
        }

        public function testGetHost(): void
        {
            $this->assertSame('http://example.test/', Router::gi()->getHost());
        }

        public function testGetHostTrailingSlash(): void
        {
            $this->assertSame('http://example.test/', Router::gi()->getHost());
        }

        public function testSetSecureHostToTrue(): void
        {
            $router = Router::gi();
            $router->setSecureHost(true);
            $this->assertStringStartsWith('https://', $router->getHost());
        }

        public function testSetSecureHostToTrueIdempotent(): void
        {
            $router = Router::gi();
            $router->setSecureHost(true);
            $router->setSecureHost(true);
            $this->assertStringStartsWith('https://', $router->getHost());
            $this->assertSame(1, substr_count($router->getHost(), 'https'));
        }

        public function testSetSecureHostToFalse(): void
        {
            Config::gi()->set('project_host', 'https://example.test');

            $router = Router::gi();
            $router->setSecureHost(false);

            $this->assertStringStartsWith('http://', $router->getHost());
            $this->assertStringNotContainsString('https://', $router->getHost());
        }

        public function testHomepage(): void
        {
            $this->assertSame('http://example.test/', Router::gi()->homepage());
        }

        public function testHomepageWithQuery(): void
        {
            $url = Router::gi()->homepage(['page' => '2', 'sort' => 'asc']);
            $this->assertStringContainsString('?', $url);
            $this->assertStringContainsString('page=2', $url);
            $this->assertStringContainsString('sort=asc', $url);
        }

        public function testUrlThrowsOnMissingController(): void
        {
            $this->expectException(\InvalidArgumentException::class);
            Router::gi()->url('NonExistentClass', 'index');
        }

        public function testUrlFallbackNoMask(): void
        {
            $url = Router::gi()->url('controllers\RouterTestHome', 'index');
            $this->assertSame('http://example.test/controllers/RouterTestHome/index', $url);
        }

        public function testUrlFallbackWithVars(): void
        {
            $url = Router::gi()->url('controllers\RouterTestHome', 'show', [42, 'hello']);
            $this->assertStringContainsString('controllers/RouterTestHome/show', $url);
            $this->assertStringContainsString('42', $url);
            $this->assertStringContainsString('hello', $url);
        }

        public function testUrlWithIntMask(): void
        {
            Config::gi()->set('routes', [
                'user/%i/profile' => 'controllers/RouterTestHome/profile',
            ]);

            $url = Router::gi()->url('controllers\RouterTestHome', 'profile', [7]);
            $this->assertSame('http://example.test/user/7/profile', $url);
        }

        public function testUrlWithStringMask(): void
        {
            Config::gi()->set('routes', [
                'post/%s/view' => 'controllers/RouterTestHome/view',
            ]);

            $url = Router::gi()->url('controllers\RouterTestHome', 'view', ['my-post']);
            $this->assertSame('http://example.test/post/my-post/view', $url);
        }

        public function testUrlWithFloatMask(): void
        {
            Config::gi()->set('routes', [
                'price/%d' => 'controllers/RouterTestHome/list',
            ]);

            $url = Router::gi()->url('controllers\RouterTestHome', 'list', [3.14]);
            $this->assertSame('http://example.test/price/3.14', $url);
        }

        public function testUrlWithQueryParams(): void
        {
            $url = Router::gi()->url('controllers\RouterTestHome', 'index', [], ['sort' => 'desc']);
            $this->assertStringContainsString('?sort=desc', $url);
        }

        public function testUrlVarCountMismatchFallsBack(): void
        {
            Config::gi()->set('routes', [
                'user/%i/profile' => 'controllers/RouterTestHome/profile',
            ]);

            // Passing 0 vars while mask expects 1 — should fall back to default URL format
            $url = Router::gi()->url('controllers\RouterTestHome', 'profile');
            $this->assertStringContainsString('controllers/RouterTestHome/profile', $url);
            $this->assertStringNotContainsString('user/', $url);
        }

        public function testFindRouteSimple(): void
        {
            Config::gi()->set('routes', [
                'about' => 'controllers/RouterTestHome/about',
            ]);

            $result = Router::gi()->findRoute('about');
            $this->assertSame('about', $result['method']);
            $this->assertSame('\\controllers\\RouterTestHome', $result['controller']);
            $this->assertSame([], $result['vars']);
        }

        public function testFindRouteWithInt(): void
        {
            Config::gi()->set('routes', [
                'user/%i' => 'controllers/RouterTestHome/show',
            ]);

            $result = Router::gi()->findRoute('user/42');
            $this->assertSame('show', $result['method']);
            $this->assertSame([42], $result['vars']);
            $this->assertIsInt($result['vars'][0]);
        }

        public function testFindRouteWithString(): void
        {
            Config::gi()->set('routes', [
                'post/%s' => 'controllers/RouterTestHome/view',
            ]);

            $result = Router::gi()->findRoute('post/hello-world');
            $this->assertSame('view', $result['method']);
            $this->assertSame(['hello-world'], $result['vars']);
            $this->assertIsString($result['vars'][0]);
        }

        public function testFindRouteWithFloat(): void
        {
            Config::gi()->set('routes', [
                'price/%d' => 'controllers/RouterTestHome/list',
            ]);

            $result = Router::gi()->findRoute('price/3.14');
            $this->assertSame('list', $result['method']);
            $this->assertSame([3.14], $result['vars']);
            $this->assertIsFloat($result['vars'][0]);
        }

        public function testFindRouteWithMultipleVars(): void
        {
            Config::gi()->set('routes', [
                'category/%s/item/%i' => 'controllers/RouterTestHome/show',
            ]);

            $result = Router::gi()->findRoute('category/books/item/5');
            $this->assertSame('show', $result['method']);
            $this->assertSame(['books', 5], $result['vars']);
        }

        public function testFindRouteNoMatch(): void
        {
            Config::gi()->set('routes', [
                'home' => 'controllers/RouterTestHome/index',
            ]);

            $result = Router::gi()->findRoute('no/such/path');
            $this->assertEmpty($result);
        }

        public function testFindRouteIsCaseInsensitive(): void
        {
            Config::gi()->set('routes', [
                'About' => 'controllers/RouterTestHome/about',
            ]);

            $result = Router::gi()->findRoute('about');
            $this->assertSame('about', $result['method']);
        }

        public function testResolveDefaultWhenPathEmpty(): void
        {
            $_SERVER['REQUEST_URI'] = '/';
            $result = Router::gi()->resolve();

            $this->assertSame('index', $result['method']);
            $this->assertStringStartsWith('\\', $result['controller']);
            $this->assertStringContainsString('controllers', $result['controller']);
            $this->assertSame([], $result['vars']);
        }

        public function testResolveWithMatchedRoute(): void
        {
            Config::gi()->set('routes', [
                'about' => 'controllers/RouterTestHome/about',
            ]);

            $_SERVER['REQUEST_URI'] = '/about';
            $result = Router::gi()->resolve();
            $this->assertSame('about', $result['method']);
        }

        public function testResolveWithRouteVars(): void
        {
            Config::gi()->set('routes', [
                'user/%i' => 'controllers/RouterTestHome/show',
            ]);

            $_SERVER['REQUEST_URI'] = '/user/99';
            $result = Router::gi()->resolve();
            $this->assertSame('show', $result['method']);
            $this->assertSame([99], $result['vars']);
        }

        public function testResolveFallbackToVarsWhenNoRouteMatches(): void
        {
            $_SERVER['REQUEST_URI'] = '/foo/bar/baz';
            $result = Router::gi()->resolve();
            $this->assertSame(['foo', 'bar', 'baz'], $result['vars']);
        }

        public function testResolveStripsQueryString(): void
        {
            Config::gi()->set('routes', [
                'about' => 'controllers/RouterTestHome/about',
            ]);

            $_SERVER['REQUEST_URI'] = '/about?foo=bar';
            $result = Router::gi()->resolve();
            $this->assertSame('about', $result['method']);
        }

        public function testCurrentWithoutQueryParams(): void
        {
            $_SERVER['SERVER_PORT'] = 80;
            $_SERVER['SERVER_NAME'] = 'example.test';
            $_SERVER['REQUEST_URI'] = '/test?foo=bar';

            $url = Router::gi()->current(false);
            $this->assertSame('http://example.test/test', $url);
        }

        public function testCurrentWithQueryParams(): void
        {
            $_SERVER['SERVER_PORT'] = 80;
            $_SERVER['SERVER_NAME'] = 'example.test';
            $_SERVER['REQUEST_URI'] = '/test?foo=bar';

            $url = Router::gi()->current(true);
            $this->assertStringContainsString('foo=bar', $url);
            $this->assertStringContainsString('/test', $url);
        }

        public function testCurrentHttps(): void
        {
            $_SERVER['SERVER_PORT'] = 443;
            $_SERVER['SERVER_NAME'] = 'example.test';
            $_SERVER['REQUEST_URI'] = '/secure';

            $url = Router::gi()->current();
            $this->assertStringStartsWith('https://', $url);
            $this->assertStringContainsString('/secure', $url);
        }

        public function testCurrentNonStandardPort(): void
        {
            $_SERVER['SERVER_PORT'] = 8080;
            $_SERVER['SERVER_NAME'] = 'example.test';
            $_SERVER['REQUEST_URI'] = '/page';

            $url = Router::gi()->current();
            $this->assertStringContainsString(':8080', $url);
        }
    }
}
