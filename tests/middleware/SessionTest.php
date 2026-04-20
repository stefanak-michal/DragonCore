<?php

use Dragon\http\Request;
use Dragon\http\Response;
use Dragon\middleware\Session;
use PHPUnit\Framework\TestCase;

/**
 * SessionTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class SessionTest extends TestCase
{
    private array $serverBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBackup = $_SERVER;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $_SERVER = $this->serverBackup;

        parent::tearDown();
    }

    private function makeNext(): callable
    {
        return function (Response $response): Response {
            return $response;
        };
    }

    public function testHandleStartsSession(): void
    {
        $this->assertNotSame(PHP_SESSION_ACTIVE, session_status());

        $middleware = new Session();
        $middleware->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testHandleReturnsResponse(): void
    {
        $middleware = new Session();
        $response = new Response();
        $result = $middleware->handle(new Request(), $response, $this->makeNext());

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testHandleCallsNext(): void
    {
        $nextCalled = false;
        $next = function (Response $response) use (&$nextCalled): Response {
            $nextCalled = true;
            return $response;
        };

        $middleware = new Session();
        $middleware->handle(new Request(), new Response(), $next);

        $this->assertTrue($nextCalled);
    }

    public function testHandleDoesNotRestartAlreadyActiveSession(): void
    {
        session_start();
        $_SESSION['test_key'] = 'test_value';
        $sessionId = session_id();

        $middleware = new Session();
        $middleware->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame($sessionId, session_id());
        $this->assertSame('test_value', $_SESSION['test_key']);
    }

    public function testNextReceivesResponseObject(): void
    {
        $response = new Response();
        $receivedResponse = null;

        $next = function (Response $r) use (&$receivedResponse): Response {
            $receivedResponse = $r;
            return $r;
        };

        $middleware = new Session();
        $middleware->handle(new Request(), $response, $next);

        $this->assertSame($response, $receivedResponse);
    }
}
