<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Client;
use MaxBotSdk\Config;
use MaxBotSdk\Http\RetryHandler;
use MaxBotSdk\Resource\Bot;
use MaxBotSdk\Resource\Callbacks;
use MaxBotSdk\Resource\Chats;
use MaxBotSdk\Resource\Members;
use MaxBotSdk\Resource\Messages;
use MaxBotSdk\Resource\Subscriptions;
use MaxBotSdk\Resource\Uploads;
use MaxBotSdk\ResponseDecoder;
use MaxBotSdk\Tests\Helper\MockHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private MockHttpClient $mockHttp;
    private Client $client;

    protected function setUp(): void
    {
        $this->mockHttp = new MockHttpClient();
        $config = new Config('test_token');
        $decoder = new ResponseDecoder();
        $retryHandler = new RetryHandler(0);
        $this->client = new Client($config, $this->mockHttp, $decoder, $retryHandler);
    }

    #[Test]
    public function botReturnsResource(): void
    {
        self::assertInstanceOf(Bot::class, $this->client->bot());
    }

    #[Test]
    public function chatsReturnsResource(): void
    {
        self::assertInstanceOf(Chats::class, $this->client->chats());
    }

    #[Test]
    public function membersReturnsResource(): void
    {
        self::assertInstanceOf(Members::class, $this->client->members());
    }

    #[Test]
    public function messagesReturnsResource(): void
    {
        self::assertInstanceOf(Messages::class, $this->client->messages());
    }

    #[Test]
    public function subscriptionsReturnsResource(): void
    {
        self::assertInstanceOf(Subscriptions::class, $this->client->subscriptions());
    }

    #[Test]
    public function uploadsReturnsResource(): void
    {
        self::assertInstanceOf(Uploads::class, $this->client->uploads());
    }

    #[Test]
    public function callbacksReturnsResource(): void
    {
        self::assertInstanceOf(Callbacks::class, $this->client->callbacks());
    }

    #[Test]
    public function resourceReturnsSameInstance(): void
    {
        $bot1 = $this->client->bot();
        $bot2 = $this->client->bot();
        self::assertSame($bot1, $bot2);
    }

    #[Test]
    public function getSendsRequest(): void
    {
        $this->mockHttp->setResponse(200, '{"user_id": 1}');
        $result = $this->client->get('/me');

        self::assertSame(['user_id' => 1], $result);
        $requests = $this->mockHttp->getRequests();
        self::assertCount(1, $requests);
        self::assertSame('GET', $requests[0]['method']);
        self::assertSame('/me', $requests[0]['url']);
    }

    #[Test]
    public function postSendsRequest(): void
    {
        $this->mockHttp->setResponse(200, '{"message": {"body": {"mid": "abc"}}}');
        $this->client->post('/messages', ['text' => 'hello'], ['chat_id' => 123]);

        $requests = $this->mockHttp->getRequests();
        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0]['method']);
    }

    #[Test]
    public function deleteSendsRequest(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->delete('/messages', ['message_id' => 'abc']);

        $requests = $this->mockHttp->getRequests();
        self::assertSame('DELETE', $requests[0]['method']);
    }

    #[Test]
    public function getHttpClient(): void
    {
        self::assertSame($this->mockHttp, $this->client->getHttpClient());
    }

    #[Test]
    public function getConfig(): void
    {
        $config = $this->client->getConfig();
        self::assertInstanceOf(Config::class, $config);
        self::assertSame('test_token', $config->getToken());
    }

    #[Test]
    public function putSendsRequest(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->put('/chats/123', ['title' => 'Updated']);

        $req = $this->mockHttp->getLastRequest();
        self::assertSame('PUT', $req['method']);
        self::assertSame('/chats/123', $req['url']);
        self::assertSame(['title' => 'Updated'], $req['options']['json']);
    }

    #[Test]
    public function patchSendsRequest(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->patch('/chats/123', ['title' => 'Patched']);

        $req = $this->mockHttp->getLastRequest();
        self::assertSame('PATCH', $req['method']);
        self::assertSame('/chats/123', $req['url']);
        self::assertSame(['title' => 'Patched'], $req['options']['json']);
    }

    #[Test]
    public function postWithoutJsonBody(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->post('/endpoint');

        $req = $this->mockHttp->getLastRequest();
        self::assertSame('POST', $req['method']);
        self::assertArrayNotHasKey('json', $req['options']);
    }

    #[Test]
    public function getWithQueryParams(): void
    {
        $this->mockHttp->setResponse(200, '{"items": []}');
        $this->client->get('/chats', ['count' => 10, 'marker' => 'abc']);

        $req = $this->mockHttp->getLastRequest();
        self::assertSame('GET', $req['method']);
        self::assertSame(['count' => 10, 'marker' => 'abc'], $req['options']['query']);
    }
}
