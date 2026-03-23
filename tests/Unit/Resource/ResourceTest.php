<?php

declare(strict_types=1);

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
use MaxBotSdk\Enum\UploadType;
use MaxBotSdk\Exception\MaxValidationException;
use MaxBotSdk\Http\RetryHandler;
use MaxBotSdk\ResponseDecoder;
use MaxBotSdk\Tests\Helper\MockHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResourceTest extends TestCase
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

    // =====================================================================
    // Bot
    // =====================================================================

    #[Test]
    public function botGetMeReturnsUserDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'user_id'  => 42,
            'name'     => 'TestBot',
            'username' => 'testbot',
            'is_bot'   => true,
        ]));
        $user = $this->client->bot()->getMe();

        self::assertInstanceOf(User::class, $user);
        self::assertSame(42, $user->getUserId());
        self::assertSame('TestBot', $user->getName());
        self::assertTrue($user->isBot());
        self::assertSame('GET', $this->mockHttp->getLastRequest()['method']);
    }

    // =====================================================================
    // Chats
    // =====================================================================

    #[Test]
    public function chatsGetChatsReturnsPaginatedResult(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'chats'  => [
                ['chat_id' => 1, 'type' => 'chat', 'title' => 'Group1'],
                ['chat_id' => 2, 'type' => 'chat', 'title' => 'Group2'],
            ],
            'marker' => 'next_page',
        ]));

        $result = $this->client->chats()->getChats(10);
        self::assertInstanceOf(PaginatedResult::class, $result);
        $items = $result->getItems();
        self::assertCount(2, $items);
        self::assertInstanceOf(Chat::class, $items[0]);
        self::assertSame(1, $items[0]->getChatId());
        self::assertTrue($result->hasMore());
    }

    #[Test]
    public function chatsGetChatReturnsChatDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'chat_id' => 123,
            'type'    => 'dialog',
            'title'   => 'Private',
        ]));
        $chat = $this->client->chats()->getChat(123);
        self::assertInstanceOf(Chat::class, $chat);
        self::assertSame(123, $chat->getChatId());
        self::assertSame('dialog', $chat->getType());
    }

    #[Test]
    public function chatsEditChatReturnsChatDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'chat_id' => 123,
            'title'   => 'Updated',
        ]));
        $chat = $this->client->chats()->editChat(123, ['title' => 'Updated']);
        self::assertInstanceOf(Chat::class, $chat);
        self::assertSame('PATCH', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function chatsDeleteChatReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->chats()->deleteChat(123);
        self::assertInstanceOf(ActionResult::class, $result);
        self::assertTrue($result->isSuccess());
        self::assertSame('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function chatsSendActionReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->chats()->sendAction(123, 'typing_on');
        self::assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        self::assertSame('POST', $req['method']);
        self::assertStringContainsString('/actions', $req['url']);
    }

    #[Test]
    public function chatsGetPinnedMessageReturnsMessage(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => [
                'mid'  => 'msg_1',
                'text' => 'Pinned',
            ],
        ]));
        $msg = $this->client->chats()->getPinnedMessage(123);
        self::assertInstanceOf(Message::class, $msg);
    }

    #[Test]
    public function chatsGetPinnedMessageReturnsNullWhenEmpty(): void
    {
        $this->mockHttp->setResponse(200, '{}');
        $msg = $this->client->chats()->getPinnedMessage(123);
        self::assertNull($msg);
    }

    #[Test]
    public function chatsPinMessageReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->chats()->pinMessage(123, 'mid_456');
        self::assertInstanceOf(ActionResult::class, $result);
        self::assertSame('PUT', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function chatsUnpinMessageReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->chats()->unpinMessage(123);
        self::assertInstanceOf(ActionResult::class, $result);
        self::assertSame('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    // =====================================================================
    // Members
    // =====================================================================

    #[Test]
    public function membersGetMembersReturnsPaginatedResult(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'members' => [
                ['user_id' => 10, 'name' => 'User1'],
            ],
        ]));
        $result = $this->client->members()->getMembers(123);
        self::assertInstanceOf(PaginatedResult::class, $result);
        $items = $result->getItems();
        self::assertCount(1, $items);
        self::assertInstanceOf(ChatMember::class, $items[0]);
    }

    #[Test]
    public function membersAddMembersReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->addMembers(123, [1, 2, 3]);
        self::assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        self::assertSame('POST', $req['method']);
        self::assertStringContainsString('/members', $req['url']);
    }

    #[Test]
    public function membersRemoveMemberReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->removeMember(123, 456);
        self::assertInstanceOf(ActionResult::class, $result);
        self::assertSame('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function membersGetMyMembershipReturnsChatMemberDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'user_id'  => 99,
            'name'     => 'Bot',
            'is_admin' => true,
        ]));
        $member = $this->client->members()->getMyMembership(123);
        self::assertInstanceOf(ChatMember::class, $member);
        self::assertSame(99, $member->getUserId());
    }

    #[Test]
    public function membersLeaveChatReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->leaveChat(123);
        self::assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        self::assertSame('DELETE', $req['method']);
        self::assertStringContainsString('/members/me', $req['url']);
    }

    #[Test]
    public function membersGetAdminsReturnsPaginatedResult(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'members' => [
                ['user_id' => 5, 'name' => 'Admin1', 'is_admin' => true],
            ],
        ]));
        $result = $this->client->members()->getAdmins(123);
        self::assertInstanceOf(PaginatedResult::class, $result);
    }

    #[Test]
    public function membersAddAdminReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->addAdmin(123, 456);
        self::assertInstanceOf(ActionResult::class, $result);
        self::assertSame('POST', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function membersRemoveAdminReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->members()->removeAdmin(123, 456);
        self::assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        self::assertSame('DELETE', $req['method']);
        self::assertStringContainsString('/admins', $req['url']);
    }

    // =====================================================================
    // Messages
    // =====================================================================

    #[Test]
    public function messagesSendMessageReturnsMessageDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => [
                'mid'  => 'm1',
                'text' => 'Hello',
            ],
            'sender' => ['user_id' => 1, 'name' => 'Bot'],
        ]));
        $msg = $this->client->messages()->sendMessage(
            ['text' => 'Hello'],
            null,
            123,
        );
        self::assertInstanceOf(Message::class, $msg);
        self::assertSame('POST', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function messagesGetMessageReturnsMessageDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => ['mid' => 'mid_123', 'text' => 'Hi'],
        ]));
        $msg = $this->client->messages()->getMessage('mid_123');
        self::assertInstanceOf(Message::class, $msg);
    }

    #[Test]
    public function messagesGetMessagesReturnsPaginatedResult(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'messages' => [
                ['message' => ['mid' => 'm1', 'text' => 'Msg1']],
            ],
        ]));
        $result = $this->client->messages()->getMessages(123);
        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertCount(1, $result->getItems());
    }

    #[Test]
    public function messagesEditMessageReturnsMessageDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => ['mid' => 'mid_123', 'text' => 'Updated'],
        ]));
        $msg = $this->client->messages()->editMessage('mid_123', ['text' => 'Updated']);
        self::assertInstanceOf(Message::class, $msg);
        self::assertSame('PUT', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function messagesDeleteMessageReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->messages()->deleteMessage('mid_123');
        self::assertInstanceOf(ActionResult::class, $result);
        self::assertSame('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function messagesSendTextReturnsMessageDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => ['mid' => 'm2', 'text' => 'Привет'],
        ]));
        $msg = $this->client->messages()->sendText('Привет', 123);
        self::assertInstanceOf(Message::class, $msg);
        $req = $this->mockHttp->getLastRequest();
        self::assertSame('POST', $req['method']);
        self::assertStringContainsString('/messages', $req['url']);
    }

    #[Test]
    public function messagesSendTextWithKeyboardReturnsMessageDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => ['mid' => 'm4'],
        ]));
        $keyboard = [
            [
                ['type' => 'callback', 'text' => 'Кнопка', 'payload' => 'btn1'],
            ],
        ];
        $msg = $this->client->messages()->sendTextWithKeyboard('Выберите:', 123, $keyboard);
        self::assertInstanceOf(Message::class, $msg);
        self::assertSame('POST', $this->mockHttp->getLastRequest()['method']);
    }

    // =====================================================================
    // Subscriptions
    // =====================================================================

    #[Test]
    public function subscriptionsSubscribeReturnsSubscription(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'url'  => 'https://example.com/webhook',
            'time' => 1234567890,
        ]));
        $result = $this->client->subscriptions()->subscribe('https://example.com/webhook');
        self::assertInstanceOf(Subscription::class, $result);
        self::assertSame('POST', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function subscriptionsGetSubscriptionsReturnsArray(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'subscriptions' => [
                ['url' => 'https://example.com', 'time' => 1234567890],
            ],
        ]));
        $subs = $this->client->subscriptions()->getSubscriptions();
        self::assertIsArray($subs);
        self::assertCount(1, $subs);
        self::assertInstanceOf(Subscription::class, $subs[0]);
    }

    #[Test]
    public function subscriptionsUnsubscribeReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->subscriptions()->unsubscribe('https://example.com/webhook');
        self::assertInstanceOf(ActionResult::class, $result);
        self::assertSame('DELETE', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function subscriptionsGetUpdatesReturnsUpdatesResult(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'updates' => [
                ['update_type' => 'message_created', 'timestamp' => 1234567890],
            ],
            'marker' => 42,
        ]));
        $result = $this->client->subscriptions()->getUpdates(50, 10);
        self::assertInstanceOf(UpdatesResult::class, $result);
        self::assertCount(1, $result->getUpdates());
        self::assertInstanceOf(Update::class, $result->getUpdates()[0]);
        self::assertSame(42, $result->getMarker());
    }

    #[Test]
    public function subscriptionsHttpUrlThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        $this->client->subscriptions()->subscribe('http://example.com');
    }

    // =====================================================================
    // Callbacks
    // =====================================================================

    #[Test]
    public function callbacksAnswerCallbackReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $result = $this->client->callbacks()->answerCallback('cb_123', null, 'Готово!');
        self::assertInstanceOf(ActionResult::class, $result);
        $req = $this->mockHttp->getLastRequest();
        self::assertSame('POST', $req['method']);
        self::assertStringContainsString('/answers', $req['url']);
    }

    #[Test]
    public function callbacksAnswerCallbackWithMessage(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->callbacks()->answerCallback(
            'cb_123',
            ['text' => 'Updated text'],
        );
        $req = $this->mockHttp->getLastRequest();
        $json = $req['options']['json'] ?? [];
        self::assertArrayHasKey('message', $json);
    }

    #[Test]
    public function callbacksEmptyIdThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        $this->client->callbacks()->answerCallback('');
    }

    // =====================================================================
    // Uploads
    // =====================================================================

    #[Test]
    public function uploadsGetUploadUrlReturnsUploadResult(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'url' => 'https://upload.max.ru/abc123',
        ]));
        $result = $this->client->uploads()->getUploadUrl(UploadType::Image);
        self::assertInstanceOf(UploadResult::class, $result);
        self::assertSame('https://upload.max.ru/abc123', $result->getUrl());
        self::assertSame('POST', $this->mockHttp->getLastRequest()['method']);
    }

    #[Test]
    public function uploadsGetVideoInfoReturnsVideoInfoDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'token'    => 'vid_abc',
            'url'      => 'https://cdn.max.ru/video.mp4',
            'width'    => 1920,
            'height'   => 1080,
            'duration' => 120,
        ]));
        $info = $this->client->uploads()->getVideoInfo('vid_abc');
        self::assertInstanceOf(VideoInfo::class, $info);
        self::assertSame('vid_abc', $info->getToken());
        self::assertSame(1920, $info->getWidth());
        self::assertSame(1080, $info->getHeight());
        self::assertSame(120, $info->getDuration());
        self::assertSame('GET', $this->mockHttp->getLastRequest()['method']);
    }
}
