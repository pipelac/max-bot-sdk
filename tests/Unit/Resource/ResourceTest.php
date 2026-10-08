<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit\Resource;

use MaxBotSdk\Client;
use MaxBotSdk\Config;
use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\Chat;
use MaxBotSdk\DTO\ChatMember;
use MaxBotSdk\DTO\CommentMessage;
use MaxBotSdk\DTO\CommentPayload;
use MaxBotSdk\DTO\Message;
use MaxBotSdk\DTO\PaginatedResult;
use MaxBotSdk\DTO\Subscription;
use MaxBotSdk\DTO\Update;
use MaxBotSdk\DTO\UpdatesResult;
use MaxBotSdk\DTO\UploadResult;
use MaxBotSdk\DTO\User;
use MaxBotSdk\DTO\VideoInfo;
use MaxBotSdk\Enum\TextFormat;
use MaxBotSdk\Enum\UploadType;
use MaxBotSdk\Exception\MaxValidationException;
use MaxBotSdk\Http\RetryHandler;
use MaxBotSdk\Resource\Comments;
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

    #[Test]
    public function botPatchCommandsReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, (string) json_encode([
            'success' => true,
            'message' => 'Commands updated',
        ]));

        $result = $this->client->bot()->patchCommands([
            ['name' => 'start', 'description' => 'Запустить бота'],
            ['name' => 'help', 'description' => 'Помощь'],
        ]);

        self::assertInstanceOf(ActionResult::class, $result);
        self::assertTrue($result->isSuccess());

        $lastRequest = $this->mockHttp->getLastRequest();
        self::assertNotNull($lastRequest);
        self::assertSame('PATCH', $lastRequest['method']);
        self::assertSame('/me/commands', $lastRequest['url']);
        self::assertArrayHasKey('json', $lastRequest['options']);
        self::assertSame([
            'commands' => [
                ['name' => 'start', 'description' => 'Запустить бота'],
                ['name' => 'help', 'description' => 'Помощь'],
            ],
        ], $lastRequest['options']['json']);
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

        $deprecationCaught = false;
        set_error_handler(function (int $errno, string $errstr) use (&$deprecationCaught) {
            if ($errno === \E_USER_DEPRECATED) {
                $deprecationCaught = true;
                return true;
            }
            return false;
        });

        $result = $this->client->chats()->getChats(10);
        restore_error_handler();

        self::assertTrue($deprecationCaught, 'Ожидалось предупреждение о депрекации');
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
    public function chatsGetChatByLinkReturnsChatDto(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'chat_id' => 456,
            'type'    => 'channel',
            'title'   => 'My Channel',
        ]));
        $chat = $this->client->chats()->getChatByLink('@my_channel');
        self::assertInstanceOf(Chat::class, $chat);
        self::assertSame(456, $chat->getChatId());
        self::assertSame('channel', $chat->getType());

        $req = $this->mockHttp->getLastRequest();
        self::assertSame('GET', $req['method']);
        self::assertStringContainsString('/chats/@my_channel', $req['url']);
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

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = [$errno, $errstr];
            return true;
        }, \E_USER_DEPRECATED);

        try {
            $result = $this->client->members()->addMembers(123, [1, 2, 3]);
        } finally {
            restore_error_handler();
        }

        self::assertInstanceOf(ActionResult::class, $result);
        self::assertCount(1, $deprecations);
        self::assertStringContainsString('addMembers() is deprecated', $deprecations[0][1]);

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
    public function callbacksAnswerCallbackWithDisableLinkPreview(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');
        $this->client->callbacks()->answerCallback(
            'cb_123',
            ['text' => 'Updated text'],
            null,
            true,
        );
        $req = $this->mockHttp->getLastRequest();
        $query = $req['options']['query'] ?? [];
        self::assertArrayHasKey('disable_link_preview', $query);
        self::assertSame('true', $query['disable_link_preview']);

        // Test with false
        $this->client->callbacks()->answerCallback(
            'cb_123',
            ['text' => 'Updated text'],
            null,
            false,
        );
        $req2 = $this->mockHttp->getLastRequest();
        $query2 = $req2['options']['query'] ?? [];
        self::assertArrayHasKey('disable_link_preview', $query2);
        self::assertSame('false', $query2['disable_link_preview']);

        // Test with null (default: key should not be present)
        $this->client->callbacks()->answerCallback(
            'cb_123',
            ['text' => 'Updated text'],
        );
        $req3 = $this->mockHttp->getLastRequest();
        $query3 = $req3['options']['query'] ?? [];
        self::assertArrayNotHasKey('disable_link_preview', $query3);
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

    // =====================================================================
    // Comments
    // =====================================================================

    #[Test]
    public function clientCommentsAccessorReturnsResource(): void
    {
        $comments = $this->client->comments();
        self::assertInstanceOf(Comments::class, $comments);
        self::assertSame($comments, $this->client->comments());
    }

    #[Test]
    public function commentsGetCommentsReturnsPaginatedResult(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'comments' => [
                [
                    'mid' => 'c_1',
                    'seq' => 10,
                    'text' => 'Great news!',
                    'date' => 1775700000,
                    'format' => 'markdown',
                    'chat_id' => 999,
                    'sender' => ['user_id' => 42, 'name' => 'Bob'],
                ],
            ],
            'marker' => 888,
        ]));

        $result = $this->client->comments()->getComments(
            'post_msg_100',
            20,
            'before_marker',
            'after_marker',
            ['c_1', 'c_2'],
        );

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertCount(1, $result->getItems());
        self::assertSame(888, $result->getMarker());

        $comment = $result->getItems()[0];
        self::assertInstanceOf(CommentMessage::class, $comment);
        self::assertSame('c_1', $comment->getMid());
        self::assertSame(10, $comment->getSeq());
        self::assertSame('Great news!', $comment->getText());
        self::assertSame('markdown', $comment->getFormatString());
        self::assertSame(TextFormat::Markdown, $comment->getFormat());
        self::assertSame(999, $comment->getChatId());
        self::assertNotNull($comment->getSender());
        self::assertSame(42, $comment->getSender()->getUserId());

        $lastReq = $this->mockHttp->getLastRequest();
        self::assertSame('GET', $lastReq['method']);
        self::assertStringContainsString('/messages/post_msg_100/comments', $lastReq['url']);
        self::assertSame([
            'count' => 20,
            'before' => 'before_marker',
            'after' => 'after_marker',
            'comment_ids' => 'c_1,c_2',
        ], $lastReq['options']['query']);
    }

    #[Test]
    public function commentsGetCommentsWithDefaults(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'comments' => [],
        ]));

        $result = $this->client->comments()->getComments('post_msg_100');

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertCount(0, $result->getItems());
        $lastReq = $this->mockHttp->getLastRequest();
        self::assertSame([], $lastReq['options']['query'] ?? []);
    }

    #[Test]
    public function commentsGetCommentReturnsCommentMessage(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => [
                'mid' => 'c_42',
                'seq' => 1,
                'text' => 'Single comment message',
                'date' => 1775700500,
            ],
        ]));

        $comment = $this->client->comments()->getComment('post_msg_100', 'c_42');

        self::assertInstanceOf(CommentMessage::class, $comment);
        self::assertSame('c_42', $comment->getMid());
        self::assertSame('Single comment message', $comment->getText());

        $lastReq = $this->mockHttp->getLastRequest();
        self::assertSame('GET', $lastReq['method']);
        self::assertStringContainsString('/messages/post_msg_100/comments/c_42', $lastReq['url']);
    }

    #[Test]
    public function commentsAddCommentWithStringReturnsCommentMessage(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => [
                'mid' => 'c_created_1',
                'seq' => 5,
                'text' => 'New reply text',
                'format' => 'markdown',
            ],
        ]));

        $comment = $this->client->comments()->addComment(
            'post_msg_100',
            'New reply text',
            TextFormat::Markdown,
            ['type' => 'reply', 'mid' => 'c_parent_0'],
        );

        self::assertInstanceOf(CommentMessage::class, $comment);
        self::assertSame('c_created_1', $comment->getMid());

        $lastReq = $this->mockHttp->getLastRequest();
        self::assertSame('POST', $lastReq['method']);
        self::assertStringContainsString('/messages/post_msg_100/comments', $lastReq['url']);
        self::assertSame([
            'text' => 'New reply text',
            'format' => 'markdown',
            'link' => ['type' => 'reply', 'mid' => 'c_parent_0'],
        ], $lastReq['options']['json']);
    }

    #[Test]
    public function commentsAddCommentWithPayloadReturnsCommentMessage(): void
    {
        $this->mockHttp->setResponse(200, json_encode([
            'message' => [
                'mid' => 'c_created_2',
                'seq' => 6,
                'text' => 'Created via builder',
                'format' => 'html',
            ],
        ]));

        $payload = CommentPayload::create('Created via builder')
            ->withFormat(TextFormat::Html)
            ->withReplyTo('c_parent_1');

        $comment = $this->client->comments()->addComment('post_msg_100', $payload);

        self::assertInstanceOf(CommentMessage::class, $comment);
        self::assertSame('c_created_2', $comment->getMid());

        $lastReq = $this->mockHttp->getLastRequest();
        self::assertSame('POST', $lastReq['method']);
        self::assertSame([
            'text' => 'Created via builder',
            'format' => 'html',
            'link' => ['type' => 'reply', 'mid' => 'c_parent_1'],
        ], $lastReq['options']['json']);
    }

    #[Test]
    public function commentsEditCommentWithStringReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');

        $result = $this->client->comments()->editComment(
            'post_msg_100',
            'c_edit_1',
            'Updated comment content',
            'markdown',
        );

        self::assertInstanceOf(ActionResult::class, $result);
        self::assertTrue($result->isSuccess());

        $lastReq = $this->mockHttp->getLastRequest();
        self::assertSame('PUT', $lastReq['method']);
        self::assertStringContainsString('/messages/post_msg_100/comments', $lastReq['url']);
        self::assertSame(['comment_id' => 'c_edit_1'], $lastReq['options']['query']);
        self::assertSame([
            'text' => 'Updated comment content',
            'format' => 'markdown',
        ], $lastReq['options']['json']);
    }

    #[Test]
    public function commentsEditCommentWithPayloadReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');

        $payload = CommentPayload::create('Updated via payload')
            ->withFormat(TextFormat::Markdown);

        $result = $this->client->comments()->editComment(
            'post_msg_100',
            'c_edit_2',
            $payload,
        );

        self::assertInstanceOf(ActionResult::class, $result);
        self::assertTrue($result->isSuccess());

        $lastReq = $this->mockHttp->getLastRequest();
        self::assertSame('PUT', $lastReq['method']);
        self::assertSame(['comment_id' => 'c_edit_2'], $lastReq['options']['query']);
        self::assertSame([
            'text' => 'Updated via payload',
            'format' => 'markdown',
        ], $lastReq['options']['json']);
    }

    #[Test]
    public function commentsDeleteCommentReturnsActionResult(): void
    {
        $this->mockHttp->setResponse(200, '{"success": true}');

        $result = $this->client->comments()->deleteComment('post_msg_100', 'c_del_1');

        self::assertInstanceOf(ActionResult::class, $result);
        self::assertTrue($result->isSuccess());

        $lastReq = $this->mockHttp->getLastRequest();
        self::assertSame('DELETE', $lastReq['method']);
        self::assertStringContainsString('/messages/post_msg_100/comments', $lastReq['url']);
        self::assertSame(['comment_id' => 'c_del_1'], $lastReq['options']['query']);
    }

    #[Test]
    public function commentsValidationExceptions(): void
    {
        $comments = $this->client->comments();

        try {
            $comments->getComments('');
            self::fail('Expected MaxValidationException on empty messageId');
        } catch (MaxValidationException) {
            self::assertTrue(true);
        }

        try {
            $comments->getComments('post_1', 0);
            self::fail('Expected MaxValidationException on count < 1');
        } catch (MaxValidationException) {
            self::assertTrue(true);
        }

        try {
            $comments->getComments('post_1', 101);
            self::fail('Expected MaxValidationException on count > 100');
        } catch (MaxValidationException) {
            self::assertTrue(true);
        }

        try {
            $comments->getComment('post_1', '   ');
            self::fail('Expected MaxValidationException on whitespace commentId');
        } catch (MaxValidationException) {
            self::assertTrue(true);
        }

        try {
            $comments->addComment('post_1', '');
            self::fail('Expected MaxValidationException on empty comment text');
        } catch (MaxValidationException) {
            self::assertTrue(true);
        }

        try {
            $comments->editComment('post_1', '', 'valid text');
            self::fail('Expected MaxValidationException on empty commentId');
        } catch (MaxValidationException) {
            self::assertTrue(true);
        }

        try {
            $comments->deleteComment('post_1', '');
            self::fail('Expected MaxValidationException on empty commentId');
        } catch (MaxValidationException) {
            self::assertTrue(true);
        }
    }
}
