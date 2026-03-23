<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit\Http;

use MaxBotSdk\Config;
use MaxBotSdk\Http\CurlHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для CurlHttpClient.
 *
 * Тестируем внутреннюю логику (buildUrl, buildHeaders, buildMultipart, cleanupTempFiles)
 * через рефлексию, поскольку cURL-вызовы невозможно мокать без интеграционного окружения.
 */
final class CurlHttpClientTest extends TestCase
{
    private Config $config;
    private CurlHttpClient $client;

    protected function setUp(): void
    {
        $this->config = new Config('test_token_123');
        $this->client = new CurlHttpClient($this->config);
    }

    // --- buildUrl ---

    #[Test]
    public function buildUrlRelativePath(): void
    {
        $result = $this->invokePrivate('buildUrl', ['/chats', []]);
        self::assertSame('https://platform-api.max.ru/chats', $result);
    }

    #[Test]
    public function buildUrlRelativePathWithLeadingSlash(): void
    {
        $result = $this->invokePrivate('buildUrl', ['chats', []]);
        self::assertSame('https://platform-api.max.ru/chats', $result);
    }

    #[Test]
    public function buildUrlAbsoluteUrl(): void
    {
        $result = $this->invokePrivate('buildUrl', ['https://upload.max.ru/file', []]);
        self::assertSame('https://upload.max.ru/file', $result);
    }

    #[Test]
    public function buildUrlAbsoluteHttpUrl(): void
    {
        $result = $this->invokePrivate('buildUrl', ['http://localhost:8080/test', []]);
        self::assertSame('http://localhost:8080/test', $result);
    }

    #[Test]
    public function buildUrlWithQueryParams(): void
    {
        $result = $this->invokePrivate('buildUrl', ['/chats', [
            'query' => ['count' => 10, 'marker' => 42],
        ]]);
        self::assertStringContainsString('count=10', $result);
        self::assertStringContainsString('marker=42', $result);
        self::assertStringStartsWith('https://platform-api.max.ru/chats?', $result);
    }

    #[Test]
    public function buildUrlWithEmptyQuery(): void
    {
        $result = $this->invokePrivate('buildUrl', ['/chats', ['query' => []]]);
        self::assertSame('https://platform-api.max.ru/chats', $result);
    }

    #[Test]
    public function buildUrlWithExistingQueryString(): void
    {
        $result = $this->invokePrivate('buildUrl', ['https://example.com/path?existing=1', [
            'query' => ['new' => 'value'],
        ]]);
        self::assertStringContainsString('existing=1', $result);
        self::assertStringContainsString('&new=value', $result);
    }

    #[Test]
    public function buildUrlCustomBaseUrl(): void
    {
        $client = new CurlHttpClient($this->config, null, 'https://custom-api.example.com/');
        $ref = new \ReflectionClass($client);
        $method = $ref->getMethod('buildUrl');
        $method->setAccessible(true);
        $result = $method->invoke($client, '/test', []);
        self::assertSame('https://custom-api.example.com/test', $result);
    }

    // --- buildHeaders ---

    #[Test]
    public function buildHeadersIncludesAuthorization(): void
    {
        $headers = $this->invokePrivate('buildHeaders', ['GET', []]);
        self::assertContains('Authorization: test_token_123', $headers);
    }

    #[Test]
    public function buildHeadersJsonContentType(): void
    {
        $headers = $this->invokePrivate('buildHeaders', ['POST', [
            'json' => ['key' => 'value'],
        ]]);
        self::assertContains('Content-Type: application/json', $headers);
        self::assertContains('Accept: application/json', $headers);
    }

    #[Test]
    public function buildHeadersMultipartNoContentType(): void
    {
        $headers = $this->invokePrivate('buildHeaders', ['POST', [
            'multipart' => [['name' => 'file']],
        ]]);
        $hasContentType = false;
        foreach ($headers as $header) {
            if (\stripos($header, 'Content-Type:') !== false) {
                $hasContentType = true;
            }
        }
        self::assertFalse($hasContentType);
        self::assertContains('Accept: application/json', $headers);
    }

    #[Test]
    public function buildHeadersCustomHeadersAddedButAuthorizationProtected(): void
    {
        $headers = $this->invokePrivate('buildHeaders', ['POST', [
            'headers' => [
                'X-Custom'      => 'value1',
                'Authorization' => 'evil_token',
            ],
        ]]);

        self::assertContains('X-Custom: value1', $headers);
        $authCount = 0;
        foreach ($headers as $header) {
            if (\stripos($header, 'Authorization:') === 0) {
                $authCount++;
            }
        }
        self::assertSame(1, $authCount, 'Only one Authorization header should be present');
        self::assertContains('Authorization: test_token_123', $headers);
    }

    #[Test]
    public function buildHeadersGetRequestNoContentType(): void
    {
        $headers = $this->invokePrivate('buildHeaders', ['GET', []]);
        $hasContentType = false;
        foreach ($headers as $header) {
            if (\stripos($header, 'Content-Type:') !== false) {
                $hasContentType = true;
            }
        }
        self::assertFalse($hasContentType, 'GET requests should not have Content-Type');
    }

    // --- buildMultipart ---

    #[Test]
    public function buildMultipartWithFilepath(): void
    {
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'test_');
        \file_put_contents($tmpFile, 'test content');

        try {
            $result = $this->invokePrivate('buildMultipart', [[
                [
                    'name'     => 'data',
                    'filename' => 'test.txt',
                    'filepath' => $tmpFile,
                ],
            ]]);

            self::assertArrayHasKey('data', $result);
            self::assertInstanceOf(\CURLFile::class, $result['data']);
        } finally {
            @\unlink($tmpFile);
        }
    }

    #[Test]
    public function buildMultipartWithContents(): void
    {
        $result = $this->invokePrivate('buildMultipart', [[
            [
                'name'     => 'field',
                'contents' => 'plain text value',
            ],
        ]]);

        self::assertArrayHasKey('field', $result);
        self::assertSame('plain text value', $result['field']);
    }

    #[Test]
    public function buildMultipartWithContentsAndFilenameCreatesTempFile(): void
    {
        $result = $this->invokePrivate('buildMultipart', [[
            [
                'name'     => 'data',
                'filename' => 'upload.bin',
                'contents' => 'binary data here',
            ],
        ]]);

        self::assertArrayHasKey('data', $result);
        self::assertInstanceOf(\CURLFile::class, $result['data']);

        $ref = new \ReflectionClass($this->client);
        $prop = $ref->getProperty('tempFiles');
        $prop->setAccessible(true);
        $tempFiles = $prop->getValue($this->client);
        self::assertNotEmpty($tempFiles);

        foreach ($tempFiles as $file) {
            @\unlink($file);
        }
    }

    #[Test]
    public function buildMultipartDefaultName(): void
    {
        $result = $this->invokePrivate('buildMultipart', [[
            ['contents' => 'no name'],
        ]]);
        self::assertArrayHasKey('file', $result);
    }

    // --- cleanupTempFiles ---

    #[Test]
    public function cleanupTempFiles(): void
    {
        $tmpFile1 = \tempnam(\sys_get_temp_dir(), 'cleanup_');
        $tmpFile2 = \tempnam(\sys_get_temp_dir(), 'cleanup_');
        \file_put_contents($tmpFile1, 'a');
        \file_put_contents($tmpFile2, 'b');

        $ref = new \ReflectionClass($this->client);
        $prop = $ref->getProperty('tempFiles');
        $prop->setAccessible(true);
        $prop->setValue($this->client, [$tmpFile1, $tmpFile2]);

        $this->invokePrivate('cleanupTempFiles', []);

        self::assertFileDoesNotExist($tmpFile1);
        self::assertFileDoesNotExist($tmpFile2);
        self::assertEmpty($prop->getValue($this->client));
    }

    #[Test]
    public function cleanupTempFilesIgnoresMissingFiles(): void
    {
        $ref = new \ReflectionClass($this->client);
        $prop = $ref->getProperty('tempFiles');
        $prop->setAccessible(true);
        $prop->setValue($this->client, ['/nonexistent/file/path']);

        $this->invokePrivate('cleanupTempFiles', []);
        self::assertEmpty($prop->getValue($this->client));
    }

    // --- Constructor / getBaseUrl / getLastStatusCode ---

    #[Test]
    public function getBaseUrlDefault(): void
    {
        self::assertSame('https://platform-api.max.ru', $this->client->getBaseUrl());
    }

    #[Test]
    public function getBaseUrlCustom(): void
    {
        $client = new CurlHttpClient($this->config, null, 'https://custom.api.com/');
        self::assertSame('https://custom.api.com', $client->getBaseUrl());
    }

    #[Test]
    public function getBaseUrlTrimsTrailingSlash(): void
    {
        $client = new CurlHttpClient($this->config, null, 'https://example.com///');
        self::assertStringEndsNotWith('/', $client->getBaseUrl());
    }

    #[Test]
    public function getLastStatusCodeDefaultsToZero(): void
    {
        self::assertSame(0, $this->client->getLastStatusCode());
    }

    // --- Helper ---

    private function invokePrivate(string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod(CurlHttpClient::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->client, $args);
    }
}
