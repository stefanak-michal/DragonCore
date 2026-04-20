<?php

use Dragon\http\Response;
use PHPUnit\Framework\TestCase;

/**
 * ResponseTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonCore
 */
class ResponseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('DRAGON_DEBUG')) {
            define('DRAGON_DEBUG', false);
        }
    }

    // -------------------------------------------------------------------------
    // Default state
    // -------------------------------------------------------------------------

    public function testDefaultStatusIs200(): void
    {
        $response = new Response();
        $this->assertSame(200, $response->getStatus());
    }

    public function testDefaultBodyIsEmpty(): void
    {
        $response = new Response();
        $this->assertSame('', $response->getBody());
    }

    public function testDefaultHeadersAreEmpty(): void
    {
        $response = new Response();
        $this->assertSame([], $response->getHeaders());
    }

    // -------------------------------------------------------------------------
    // status()
    // -------------------------------------------------------------------------

    public function testStatusSetsCode(): void
    {
        $response = new Response();
        $response->status(404);
        $this->assertSame(404, $response->getStatus());
    }

    public function testStatusReturnsStatic(): void
    {
        $response = new Response();
        $this->assertSame($response, $response->status(201));
    }

    public function testStatusIsChainable(): void
    {
        $response = (new Response())->status(500)->status(503);
        $this->assertSame(503, $response->getStatus());
    }

    // -------------------------------------------------------------------------
    // header()
    // -------------------------------------------------------------------------

    public function testHeaderSetsValue(): void
    {
        $response = new Response();
        $response->header('X-Custom', 'value');
        $this->assertSame(['X-Custom' => 'value'], $response->getHeaders());
    }

    public function testHeaderOverwritesSameName(): void
    {
        $response = new Response();
        $response->header('X-Foo', 'first');
        $response->header('X-Foo', 'second');
        $this->assertSame('second', $response->getHeaders()['X-Foo']);
    }

    public function testMultipleHeadersAreStored(): void
    {
        $response = new Response();
        $response->header('Content-Type', 'text/plain');
        $response->header('X-Request-Id', 'abc123');
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('X-Request-Id', $headers);
        $this->assertCount(2, $headers);
    }

    public function testHeaderReturnsStatic(): void
    {
        $response = new Response();
        $this->assertSame($response, $response->header('X-Foo', 'bar'));
    }

    // -------------------------------------------------------------------------
    // body()
    // -------------------------------------------------------------------------

    public function testBodySetsContent(): void
    {
        $response = new Response();
        $response->body('hello world');
        $this->assertSame('hello world', $response->getBody());
    }

    public function testBodyOverwritesPreviousContent(): void
    {
        $response = new Response();
        $response->body('first');
        $response->body('second');
        $this->assertSame('second', $response->getBody());
    }

    public function testBodyDoesNotSetContentTypeHeader(): void
    {
        $response = new Response();
        $response->body('<p>hi</p>');
        $this->assertArrayNotHasKey('Content-Type', $response->getHeaders());
    }

    public function testBodyReturnsStatic(): void
    {
        $response = new Response();
        $this->assertSame($response, $response->body('test'));
    }

    // -------------------------------------------------------------------------
    // html()
    // -------------------------------------------------------------------------

    public function testHtmlSetsBody(): void
    {
        $response = new Response();
        $response->html('<h1>Hello</h1>');
        $this->assertSame('<h1>Hello</h1>', $response->getBody());
    }

    public function testHtmlSetsContentTypeHeader(): void
    {
        $response = new Response();
        $response->html('<p>test</p>');
        $this->assertSame('text/html', $response->getHeaders()['Content-Type']);
    }

    public function testHtmlReturnsStatic(): void
    {
        $response = new Response();
        $this->assertSame($response, $response->html(''));
    }

    // -------------------------------------------------------------------------
    // json()
    // -------------------------------------------------------------------------

    public function testJsonEncodesArray(): void
    {
        $response = new Response();
        $response->json(['foo' => 'bar', 'num' => 42]);
        $this->assertSame('{"foo":"bar","num":42}', $response->getBody());
    }

    public function testJsonEncodesObject(): void
    {
        $response = new Response();
        $obj = new stdClass();
        $obj->key = 'value';
        $response->json($obj);
        $this->assertSame('{"key":"value"}', $response->getBody());
    }

    public function testJsonEncodesPrimitives(): void
    {
        $response = new Response();
        $response->json(true);
        $this->assertSame('true', $response->getBody());
    }

    public function testJsonSetsContentTypeHeader(): void
    {
        $response = new Response();
        $response->json([]);
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
    }

    public function testJsonWithPrettyPrintFlag(): void
    {
        $response = new Response();
        $response->json(['a' => 1], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $this->assertStringContainsString("\n", $response->getBody());
    }

    public function testJsonThrowsOnUnencodableValue(): void
    {
        $this->expectException(\JsonException::class);
        $response = new Response();
        $response->json(INF, JSON_THROW_ON_ERROR);
    }

    public function testJsonReturnsStatic(): void
    {
        $response = new Response();
        $this->assertSame($response, $response->json([]));
    }

    // -------------------------------------------------------------------------
    // Fluent interface
    // -------------------------------------------------------------------------

    public function testFluentChainingCombinesAllSetters(): void
    {
        $response = (new Response())
            ->status(201)
            ->header('X-Powered-By', 'Dragon')
            ->html('<p>created</p>');

        $this->assertSame(201, $response->getStatus());
        $this->assertSame('<p>created</p>', $response->getBody());
        $this->assertSame('text/html', $response->getHeaders()['Content-Type']);
        $this->assertSame('Dragon', $response->getHeaders()['X-Powered-By']);
    }

    // -------------------------------------------------------------------------
    // send()
    // -------------------------------------------------------------------------

    public function testSendOutputsBody(): void
    {
        $response = (new Response())->body('output content');
        ob_start();
        $response->send();
        $output = ob_get_clean();
        $this->assertSame('output content', $output);
    }

    public function testSendOutputsJsonBody(): void
    {
        $response = (new Response())->json(['ok' => true]);
        ob_start();
        $response->send();
        $output = ob_get_clean();
        $this->assertSame('{"ok":true}', $output);
    }

    public function testSendWithEmptyBodyOutputsNothing(): void
    {
        $response = new Response();
        ob_start();
        $response->send();
        $output = ob_get_clean();
        $this->assertSame('', $output);
    }
}
