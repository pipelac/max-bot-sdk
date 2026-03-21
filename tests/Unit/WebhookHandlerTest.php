<?php

namespace App\Component\Max\Tests\Unit;

use App\Component\Max\Utils\WebhookHandler;
use App\Component\Max\DTO\Update;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для WebhookHandler.
 */
class WebhookHandlerTest extends TestCase
{
    /** @var WebhookHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new WebhookHandler();
    }

    public function testParseUpdateValidJson()
    {
        $json = '{"update_type": "message_created", "timestamp": 123}';
        $update = $this->handler->parseUpdate($json);

        $this->assertInstanceOf('App\Component\Max\DTO\Update', $update);
        $this->assertEquals('message_created', $update->getUpdateType());
        $this->assertEquals(123, $update->getTimestamp());
    }

    public function testParseUpdateWithMessage()
    {
        $json = json_encode(array(
            'update_type' => 'message_created',
            'timestamp'   => 456,
            'message'     => array(
                'body'   => array('text' => 'Hello'),
                'sender' => array('user_id' => 1, 'name' => 'Bot'),
            ),
        ));

        $update = $this->handler->parseUpdate($json);
        $this->assertNotNull($update->getMessage());
        $this->assertEquals('Hello', $update->getMessage()->getText());
    }

    public function testParseUpdateEmptyReturnsNull()
    {
        $this->assertNull($this->handler->parseUpdate(''));
    }

    public function testParseUpdateInvalidJsonReturnsNull()
    {
        $this->assertNull($this->handler->parseUpdate('NOT_JSON{{{'));
    }

    public function testVerifySecretValid()
    {
        $this->assertTrue($this->handler->verifySecret('my_secret', 'my_secret'));
    }

    public function testVerifySecretInvalid()
    {
        $this->assertFalse($this->handler->verifySecret('my_secret', 'wrong_secret'));
    }

    public function testVerifySecretEmptyExpected()
    {
        $this->assertFalse($this->handler->verifySecret('', 'something'));
    }

    public function testVerifySecretEmptyActual()
    {
        $this->assertFalse($this->handler->verifySecret('my_secret', ''));
    }
}
