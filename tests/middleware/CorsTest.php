<?php

use Dragon\http\Request;
use Dragon\http\Response;
use Dragon\middleware\Cors;
use PHPUnit\Framework\TestCase;

/**
 * CorsTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class CorsTest extends TestCase
{
    private array $serverBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBackup = $_SERVER;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        parent::tearDown();
    }

    /**
     * It represents the next middleware handle or controller action that would be called if the middleware calls $next.
     */
    private function makeNext(): callable
    {
        return function (Response $response): Response {
            return $response;
        };
    }

    // -------------------------------------------------------------------------
    // Default CORS headers
    // -------------------------------------------------------------------------

    public function testDefaultAllowOriginHeaderIsWildcard(): void
    {
        $cors = new Cors();
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('*', $response->getHeaders()['Access-Control-Allow-Origin']);
    }

    public function testDefaultAllowMethodsHeader(): void
    {
        $cors = new Cors();
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(
            'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            $response->getHeaders()['Access-Control-Allow-Methods']
        );
    }

    public function testDefaultAllowHeadersHeader(): void
    {
        $cors = new Cors();
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(
            'Content-Type, Authorization',
            $response->getHeaders()['Access-Control-Allow-Headers']
        );
    }

    public function testDefaultMaxAgeHeader(): void
    {
        $cors = new Cors();
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('86400', $response->getHeaders()['Access-Control-Max-Age']);
    }

    // -------------------------------------------------------------------------
    // Custom constructor arguments
    // -------------------------------------------------------------------------

    public function testCustomAllowedOrigin(): void
    {
        $cors = new Cors(allowedOrigin: 'https://example.com');
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('https://example.com', $response->getHeaders()['Access-Control-Allow-Origin']);
    }

    public function testCustomAllowedMethods(): void
    {
        $cors = new Cors(allowedMethods: ['GET', 'POST']);
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('GET, POST', $response->getHeaders()['Access-Control-Allow-Methods']);
    }

    public function testCustomAllowedHeaders(): void
    {
        $cors = new Cors(allowedHeaders: ['X-Api-Key']);
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('X-Api-Key', $response->getHeaders()['Access-Control-Allow-Headers']);
    }

    public function testCustomMaxAge(): void
    {
        $cors = new Cors(maxAge: 3600);
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('3600', $response->getHeaders()['Access-Control-Max-Age']);
    }

    // -------------------------------------------------------------------------
    // OPTIONS preflight short-circuit
    // -------------------------------------------------------------------------

    public function testOptionsRequestReturns204(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $cors = new Cors();
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(204, $response->getStatus());
    }

    public function testOptionsRequestDoesNotCallNext(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $nextCalled = false;
        $next = function (Response $response) use (&$nextCalled): Response {
            $nextCalled = true;
            return $response;
        };

        (new Cors())->handle(new Request(), new Response(), $next);

        $this->assertFalse($nextCalled);
    }

    public function testOptionsRequestStillSetsCorsHeaders(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $cors = new Cors(allowedOrigin: 'https://example.com');
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('https://example.com', $response->getHeaders()['Access-Control-Allow-Origin']);
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $response->getHeaders());
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $response->getHeaders());
        $this->assertArrayHasKey('Access-Control-Max-Age', $response->getHeaders());
    }

    // -------------------------------------------------------------------------
    // Non-OPTIONS requests delegate to $next
    // -------------------------------------------------------------------------

    public function testNonOptionsRequestCallsNext(): void
    {
        $nextCalled = false;
        $next = function (Response $response) use (&$nextCalled): Response {
            $nextCalled = true;
            return $response;
        };

        (new Cors())->handle(new Request(), new Response(), $next);

        $this->assertTrue($nextCalled);
    }

    public function testNonOptionsRequestReturnsResponseFromNext(): void
    {
        $expected = (new Response())->status(201)->body('ok');
        $next = fn(Response $response): Response => $expected;

        $result = (new Cors())->handle(new Request(), new Response(), $next);

        $this->assertSame($expected, $result);
    }

    public function testNonOptionsRequestDoesNotReturn204(): void
    {
        $cors = new Cors();
        $response = $cors->handle(new Request(), new Response(), $this->makeNext());

        $this->assertNotSame(204, $response->getStatus());
    }
}
