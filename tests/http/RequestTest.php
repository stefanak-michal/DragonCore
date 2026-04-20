<?php

use Dragon\http\Request;
use Dragon\http\RequestMethod;
use PHPUnit\Framework\TestCase;

/**
 * RequestTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class RequestTest extends TestCase
{
    private array $serverBackup;
    private array $getBackup;
    private array $postBackup;
    private array $cookieBackup;
    private array $filesBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBackup = $_SERVER;
        $this->getBackup = $_GET;
        $this->postBackup = $_POST;
        $this->cookieBackup = $_COOKIE;
        $this->filesBackup = $_FILES;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_GET = $this->getBackup;
        $_POST = $this->postBackup;
        $_COOKIE = $this->cookieBackup;
        $_FILES = $this->filesBackup;

        parent::tearDown();
    }

    public function testDefaultMethodIsGet(): void
    {
        $request = new Request();
        $this->assertSame(RequestMethod::GET, $request->method);
    }

    public function testMethodIsResolvedFromServer(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $this->assertSame(RequestMethod::POST, $request->method);
    }

    public function testMethodIsCaseInsensitive(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'delete';
        $request = new Request();
        $this->assertSame(RequestMethod::DELETE, $request->method);
    }

    public function testUnknownMethodFallsBackToGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'INVALID';
        $request = new Request();
        $this->assertSame(RequestMethod::GET, $request->method);
    }

    public function testUriIsReadFromServer(): void
    {
        $_SERVER['REQUEST_URI'] = '/foo/bar?baz=1';
        $request = new Request();
        $this->assertSame('/foo/bar?baz=1', $request->uri);
    }

    public function testDefaultUriIsSlash(): void
    {
        unset($_SERVER['REQUEST_URI']);
        $request = new Request();
        $this->assertSame('/', $request->uri);
    }

    public function testGetIsPopulatedFromSuperGlobal(): void
    {
        $_GET = ['foo' => 'bar', 'page' => '2'];
        $request = new Request();
        $this->assertSame(['foo' => 'bar', 'page' => '2'], $request->get);
    }

    public function testPostIsPopulatedFromSuperGlobal(): void
    {
        $_POST = ['name' => 'Alice', 'email' => 'alice@example.com'];
        $request = new Request();
        $this->assertSame(['name' => 'Alice', 'email' => 'alice@example.com'], $request->post);
    }

    public function testCookiesArePopulatedFromSuperGlobal(): void
    {
        $_COOKIE = ['session' => 'abc123'];
        $request = new Request();
        $this->assertSame(['session' => 'abc123'], $request->cookies);
    }

    public function testFilesArePopulatedFromSuperGlobal(): void
    {
        $_FILES = ['upload' => ['name' => 'file.txt', 'size' => 100]];
        $request = new Request();
        $this->assertSame(['upload' => ['name' => 'file.txt', 'size' => 100]], $request->files);
    }

    public function testParamsDefaultToEmptyArray(): void
    {
        $request = new Request();
        $this->assertSame([], $request->params);
    }

    public function testParamsArePassedThroughConstructor(): void
    {
        $request = new Request([42, 'slug']);
        $this->assertSame([42, 'slug'], $request->params);
    }

    public function testInputReturnsPostValue(): void
    {
        $_POST = ['key' => 'from-post'];
        $_GET = ['key' => 'from-get'];
        $request = new Request();
        $this->assertSame('from-post', $request->input('key'));
    }

    public function testInputFallsBackToGet(): void
    {
        $_GET = ['key' => 'from-get'];
        $request = new Request();
        $this->assertSame('from-get', $request->input('key'));
    }

    public function testInputReturnsDefaultWhenMissing(): void
    {
        $request = new Request();
        $this->assertNull($request->input('missing'));
        $this->assertSame('default', $request->input('missing', 'default'));
    }

    public function testJsonReturnsDecodedArray(): void
    {
        // We cannot inject php://input directly, so we test via a subclass
        $request = new class extends Request {
            public function __construct()
            {
                parent::__construct();
                $this->body = '{"foo":"bar","num":1}';
            }
        };
        $result = $request->json();
        $this->assertSame(['foo' => 'bar', 'num' => 1], $result);
    }

    public function testJsonAsObject(): void
    {
        $request = new class extends Request {
            public function __construct()
            {
                parent::__construct();
                $this->body = '{"foo":"bar"}';
            }
        };
        $result = $request->json(false);
        $this->assertIsObject($result);
        $this->assertSame('bar', $result->foo);
    }

    public function testJsonReturnsNullOnEmptyBody(): void
    {
        $request = new class extends Request {
            public function __construct()
            {
                parent::__construct();
                $this->body = '';
            }
        };
        $this->assertNull($request->json());
    }

    public function testJsonReturnsNullOnInvalidJson(): void
    {
        $request = new class extends Request {
            public function __construct()
            {
                parent::__construct();
                $this->body = '{not valid json}';
            }
        };
        $this->assertNull($request->json());
    }

    public function testHeaderNormalisedToTitleCase(): void
    {
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate';
        $request = new Request();
        $this->assertSame('gzip, deflate', $request->header('Accept-Encoding'));
    }

    public function testHeaderIsCaseInsensitive(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';
        $request = new Request();
        $this->assertSame('en-US', $request->header('accept-language'));
        $this->assertSame('en-US', $request->header('ACCEPT-LANGUAGE'));
    }

    public function testHeaderReturnsNullWhenMissing(): void
    {
        $request = new Request();
        $this->assertNull($request->header('X-Non-Existent'));
    }

    public function testContentTypeHeaderFromServer(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $request = new Request();
        $this->assertSame('application/json', $request->header('Content-Type'));
    }

    public function testIsAjaxReturnsTrueForXmlHttpRequest(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $request = new Request();
        $this->assertTrue($request->isAjax());
    }

    public function testIsAjaxIsCaseInsensitive(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $request = new Request();
        $this->assertTrue($request->isAjax());
    }

    public function testIsAjaxReturnsFalseWhenHeaderMissing(): void
    {
        $request = new Request();
        $this->assertFalse($request->isAjax());
    }

    public function testIsAjaxReturnsFalseForOtherHeaderValues(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'fetch';
        $request = new Request();
        $this->assertFalse($request->isAjax());
    }

    public function testIsMethodWithEnum(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $this->assertTrue($request->isMethod(RequestMethod::POST));
        $this->assertFalse($request->isMethod(RequestMethod::GET));
    }

    public function testIsMethodWithString(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $request = new Request();
        $this->assertTrue($request->isMethod('put'));
        $this->assertTrue($request->isMethod('PUT'));
        $this->assertFalse($request->isMethod('POST'));
    }

    public function testIpFromRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR']);
        $request = new Request();
        $this->assertSame('127.0.0.1', $request->ip());
    }

    public function testIpPrefersHttpClientIp(): void
    {
        $_SERVER['HTTP_CLIENT_IP'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.2';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.3';
        $request = new Request();
        $this->assertSame('10.0.0.1', $request->ip());
    }

    public function testIpPrefersXForwardedForOverRemoteAddr(): void
    {
        unset($_SERVER['HTTP_CLIENT_IP']);
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.2';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.3';
        $request = new Request();
        $this->assertSame('10.0.0.2', $request->ip());
    }

    public function testServerIsPopulatedFromSuperGlobal(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CUSTOM_KEY'] = 'custom_value';
        $request = new Request();
        $this->assertSame('custom_value', $request->server['CUSTOM_KEY']);
    }

    public function testAllHttpMethodsAreResolvable(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
        foreach ($methods as $method) {
            $_SERVER['REQUEST_METHOD'] = $method;
            $request = new Request();
            $this->assertSame($method, $request->method->value);
        }
    }
}
