<?php

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
use PHPUnit\Framework\TestCase;

/**
 * Тесты для Client (фасад).
 */
class ClientTest extends TestCase
{
    /** @var MockHttpClient */
    private $mockHttp;

    /** @var Client */
    private $client;

    protected function setUp(): void
    {
        $this->mockHttp = new MockHttpClient();
        $config = new Config('test_token');
        $decoder = new ResponseDecoder();
        $retryHandler = new RetryHandler(0); // без повторов для тестов
        $this->client = new Client($config, $this->mockHttp, $decoder, $retryHandler);
    }

    public function testBotReturnsResource()
    {
        $this->assertInstanceOf(Bot::class, $this->client->bot());
    }

    public function testChatsReturnsResource()
    {
        $this->assertInstanceOf(Chats::class, $this->client->chats());
    }

    public function testMembersReturnsResource()
    {
        $this->assertInstanceOf(Members::class, $this->client->members());
    }

    public function testMessagesReturnsResource()
    {
        $this->assertInstanceOf(Messages::class, $this->client->messages());
    }

    public function testSubscriptionsReturnsResource()
    {
        $this->assertInstanceOf(Subscriptions::class, $this->client->subscriptions());
    }

    public function testUploadsReturnsResource()
    {
        $this->assertInstanceOf(Uploads::class, $this->client->uploads());
    }

    public function testCallbacksReturnsResource()
    {
        $this->assertInstanceOf(Callbacks::class, $this->client->callbacks());
    }

    public function testResourceReturnsSameInstance()
    {
        $bot1 = $this->client->bot();
        $bot2 = $this->client->bot();
        $this->assertSame($bot1, $bot2);
    }

    public function testGetSendsRequest()
    {
        $this->mockHttp->setResponse(200, '{"user_id": 1}');
        $result = $this->client->get('/me');

        $this->assertEquals(['user_id' => 1], $result);
        $requests = $this->mockHttp->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('GET', $requests[0]['method']);
        $this->assertEquals('/me', $requests[0]['url']);
    }

    public function testPostSendsRequest()
    {
        $this->mockHttp->setResponse(200, '{"message": {"body": {"mid": "abc"}}}');
        $result = $this->client->post('/messages', ['text' => 'hello'], ['chat_id' => 123]);

        $requests = $this->mockHttp->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('POST', $requests[0]['method']);
    }

    public function testDeleteSendsRequest()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->delete('/messages', ['message_id' => 'abc']);

        $requests = $this->mockHttp->getRequests();
        $this->assertEquals('DELETE', $requests[0]['method']);
    }

    public function testGetHttpClient()
    {
        $this->assertSame($this->mockHttp, $this->client->getHttpClient());
    }

    public function testGetConfig()
    {
        $config = $this->client->getConfig();
        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('test_token', $config->getToken());
    }

    // --- performRequest: rate limiter integration ---

    public function testPerformRequestWithRateLimiter()
    {
        // Config с rateLimit > 0 → создаёт RateLimiter
        $config = new Config('test_token', 30, 3, 10);
        $decoder = new ResponseDecoder();
        $retryHandler = new RetryHandler(0);
        $client = new Client($config, $this->mockHttp, $decoder, $retryHandler);

        $this->mockHttp->setResponse(200, '{"ok": true}');
        $result = $client->get('/me');
        $this->assertEquals(['ok' => true], $result);
    }

    // --- log: with logger ---

    public function testLogDelegatesWithPrefix()
    {
        $logger = $this->createMock(\MaxBotSdk\Contracts\LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('debug')
            ->with($this->stringContains('MyBot: '));

        $config = new Config('test_token', 30, 3, 30, true, true, 'MyBot', $logger);
        $decoder = new ResponseDecoder();
        $retryHandler = new RetryHandler(0);
        $mockHttp = new MockHttpClient();
        $mockHttp->setResponse(200, '{"ok": true}');

        $client = new Client($config, $mockHttp, $decoder, $retryHandler);
        $client->get('/me');
    }

    // --- getResource: lazy init multiple ---

    public function testAllResourcesCachedLazily()
    {
        $this->assertSame($this->client->chats(), $this->client->chats());
        $this->assertSame($this->client->messages(), $this->client->messages());
        $this->assertSame($this->client->members(), $this->client->members());
        $this->assertSame($this->client->subscriptions(), $this->client->subscriptions());
        $this->assertSame($this->client->uploads(), $this->client->uploads());
        $this->assertSame($this->client->callbacks(), $this->client->callbacks());
    }

    // --- PUT/PATCH ---

    public function testPutSendsRequest()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->put('/chats/123/pin', ['message_id' => 'mid']);
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('PUT', $req['method']);
    }

    public function testPatchSendsRequest()
    {
        $this->mockHttp->setResponse(200, '{"chat_id": 123}');
        $this->client->patch('/chats/123', ['title' => 'New']);
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('PATCH', $req['method']);
    }
}
