<?php

namespace MaxBotSdk\Tests\Unit\Resource;

use MaxBotSdk\Client;
use MaxBotSdk\Config;
use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\Chat;
use MaxBotSdk\DTO\ChatMember;
use MaxBotSdk\DTO\Message;
use MaxBotSdk\DTO\PaginatedResult;
use MaxBotSdk\DTO\Subscription;
use MaxBotSdk\DTO\Update;
use MaxBotSdk\DTO\UpdatesResult;
use MaxBotSdk\DTO\UploadResult;
use MaxBotSdk\DTO\User;
use MaxBotSdk\DTO\VideoInfo;
use MaxBotSdk\Http\RetryHandler;
use MaxBotSdk\ResponseDecoder;
use MaxBotSdk\Tests\Helper\MockHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для всех ресурсов MAX SDK с проверкой DTO маппинга.
 */
class ResourceTest extends TestCase
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
        $retryHandler = new RetryHandler(0);
        $this->client = new Client($config, $this->mockHttp, $decoder, $retryHandler);
    }

    // =====================================================================
    // Bot
    // =====================================================================

    public function testBotGetMeReturnsUserDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'user_id'  => 42,
            'name'     => 'TestBot',
            'username' => 'testbot',
            'is_bot'   => true,
        ]));
        $user = $this->client->bot()->getMe();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(42, $user->getUserId());
        $this->assertEquals('TestBot', $user->getName());
        $this->assertTrue($user->isBot());
        $this->assertEquals('GET', $this->mockHttp->getLastRequest()['method']);
    }

    // =====================================================================
    // Chats
    // =====================================================================

    public function testChatsGetChatsReturnsPaginatedResult()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'chats'  => [
                ['chat_id' => 1, 'type' => 'chat', 'title' => 'Group1'],
                ['chat_id' => 2, 'type' => 'chat', 'title' => 'Group2'],
            ],
            'marker' => 'next_page',
        ]));

        $result = $this->client->chats()->getChats(10);
        $this->assertInstanceOf(PaginatedResult::class, $result);
        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertInstanceOf(Chat::class, $items[0]);
        $this->assertEquals(1, $items[0]->getChatId());
        $this->assertTrue($result->hasMore());
    }

    public function testChatsGetChatReturnsChatDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'chat_id' => 123,
            'type'    => 'dialog',
            'title'   => 'Private',
        ]));
        $chat = $this->client->chats()->getChat(123);
        $this->assertInstanceOf(Chat::class, $chat);
        $this->assertEquals(123, $chat->getChatId());
        $this->assertEquals('dialog', $chat->getType());
    }

    public function testChatsEditChatReturnsChatDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'chat_id' => 123,
            'title'   => 'Updated',
        ]));
        $chat = $this->client->chats()->editChat(123, ['title' => 'Updated']);
        $this->assertInstanceOf(Chat::class, $chat);
        $this->assertEquals('PATCH', $this->mockHttp->getLastRequest()['method']);
    }

    public function testChatsDeleteChatReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->chats()->deleteChat(123);
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    public function testChatsSendActionReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->chats()->sendAction(123, 'typing_on');
        $this->assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('POST', $req['method']);
        $this->assertStringContainsString('/actions', $req['url']);
    }

    public function testChatsGetPinnedMessageReturnsMessage()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => [
                'body' => ['mid' => 'msg_1', 'text' => 'Pinned'],
            ],
        ]));
        $msg = $this->client->chats()->getPinnedMessage(123);
        $this->assertInstanceOf(Message::class, $msg);
    }

    public function testChatsGetPinnedMessageReturnsNullWhenEmpty()
    {
        $this->mockHttp->setResponse(200, '{}');
        $msg = $this->client->chats()->getPinnedMessage(123);
        $this->assertNull($msg);
    }

    public function testChatsPinMessageReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->chats()->pinMessage(123, 'mid_456');
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertEquals('PUT', $this->mockHttp->getLastRequest()['method']);
    }

    public function testChatsUnpinMessageReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->chats()->unpinMessage(123);
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertEquals('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    public function testChatsGetChatInvalidIdThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->chats()->getChat('abc');
    }

    // =====================================================================
    // Members
    // =====================================================================

    public function testMembersGetMembersReturnsPaginatedResult()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'members' => [
                ['user_id' => 10, 'name' => 'User1'],
            ],
        ]));
        $result = $this->client->members()->getMembers(123);
        $this->assertInstanceOf(PaginatedResult::class, $result);
        $items = $result->getItems();
        $this->assertCount(1, $items);
        $this->assertInstanceOf(ChatMember::class, $items[0]);
    }

    public function testMembersAddMembersReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->addMembers(123, [1, 2, 3]);
        $this->assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('POST', $req['method']);
        $this->assertStringContainsString('/members', $req['url']);
    }

    public function testMembersRemoveMemberReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->removeMember(123, 456);
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertEquals('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    public function testMembersGetMyMembershipReturnsChatMemberDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'user_id'  => 99,
            'name'     => 'Bot',
            'is_admin' => true,
        ]));
        $member = $this->client->members()->getMyMembership(123);
        $this->assertInstanceOf(ChatMember::class, $member);
        $this->assertEquals(99, $member->getUserId());
    }

    public function testMembersLeaveChatReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->leaveChat(123);
        $this->assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('DELETE', $req['method']);
        $this->assertStringContainsString('/members/me', $req['url']);
    }

    public function testMembersGetAdminsReturnsPaginatedResult()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'members' => [
                ['user_id' => 5, 'name' => 'Admin1', 'is_admin' => true],
            ],
        ]));
        $result = $this->client->members()->getAdmins(123);
        $this->assertInstanceOf(PaginatedResult::class, $result);
    }

    public function testMembersAddAdminReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->addAdmin(123, 456);
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertEquals('POST', $this->mockHttp->getLastRequest()['method']);
    }

    public function testMembersRemoveAdminReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->removeAdmin(123, 456);
        $this->assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('DELETE', $req['method']);
        $this->assertStringContainsString('/admins', $req['url']);
    }

    public function testMembersRemoveMemberInvalidIdThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->members()->removeMember('abc', 123);
    }

    // =====================================================================
    // Messages
    // =====================================================================

    public function testMessagesSendMessageReturnsMessageDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'body' => [
                'mid'  => 'm1',
                'text' => 'Hello',
            ],
            'sender' => ['user_id' => 1, 'name' => 'Bot'],
        ]));
        $msg = $this->client->messages()->sendMessage(
            ['text' => 'Hello'],
            null,
            123
        );
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertEquals('POST', $this->mockHttp->getLastRequest()['method']);
    }

    public function testMessagesGetMessageReturnsMessageDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'body' => ['mid' => 'mid_123', 'text' => 'Hi'],
        ]));
        $msg = $this->client->messages()->getMessage('mid_123');
        $this->assertInstanceOf(Message::class, $msg);
    }

    public function testMessagesGetMessagesReturnsPaginatedResult()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'messages' => [
                ['body' => ['mid' => 'm1', 'text' => 'Msg1']],
            ],
        ]));
        $result = $this->client->messages()->getMessages(123);
        $this->assertInstanceOf(PaginatedResult::class, $result);
        $this->assertCount(1, $result->getItems());
    }

    public function testMessagesEditMessageReturnsMessageDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'body' => ['mid' => 'mid_123', 'text' => 'Updated'],
        ]));
        $msg = $this->client->messages()->editMessage('mid_123', ['text' => 'Updated']);
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertEquals('PUT', $this->mockHttp->getLastRequest()['method']);
    }

    public function testMessagesDeleteMessageReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->messages()->deleteMessage('mid_123');
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertEquals('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    public function testMessagesSendTextReturnsMessageDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'body' => ['mid' => 'm2', 'text' => 'Привет'],
        ]));
        $msg = $this->client->messages()->sendText('Привет', 123);
        $this->assertInstanceOf(Message::class, $msg);
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('POST', $req['method']);
        $this->assertStringContainsString('/messages', $req['url']);
    }

    public function testMessagesSendTextWithFormatReturnsMessageDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'body' => ['mid' => 'm3'],
        ]));
        $msg = $this->client->messages()->sendText('**bold**', 123, 'markdown');
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertEquals('POST', $this->mockHttp->getLastRequest()['method']);
    }

    public function testMessagesSendTextWithKeyboardReturnsMessageDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'body' => ['mid' => 'm4'],
        ]));
        $keyboard = [
            [
                ['type' => 'callback', 'text' => 'Кнопка', 'payload' => 'btn1'],
            ],
        ];
        $msg = $this->client->messages()->sendTextWithKeyboard('Выберите:', 123, $keyboard);
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertEquals('POST', $this->mockHttp->getLastRequest()['method']);
    }

    // =====================================================================
    // Subscriptions
    // =====================================================================

    public function testSubscriptionsSubscribeReturnsSubscription()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'url' => 'https://example.com/webhook',
            'time' => 1234567890,
        ]));
        $result = $this->client->subscriptions()->subscribe('https://example.com/webhook');
        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertEquals('POST', $this->mockHttp->getLastRequest()['method']);
    }

    public function testSubscriptionsSubscribeWithOptions()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'url' => 'https://example.com/webhook',
        ]));
        $this->client->subscriptions()->subscribe(
            'https://example.com/webhook',
            ['message_created']
        );
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('POST', $req['method']);
        $json = isset($req['options']['json']) ? $req['options']['json'] : [];
        $this->assertEquals('https://example.com/webhook', $json['url']);
    }

    public function testSubscriptionsGetSubscriptionsReturnsArray()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'subscriptions' => [
                ['url' => 'https://example.com', 'time' => 1234567890],
            ],
        ]));
        $subs = $this->client->subscriptions()->getSubscriptions();
        $this->assertTrue(is_array($subs));
        $this->assertCount(1, $subs);
        $this->assertInstanceOf(Subscription::class, $subs[0]);
    }

    public function testSubscriptionsUnsubscribeReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->subscriptions()->unsubscribe('https://example.com/webhook');
        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertEquals('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    public function testSubscriptionsGetUpdatesReturnsUpdatesResult()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'updates' => [
                ['update_type' => 'message_created', 'timestamp' => 1234567890],
            ],
            'marker' => 42,
        ]));
        $result = $this->client->subscriptions()->getUpdates(50, 10);
        $this->assertInstanceOf(UpdatesResult::class, $result);
        $this->assertCount(1, $result->getUpdates());
        $this->assertInstanceOf(Update::class, $result->getUpdates()[0]);
        $this->assertEquals(42, $result->getMarker());
    }

    public function testSubscriptionsHttpUrlThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->subscriptions()->subscribe('http://example.com');
    }

    // =====================================================================
    // Callbacks
    // =====================================================================

    public function testCallbacksAnswerCallbackReturnsActionResult()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->callbacks()->answerCallback('cb_123', null, 'Готово!');
        $this->assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        $this->assertEquals('POST', $req['method']);
        $this->assertStringContainsString('/answers', $req['url']);
    }

    public function testCallbacksAnswerCallbackWithMessage()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->callbacks()->answerCallback(
            'cb_123',
            ['text' => 'Updated text']
        );
        $req = $this->mockHttp->getLastRequest();
        $json = isset($req['options']['json']) ? $req['options']['json'] : [];
        $this->assertArrayHasKey('message', $json);
    }

    public function testCallbacksEmptyIdThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->callbacks()->answerCallback('');
    }

    // =====================================================================
    // Uploads
    // =====================================================================

    public function testUploadsGetUploadUrlReturnsUploadResult()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'url' => 'https://upload.max.ru/abc123',
        ]));
        $result = $this->client->uploads()->getUploadUrl('image');
        $this->assertInstanceOf(UploadResult::class, $result);
        $this->assertEquals('https://upload.max.ru/abc123', $result->getUrl());
        $this->assertEquals('POST', $this->mockHttp->getLastRequest()['method']);
    }

    public function testUploadsGetVideoInfoReturnsVideoInfoDto()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'token'    => 'vid_abc',
            'url'      => 'https://cdn.max.ru/video.mp4',
            'width'    => 1920,
            'height'   => 1080,
            'duration' => 120,
        ]));
        $info = $this->client->uploads()->getVideoInfo('vid_abc');
        $this->assertInstanceOf(VideoInfo::class, $info);
        $this->assertEquals('vid_abc', $info->getToken());
        $this->assertEquals(1920, $info->getWidth());
        $this->assertEquals(1080, $info->getHeight());
        $this->assertEquals(120, $info->getDuration());
        $this->assertEquals('GET', $this->mockHttp->getLastRequest()['method']);
    }

    public function testUploadsInvalidTypeThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->uploads()->getUploadUrl('invalid_type');
    }

    // =====================================================================
    // Chats — дополнительные edge cases
    // =====================================================================

    public function testChatsGetChatsWithPagination()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'chats'  => [
                ['chat_id' => 1, 'type' => 'chat', 'title' => 'G1'],
            ],
            'marker' => 42,
        ]));
        $result = $this->client->chats()->getChats(5, 10);
        $req = $this->mockHttp->getLastRequest();
        $query = isset($req['options']['query']) ? $req['options']['query'] : [];
        $this->assertEquals(5, $query['count']);
        $this->assertEquals(10, $query['marker']);
    }

    public function testChatsSendActionEmptyActionThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->chats()->sendAction(123, '');
    }

    public function testChatsPinMessageEmptyMessageIdThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->chats()->pinMessage(123, '');
    }

    // =====================================================================
    // Members — дополнительные edge cases
    // =====================================================================

    public function testMembersGetMembersWithPagination()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'members' => [
                ['user_id' => 10, 'name' => 'U1'],
            ],
        ]));
        $this->client->members()->getMembers(123, 5, 10);
        $req = $this->mockHttp->getLastRequest();
        $query = isset($req['options']['query']) ? $req['options']['query'] : [];
        $this->assertEquals(5, $query['count']);
        $this->assertEquals(10, $query['marker']);
    }

    public function testMembersRemoveMemberWithBlock()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->members()->removeMember(123, 456, true);
        $req = $this->mockHttp->getLastRequest();
        $query = isset($req['options']['query']) ? $req['options']['query'] : [];
        $this->assertEquals('true', $query['block']);
    }

    // =====================================================================
    // Messages — дополнительные edge cases
    // =====================================================================

    public function testMessagesGetMessageEmptyIdThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->messages()->getMessage('');
    }

    public function testMessagesEditMessageEmptyIdThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->messages()->editMessage('', ['text' => 'new']);
    }

    public function testMessagesDeleteMessageEmptyIdThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->messages()->deleteMessage('');
    }

    public function testMessagesSendTextEmptyThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->messages()->sendText('', 123);
    }

    public function testMessagesGetMessagesWithAllParams()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'messages' => [],
        ]));
        $this->client->messages()->getMessages(123, 10, 100, 200);
        $req = $this->mockHttp->getLastRequest();
        $query = isset($req['options']['query']) ? $req['options']['query'] : [];
        $this->assertEquals(10, $query['count']);
        $this->assertEquals(100, $query['from']);
        $this->assertEquals(200, $query['to']);
    }

    // =====================================================================
    // Subscriptions — дополнительные edge cases
    // =====================================================================

    public function testSubscriptionsSubscribeWithAllOptions()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'url' => 'https://example.com/wh',
        ]));
        $this->client->subscriptions()->subscribe(
            'https://example.com/wh',
            ['message_created'],
            '0.1.8',
            'my_secret'
        );
        $req = $this->mockHttp->getLastRequest();
        $json = isset($req['options']['json']) ? $req['options']['json'] : [];
        $this->assertArrayHasKey('update_types', $json);
        $this->assertArrayHasKey('version', $json);
        $this->assertArrayHasKey('secret_key', $json);
        $this->assertEquals('my_secret', $json['secret_key']);
    }

    public function testSubscriptionsGetSubscriptionsEmpty()
    {
        $this->mockHttp->setResponse(200, '{}');
        $subs = $this->client->subscriptions()->getSubscriptions();
        $this->assertEmpty($subs);
    }

    public function testSubscriptionsGetUpdatesWithAllParams()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'updates' => [],
            'marker'  => null,
        ]));
        $this->client->subscriptions()->getUpdates(50, 30, 100, ['message_created']);
        $req = $this->mockHttp->getLastRequest();
        $query = isset($req['options']['query']) ? $req['options']['query'] : [];
        $this->assertEquals(50, $query['limit']);
        $this->assertEquals(30, $query['timeout']);
        $this->assertEquals(100, $query['marker']);
        $this->assertEquals('message_created', $query['types']);
    }

    // =====================================================================
    // Callbacks — дополнительные edge cases
    // =====================================================================

    public function testCallbacksAnswerCallbackWithNotification()
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->callbacks()->answerCallback('cb_123', null, 'Уведомление!');
        $req = $this->mockHttp->getLastRequest();
        $json = isset($req['options']['json']) ? $req['options']['json'] : [];
        $this->assertArrayHasKey('notification', $json);
        $this->assertEquals('Уведомление!', $json['notification']);
    }

    // =====================================================================
    // Uploads — дополнительные edge cases
    // =====================================================================

    public function testUploadsUploadFileToUrlFileNotFoundThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxFileException::class);
        $this->client->uploads()->uploadFileToUrl('https://upload.max.ru/abc', '/nonexistent/file.txt');
    }

    public function testUploadsUploadFileToUrlEmptyUrlThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->uploads()->uploadFileToUrl('', '/tmp/file.txt');
    }

    public function testUploadsUploadFileToUrlBadResponseThrows()
    {
        // Mock HttpClient вернёт не-JSON ответ
        $this->mockHttp->setResponse(200, 'NOT_JSON');
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tmpFile, 'test content');

        try {
            $this->expectException(\MaxBotSdk\Exception\MaxFileException::class);
            $this->client->uploads()->uploadFileToUrl('https://upload.max.ru/abc', $tmpFile);
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testUploadsUploadFileToUrlSuccess()
    {
        $this->mockHttp->setResponse(200, json_encode([
            'token' => 'tok_uploaded',
        ]));
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tmpFile, 'test content');

        try {
            $result = $this->client->uploads()->uploadFileToUrl('https://upload.max.ru/abc', $tmpFile);
            $this->assertInstanceOf(UploadResult::class, $result);
            $this->assertEquals('tok_uploaded', $result->getToken());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testUploadsGetVideoInfoEmptyTokenThrows()
    {
        $this->expectException(\MaxBotSdk\Exception\MaxValidationException::class);
        $this->client->uploads()->getVideoInfo('');
    }
}
