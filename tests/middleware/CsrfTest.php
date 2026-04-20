<?php

use Dragon\http\Request;
use Dragon\http\Response;
use Dragon\middleware\Csrf;
use PHPUnit\Framework\TestCase;

/**
 * CsrfTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class CsrfTest extends TestCase
{
    private array $serverBackup;
    private array $postBackup;
    private array $getBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBackup = $_SERVER;
        $this->postBackup = $_POST;
        $this->getBackup = $_GET;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_POST = [];
        $_GET = [];

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_unset();
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;
        $_GET = $this->getBackup;

        parent::tearDown();
    }

    private function makeNext(?bool &$called = null): callable
    {
        $called = false;
        return function (Response $response) use (&$called): Response {
            $called = true;
            return $response;
        };
    }

    // -------------------------------------------------------------------------
    // Safe methods (GET, OPTIONS, HEAD) — no validation
    // -------------------------------------------------------------------------

    public function testGetRequestPassesWithoutToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(200, $response->getStatus());
    }

    public function testGetRequestCallsNext(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $csrf = new Csrf();
        $csrf->handle(new Request(), new Response(), $this->makeNext($called));

        $this->assertTrue($called);
    }

    // -------------------------------------------------------------------------
    // State-changing methods without a valid token → 403
    // -------------------------------------------------------------------------

    public function testPostWithoutTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(403, $response->getStatus());
    }

    public function testPostWithWrongTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['_csrf_token'] = 'correct_token';
        $_POST['_csrf_token'] = 'wrong_token';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(403, $response->getStatus());
    }

    public function testPostWithEmptyStoredTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['_csrf_token'] = '';
        $_POST['_csrf_token'] = '';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(403, $response->getStatus());
    }

    public function test403ResponseBodyContainsMismatchMessage(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('CSRF token mismatch', $response->getBody());
    }

    public function testPutWithoutTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(403, $response->getStatus());
    }

    public function testPatchWithoutTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PATCH';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(403, $response->getStatus());
    }

    public function testDeleteWithoutTokenReturns403(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame(403, $response->getStatus());
    }

    // -------------------------------------------------------------------------
    // State-changing methods with valid token → passes
    // -------------------------------------------------------------------------

    public function testPostWithValidPostFieldTokenPasses(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['_csrf_token'] = 'valid_token_abc';
        $_POST['_csrf_token'] = 'valid_token_abc';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext($called));

        $this->assertSame(200, $response->getStatus());
        $this->assertTrue($called);
    }

    public function testPostWithValidHeaderTokenPasses(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'valid_header_token';
        $_SESSION['_csrf_token'] = 'valid_header_token';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext($called));

        $this->assertSame(200, $response->getStatus());
        $this->assertTrue($called);
    }

    public function testPostFieldTokenTakesPrecedenceOverHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['_csrf_token'] = 'correct_token';
        $_POST['_csrf_token'] = 'correct_token';
        // Header has a different (wrong) value — POST field wins
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'wrong_header_token';

        $csrf = new Csrf();
        $response = $csrf->handle(new Request(), new Response(), $this->makeNext($called));

        $this->assertSame(200, $response->getStatus());
        $this->assertTrue($called);
    }

    // -------------------------------------------------------------------------
    // Token generation on safe requests
    // -------------------------------------------------------------------------

    public function testTokenIsGeneratedInSessionOnGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SESSION['_csrf_token']);

        $csrf = new Csrf();
        $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertNotEmpty($_SESSION['_csrf_token']);
    }

    public function testTokenIsNotRegeneratedIfAlreadyPresent(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['_csrf_token'] = 'existing_token';

        $csrf = new Csrf();
        $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertSame('existing_token', $_SESSION['_csrf_token']);
    }

    public function testGeneratedTokenIs64HexCharacters(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SESSION['_csrf_token']);

        $csrf = new Csrf();
        $csrf->handle(new Request(), new Response(), $this->makeNext());

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $_SESSION['_csrf_token']);
    }

    // -------------------------------------------------------------------------
    // Static helpers: token() and field()
    // -------------------------------------------------------------------------

    public function testTokenReturnsStoredSessionToken(): void
    {
        $_SESSION['_csrf_token'] = 'my_static_token';

        $this->assertSame('my_static_token', Csrf::token());
    }

    public function testTokenReturnsEmptyStringWhenNotSet(): void
    {
        unset($_SESSION['_csrf_token']);

        $this->assertSame('', Csrf::token());
    }

    public function testFieldReturnsHiddenInputWithToken(): void
    {
        $_SESSION['_csrf_token'] = 'my_field_token';

        $html = Csrf::field();

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="_csrf_token"', $html);
        $this->assertStringContainsString('value="my_field_token"', $html);
    }
}
