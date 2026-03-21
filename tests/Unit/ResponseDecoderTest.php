<?php

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\ResponseDecoder;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для ResponseDecoder.
 */
class ResponseDecoderTest extends TestCase
{
    /** @var ResponseDecoder */
    private $decoder;

    protected function setUp(): void
    {
        $this->decoder = new ResponseDecoder();
    }

    public function testDecodeValidJson()
    {
        $result = $this->decoder->decode(200, '{"ok": true}', 'GET', '/me');
        $this->assertEquals(['ok' => true], $result);
    }

    public function testDecodeEmptyJsonReturnsEmptyArray()
    {
        $result = $this->decoder->decode(200, 'null', 'GET', '/me');
        $this->assertEquals([], $result);
    }

    public function testDecodeInvalidJsonThrows()
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(200, 'NOT_JSON{{{', 'GET', '/me');
    }

    public function testDecode400Throws()
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(400, '{"message": "Bad request"}', 'POST', '/messages');
    }

    public function testDecode401Throws()
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(401, '{"message": "Unauthorized"}', 'GET', '/me');
    }

    public function testDecode404Throws()
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(404, '{"message": "Not found"}', 'GET', '/chats/999');
    }

    public function testDecode429Throws()
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(429, '{"message": "Rate limit"}', 'POST', '/messages');
    }

    public function testDecode503Throws()
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(503, '{"message": "Service unavailable"}', 'GET', '/me');
    }

    public function testErrorContainsStatusCode()
    {
        try {
            $this->decoder->decode(401, '{"message": "Bad token"}', 'GET', '/me');
            $this->fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            $this->assertEquals('Bad token', $e->getDescription());
        }
    }

    public function testErrorWithErrorCode()
    {
        try {
            $this->decoder->decode(400, '{"message": "Invalid", "code": "ERR_001"}', 'POST', '/messages');
            $this->fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            $this->assertEquals('ERR_001', $e->getErrorCode());
        }
    }

    public function testDecodeArrayResponse()
    {
        $json = '{"chats": [{"id": 1}, {"id": 2}], "marker": "abc"}';
        $result = $this->decoder->decode(200, $json, 'GET', '/chats');
        $this->assertCount(2, $result['chats']);
        $this->assertEquals('abc', $result['marker']);
    }
}
