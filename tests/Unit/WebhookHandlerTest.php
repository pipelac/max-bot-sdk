<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Utils\WebhookHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebhookHandlerTest extends TestCase
{
    private WebhookHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new WebhookHandler();
    }

    #[Test]
    public function parseUpdateValidJson(): void
    {
        $json = '{"update_type": "message_created", "timestamp": 123}';
        $update = $this->handler->parseUpdate($json);

        self::assertNotNull($update);
        self::assertSame('message_created', $update->getUpdateType());
        self::assertSame(123, $update->getTimestamp());
    }

    #[Test]
    public function parseUpdateWithMessage(): void
    {
        $json = \json_encode([
            'update_type' => 'message_created',
            'timestamp'   => 456,
            'message'     => [
                'text'   => 'Hello',
                'sender' => ['user_id' => 1, 'name' => 'Bot'],
            ],
        ]);

        $update = $this->handler->parseUpdate($json);
        self::assertNotNull($update);
        $message = $update->getMessage();
        self::assertNotNull($message);
        self::assertSame('Hello', $message->getText());
    }

    #[Test]
    public function parseUpdateEmptyReturnsNull(): void
    {
        self::assertNull($this->handler->parseUpdate(''));
    }

    #[Test]
    public function parseUpdateInvalidJsonReturnsNull(): void
    {
        self::assertNull($this->handler->parseUpdate('NOT_JSON{{{'));
    }

    #[Test]
    public function verifySecretValid(): void
    {
        self::assertTrue($this->handler->verifySecret('my_secret', 'my_secret'));
    }

    #[Test]
    public function verifySecretInvalid(): void
    {
        self::assertFalse($this->handler->verifySecret('my_secret', 'wrong_secret'));
    }

    #[Test]
    public function verifySecretEmptyExpected(): void
    {
        self::assertFalse($this->handler->verifySecret('', 'something'));
    }

    #[Test]
    public function verifySecretEmptyActual(): void
    {
        self::assertFalse($this->handler->verifySecret('my_secret', ''));
    }
}
