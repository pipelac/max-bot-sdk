<?php

namespace App\Component\Max\Tests\Unit;

use App\Component\Max\Client;
use App\Component\Max\Config;
use App\Component\Max\ResponseDecoder;
use App\Component\Max\Http\RetryHandler;
use App\Component\Max\Tests\Helper\MockHttpClient;
use App\Component\Max\Resource\Bot;
use App\Component\Max\Resource\Chats;
use App\Component\Max\Resource\Members;
use App\Component\Max\Resource\Messages;
use App\Component\Max\Resource\Subscriptions;
use App\Component\Max\Resource\Uploads;
use App\Component\Max\Resource\Callbacks;
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

        $this->assertEquals(array('user_id' => 1), $result);
        $requests = $this->mockHttp->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('GET', $requests[0]['method']);
        $this->assertEquals('/me', $requests[0]['url']);
    }

    public function testPostSendsRequest()
    {
        $this->mockHttp->setResponse(200, '{"message": {"body": {"mid": "abc"}}}');
        $result = $this->client->post('/messages', array('text' => 'hello'), array('chat_id' => 123));

        $requests = $this->mockHttp->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('POST', $requests[0]['method']);
    }

    public function testDeleteSendsRequest()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->delete('/messages', array('message_id' => 'abc'));

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
}
