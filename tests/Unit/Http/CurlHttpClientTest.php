<?php

namespace App\Component\Max\Tests\Unit\Http;

use App\Component\Max\Config;
use App\Component\Max\Http\CurlHttpClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit-тесты для CurlHttpClient.
 *
 * Тестируем внутреннюю логику (buildUrl, buildHeaders, buildMultipart, cleanupTempFiles)
 * через рефлексию, поскольку cURL-вызовы невозможно мокать без интеграционного окружения.
 */
class CurlHttpClientTest extends TestCase
{
    /** @var Config */
    private $config;

    /** @var CurlHttpClient */
    private $client;

    protected function setUp(): void
    {
        $this->config = new Config('test_token_123');
        $this->client = new CurlHttpClient($this->config);
    }

    // --- buildUrl ---

    public function testBuildUrlRelativePath()
    {
        $result = $this->invokePrivate('buildUrl', array('/chats', array()));
        $this->assertEquals('https://platform-api.max.ru/chats', $result);
    }

    public function testBuildUrlRelativePathWithLeadingSlash()
    {
        $result = $this->invokePrivate('buildUrl', array('chats', array()));
        $this->assertEquals('https://platform-api.max.ru/chats', $result);
    }

    public function testBuildUrlAbsoluteUrl()
    {
        $result = $this->invokePrivate('buildUrl', array('https://upload.max.ru/file', array()));
        $this->assertEquals('https://upload.max.ru/file', $result);
    }

    public function testBuildUrlAbsoluteHttpUrl()
    {
        $result = $this->invokePrivate('buildUrl', array('http://localhost:8080/test', array()));
        $this->assertEquals('http://localhost:8080/test', $result);
    }

    public function testBuildUrlWithQueryParams()
    {
        $result = $this->invokePrivate('buildUrl', array('/chats', array(
            'query' => array('count' => 10, 'marker' => 42),
        )));
        $this->assertStringContainsString('count=10', $result);
        $this->assertStringContainsString('marker=42', $result);
        $this->assertStringStartsWith('https://platform-api.max.ru/chats?', $result);
    }

    public function testBuildUrlWithEmptyQuery()
    {
        $result = $this->invokePrivate('buildUrl', array('/chats', array(
            'query' => array(),
        )));
        $this->assertEquals('https://platform-api.max.ru/chats', $result);
    }

    public function testBuildUrlWithExistingQueryString()
    {
        $result = $this->invokePrivate('buildUrl', array('https://example.com/path?existing=1', array(
            'query' => array('new' => 'value'),
        )));
        $this->assertStringContainsString('existing=1', $result);
        $this->assertStringContainsString('&new=value', $result);
    }

    public function testBuildUrlCustomBaseUrl()
    {
        $client = new CurlHttpClient($this->config, null, 'https://custom-api.example.com/');
        $ref = new ReflectionClass($client);
        $method = $ref->getMethod('buildUrl');
        $method->setAccessible(true);
        $result = $method->invoke($client, '/test', array());
        $this->assertEquals('https://custom-api.example.com/test', $result);
    }

    // --- buildHeaders ---

    public function testBuildHeadersIncludesAuthorization()
    {
        $headers = $this->invokePrivate('buildHeaders', array('GET', array()));
        $this->assertContains('Authorization: test_token_123', $headers);
    }

    public function testBuildHeadersJsonContentType()
    {
        $headers = $this->invokePrivate('buildHeaders', array('POST', array(
            'json' => array('key' => 'value'),
        )));
        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertContains('Accept: application/json', $headers);
    }

    public function testBuildHeadersMultipartNoContentType()
    {
        $headers = $this->invokePrivate('buildHeaders', array('POST', array(
            'multipart' => array(array('name' => 'file')),
        )));
        // Multipart should NOT have Content-Type (cURL sets it with boundary)
        $hasContentType = false;
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') !== false) {
                $hasContentType = true;
            }
        }
        $this->assertFalse($hasContentType);
        $this->assertContains('Accept: application/json', $headers);
    }

    public function testBuildHeadersCustomHeadersAddedButAuthorizationProtected()
    {
        $headers = $this->invokePrivate('buildHeaders', array('POST', array(
            'headers' => array(
                'X-Custom' => 'value1',
                'Authorization' => 'evil_token',
            ),
        )));

        $this->assertContains('X-Custom: value1', $headers);
        // Should NOT contain the injected authorization
        $authCount = 0;
        foreach ($headers as $header) {
            if (stripos($header, 'Authorization:') === 0) {
                $authCount++;
            }
        }
        $this->assertEquals(1, $authCount, 'Only one Authorization header should be present');
        $this->assertContains('Authorization: test_token_123', $headers);
    }

    public function testBuildHeadersGetRequestNoContentType()
    {
        $headers = $this->invokePrivate('buildHeaders', array('GET', array()));
        $hasContentType = false;
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') !== false) {
                $hasContentType = true;
            }
        }
        $this->assertFalse($hasContentType, 'GET requests should not have Content-Type');
    }

    // --- buildMultipart ---

    public function testBuildMultipartWithFilepath()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'test content');

        try {
            $result = $this->invokePrivate('buildMultipart', array(array(
                array(
                    'name'     => 'data',
                    'filename' => 'test.txt',
                    'filepath' => $tmpFile,
                ),
            )));

            $this->assertArrayHasKey('data', $result);
            $this->assertInstanceOf(\CURLFile::class, $result['data']);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testBuildMultipartWithContents()
    {
        $result = $this->invokePrivate('buildMultipart', array(array(
            array(
                'name'     => 'field',
                'contents' => 'plain text value',
            ),
        )));

        $this->assertArrayHasKey('field', $result);
        $this->assertEquals('plain text value', $result['field']);
    }

    public function testBuildMultipartWithContentsAndFilenameCreatesTempFile()
    {
        $result = $this->invokePrivate('buildMultipart', array(array(
            array(
                'name'     => 'data',
                'filename' => 'upload.bin',
                'contents' => 'binary data here',
            ),
        )));

        $this->assertArrayHasKey('data', $result);
        $this->assertInstanceOf(\CURLFile::class, $result['data']);

        // Verify temp file was tracked for cleanup
        $ref = new ReflectionClass($this->client);
        $prop = $ref->getProperty('tempFiles');
        $prop->setAccessible(true);
        $tempFiles = $prop->getValue($this->client);
        $this->assertNotEmpty($tempFiles);

        // Cleanup
        foreach ($tempFiles as $file) {
            @unlink($file);
        }
    }

    public function testBuildMultipartDefaultName()
    {
        $result = $this->invokePrivate('buildMultipart', array(array(
            array(
                'contents' => 'no name',
            ),
        )));

        // Default name is 'file'
        $this->assertArrayHasKey('file', $result);
    }

    // --- cleanupTempFiles ---

    public function testCleanupTempFiles()
    {
        $tmpFile1 = tempnam(sys_get_temp_dir(), 'cleanup_');
        $tmpFile2 = tempnam(sys_get_temp_dir(), 'cleanup_');
        file_put_contents($tmpFile1, 'a');
        file_put_contents($tmpFile2, 'b');

        // Inject temp files via reflection
        $ref = new ReflectionClass($this->client);
        $prop = $ref->getProperty('tempFiles');
        $prop->setAccessible(true);
        $prop->setValue($this->client, array($tmpFile1, $tmpFile2));

        // Run cleanup
        $this->invokePrivate('cleanupTempFiles', array());

        $this->assertFileDoesNotExist($tmpFile1);
        $this->assertFileDoesNotExist($tmpFile2);

        // tempFiles array should be empty after cleanup
        $this->assertEmpty($prop->getValue($this->client));
    }

    public function testCleanupTempFilesIgnoresMissingFiles()
    {
        $ref = new ReflectionClass($this->client);
        $prop = $ref->getProperty('tempFiles');
        $prop->setAccessible(true);
        $prop->setValue($this->client, array('/nonexistent/file/path'));

        // Should not throw
        $this->invokePrivate('cleanupTempFiles', array());
        $this->assertEmpty($prop->getValue($this->client));
    }

    // --- Constructor / getBaseUrl / getLastStatusCode ---

    public function testGetBaseUrlDefault()
    {
        $this->assertEquals('https://platform-api.max.ru', $this->client->getBaseUrl());
    }

    public function testGetBaseUrlCustom()
    {
        $client = new CurlHttpClient($this->config, null, 'https://custom.api.com/');
        $this->assertEquals('https://custom.api.com', $client->getBaseUrl());
    }

    public function testGetBaseUrlTrimsTrailingSlash()
    {
        $client = new CurlHttpClient($this->config, null, 'https://example.com///');
        $this->assertStringEndsNotWith('/', $client->getBaseUrl());
    }

    public function testGetLastStatusCodeDefaultsToZero()
    {
        $this->assertEquals(0, $this->client->getLastStatusCode());
    }

    // --- Helper ---

    /**
     * Вызвать приватный метод через рефлексию.
     *
     * @param string $method Имя метода.
     * @param array  $args   Аргументы.
     * @return mixed
     */
    private function invokePrivate($method, array $args = array())
    {
        $ref = new ReflectionMethod(CurlHttpClient::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->client, $args);
    }
}
