<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\ResponseDecoder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResponseDecoderTest extends TestCase
{
    private ResponseDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new ResponseDecoder();
    }

    #[Test]
    public function decodeValidJson(): void
    {
        $result = $this->decoder->decode(200, '{"ok": true}', 'GET', '/me');
        self::assertSame(['ok' => true], $result);
    }

    #[Test]
    public function decodeEmptyJsonReturnsEmptyArray(): void
    {
        $result = $this->decoder->decode(200, 'null', 'GET', '/me');
        self::assertSame([], $result);
    }

    #[Test]
    public function decodeInvalidJsonThrows(): void
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(200, 'NOT_JSON{{{', 'GET', '/me');
    }

    #[Test]
    public function decode400Throws(): void
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(400, '{"message": "Bad request"}', 'POST', '/messages');
    }

    #[Test]
    public function decode401Throws(): void
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(401, '{"message": "Unauthorized"}', 'GET', '/me');
    }

    #[Test]
    public function decode404Throws(): void
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(404, '{"message": "Not found"}', 'GET', '/chats/999');
    }

    #[Test]
    public function decode429Throws(): void
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(429, '{"message": "Rate limit"}', 'POST', '/messages');
    }

    #[Test]
    public function decode503Throws(): void
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(503, '{"message": "Service unavailable"}', 'GET', '/me');
    }

    #[Test]
    public function errorContainsStatusCode(): void
    {
        try {
            $this->decoder->decode(401, '{"message": "Bad token"}', 'GET', '/me');
            self::fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            self::assertSame(401, $e->getStatusCode());
            self::assertSame('Bad token', $e->getDescription());
        }
    }

    #[Test]
    public function errorWithErrorCode(): void
    {
        try {
            $this->decoder->decode(400, '{"message": "Invalid", "code": "ERR_001"}', 'POST', '/messages');
            self::fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            self::assertSame('ERR_001', $e->getErrorCode());
        }
    }

    #[Test]
    public function decodeArrayResponse(): void
    {
        $json = '{"chats": [{"id": 1}, {"id": 2}], "marker": "abc"}';
        $result = $this->decoder->decode(200, $json, 'GET', '/chats');
        self::assertCount(2, $result['chats']);
        self::assertSame('abc', $result['marker']);
    }

    #[Test]
    public function decode502Throws(): void
    {
        $this->expectException(MaxApiException::class);
        $this->decoder->decode(502, '{"message": "Bad Gateway"}', 'GET', '/me');
    }

    #[Test]
    public function decode204ReturnsEmptyArray(): void
    {
        $result = $this->decoder->decode(204, '', 'DELETE', '/messages/123');
        self::assertSame([], $result);
    }

    #[Test]
    public function decode500WithBodyPreservesDetails(): void
    {
        try {
            $this->decoder->decode(500, '{"message": "Internal error", "code": "SERVER_ERR"}', 'POST', '/messages');
            self::fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            self::assertSame(500, $e->getStatusCode());
            self::assertSame('Internal error', $e->getDescription());
            self::assertSame('SERVER_ERR', $e->getErrorCode());
        }
    }

    #[Test]
    public function decode403Throws(): void
    {
        try {
            $this->decoder->decode(403, '{"message": "Forbidden"}', 'POST', '/chats');
            self::fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            self::assertSame(403, $e->getStatusCode());
            self::assertSame('Forbidden', $e->getDescription());
        }
    }
}
