<?php

namespace MaxBotSdk\Tests\Unit\DTO;

use MaxBotSdk\DTO\AbstractDto;
use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\Attachment;
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
use PHPUnit\Framework\TestCase;

/**
 * Тесты для всех DTO классов.
 */
class DtoTest extends TestCase
{
    // --- AbstractDto ---

    public function testAllDtosExtendAbstractDto()
    {
        $dtos = [
            User::fromArray([]),
            Chat::fromArray([]),
            ChatMember::fromArray([]),
            Attachment::fromArray([]),
            Message::fromArray([]),
            Update::fromArray([]),
            Subscription::fromArray([]),
            UploadResult::fromArray([]),
            VideoInfo::fromArray([]),
            ActionResult::fromArray([]),
        ];

        foreach ($dtos as $dto) {
            $this->assertInstanceOf(AbstractDto::class, $dto);
        }
    }

    // --- User ---

    public function testUserFromArray()
    {
        $user = User::fromArray([
            'user_id'  => 123,
            'name'     => 'TestBot',
            'username' => 'test_bot',
            'is_bot'   => true,
        ]);

        $this->assertEquals(123, $user->getUserId());
        $this->assertEquals('TestBot', $user->getName());
        $this->assertEquals('test_bot', $user->getUsername());
        $this->assertTrue($user->isBot());
    }

    public function testUserFromEmptyArray()
    {
        $user = User::fromArray([]);
        $this->assertEquals(0, $user->getUserId());
        $this->assertEquals('', $user->getName());
        $this->assertNull($user->getUsername());
        $this->assertFalse($user->isBot());
    }

    public function testUserToArray()
    {
        $data = [
            'user_id'  => 42,
            'name'     => 'Bot',
            'username' => 'bot42',
            'is_bot'   => true,
        ];
        $user = User::fromArray($data);
        $arr = $user->toArray();
        $this->assertEquals(42, $arr['user_id']);
        $this->assertEquals('Bot', $arr['name']);
    }

    // --- Chat ---

    public function testChatFromArray()
    {
        $chat = Chat::fromArray([
            'chat_id'            => 456,
            'type'               => 'chat',
            'status'             => 'active',
            'title'              => 'Test Chat',
            'participants_count' => 5,
            'is_public'          => true,
        ]);

        $this->assertEquals(456, $chat->getChatId());
        $this->assertEquals('chat', $chat->getType());
        $this->assertEquals('active', $chat->getStatus());
        $this->assertEquals('Test Chat', $chat->getTitle());
        $this->assertEquals(5, $chat->getParticipantsCount());
        $this->assertTrue($chat->isPublic());
    }

    public function testChatFromEmptyArray()
    {
        $chat = Chat::fromArray([]);
        $this->assertEquals(0, $chat->getChatId());
        $this->assertEquals('', $chat->getTitle());
        $this->assertFalse($chat->isPublic());
    }

    // --- ChatMember ---

    public function testChatMemberFromArray()
    {
        $member = ChatMember::fromArray([
            'user_id'  => 789,
            'name'     => 'User1',
            'is_admin' => true,
            'is_owner' => false,
        ]);

        $this->assertEquals(789, $member->getUserId());
        $this->assertEquals('User1', $member->getName());
        $this->assertTrue($member->isAdmin());
        $this->assertFalse($member->isOwner());
    }

    // --- Attachment ---

    public function testAttachmentFromArray()
    {
        $att = Attachment::fromArray([
            'type'    => 'image',
            'payload' => ['token' => 'abc123'],
        ]);

        $this->assertEquals('image', $att->getType());
        $this->assertEquals(['token' => 'abc123'], $att->getPayload());
        $this->assertEquals('abc123', $att->getPayloadValue('token'));
        $this->assertNull($att->getPayloadValue('nonexistent'));
        $this->assertEquals('default', $att->getPayloadValue('nonexistent', 'default'));
    }

    // --- Message ---

    public function testMessageFromArray()
    {
        $msg = Message::fromArray([
            'body' => [
                'mid'  => 'msg_001',
                'text' => 'Привет!',
            ],
            'sender' => [
                'user_id' => 1,
                'name'    => 'Bot',
            ],
            'recipient' => [
                'chat_id' => 100,
            ],
            'timestamp' => 1234567890,
        ]);

        $this->assertEquals('msg_001', $msg->getMessageId());
        $this->assertEquals('Привет!', $msg->getText());
        $this->assertNotNull($msg->getSender());
        $this->assertEquals(1, $msg->getSender()->getUserId());
        $this->assertEquals(100, $msg->getChatId());
        $this->assertEquals(1234567890, $msg->getTimestamp());
    }

    public function testMessageFromEmptyArray()
    {
        $msg = Message::fromArray([]);
        $this->assertNull($msg->getMessageId());
        $this->assertNull($msg->getText());
        $this->assertNull($msg->getSender());
        $this->assertEmpty($msg->getAttachments());
    }

    public function testMessageWithAttachments()
    {
        $msg = Message::fromArray([
            'body' => [
                'text' => 'С картинкой',
                'attachments' => [
                    ['type' => 'image', 'payload' => ['token' => 'img1']],
                    ['type' => 'file', 'payload' => ['token' => 'file1']],
                ],
            ],
        ]);

        $this->assertCount(2, $msg->getAttachments());
        $this->assertEquals('image', $msg->getAttachments()[0]->getType());
    }

    public function testMessageToArrayReconstructs()
    {
        $msg = Message::fromArray([
            'body' => ['mid' => 'm1', 'text' => 'Hello'],
            'sender' => ['user_id' => 1, 'name' => 'Bot'],
            'timestamp' => 999,
        ]);

        $arr = $msg->toArray();
        $this->assertEquals('m1', $arr['message_id']);
        $this->assertEquals('Hello', $arr['text']);
        $this->assertNotNull($arr['sender']);
        $this->assertEquals(999, $arr['timestamp']);
    }

    // --- Update ---

    public function testUpdateFromArray()
    {
        $upd = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp'   => 1111111111,
            'message'     => [
                'body' => ['text' => 'Hello'],
                'sender' => ['user_id' => 1, 'name' => 'Bot'],
            ],
        ]);

        $this->assertEquals('message_created', $upd->getUpdateType());
        $this->assertEquals(1111111111, $upd->getTimestamp());
        $this->assertNotNull($upd->getMessage());
        $this->assertEquals('Hello', $upd->getMessage()->getText());
    }

    public function testUpdateCallbackShortcuts()
    {
        $upd = Update::fromArray([
            'update_type' => 'message_callback',
            'timestamp'   => 222,
            'callback'    => [
                'callback_id' => 'cb_123',
                'payload'     => 'btn_click',
            ],
        ]);

        $this->assertEquals('cb_123', $upd->getCallbackId());
        $this->assertEquals('btn_click', $upd->getCallbackPayload());
    }

    public function testUpdateBotStarted()
    {
        $upd = Update::fromArray([
            'update_type' => 'bot_started',
            'timestamp'   => 333,
            'user'        => ['user_id' => 99, 'name' => 'NewUser'],
        ]);

        $this->assertEquals('bot_started', $upd->getUpdateType());
        $this->assertNotNull($upd->getUser());
        $this->assertEquals(99, $upd->getUser()->getUserId());
    }

    public function testUpdateToArrayReconstructs()
    {
        $upd = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp'   => 555,
            'message'     => [
                'body' => ['text' => 'Test'],
            ],
        ]);

        $arr = $upd->toArray();
        $this->assertEquals('message_created', $arr['update_type']);
        $this->assertEquals(555, $arr['timestamp']);
        $this->assertArrayHasKey('message', $arr);
    }

    // --- Subscription ---

    public function testSubscriptionFromArray()
    {
        $sub = Subscription::fromArray([
            'url'          => 'https://example.com/webhook',
            'time'         => 1234567890,
            'update_types' => ['message_created', 'message_callback'],
        ]);

        $this->assertEquals('https://example.com/webhook', $sub->getUrl());
        $this->assertEquals(1234567890, $sub->getTime());
        $this->assertCount(2, $sub->getUpdateTypes());
    }

    // --- UploadResult ---

    public function testUploadResultFromArray()
    {
        $res = UploadResult::fromArray([
            'url'   => 'https://upload.example.com/abc',
            'token' => 'tok_123',
        ]);

        $this->assertEquals('https://upload.example.com/abc', $res->getUrl());
        $this->assertEquals('tok_123', $res->getToken());
    }

    public function testUploadResultFromUrl()
    {
        $res = UploadResult::fromUrl('https://upload.example.com/xyz');
        $this->assertEquals('https://upload.example.com/xyz', $res->getUrl());
        $this->assertEquals('', $res->getToken());
    }

    public function testUploadResultFromToken()
    {
        $res = UploadResult::fromToken('tok_abc');
        $this->assertEquals('', $res->getUrl());
        $this->assertEquals('tok_abc', $res->getToken());
    }

    // --- PaginatedResult ---

    public function testPaginatedResultBasic()
    {
        $result = PaginatedResult::fromApiResponse([
            'chats'  => [
                ['chat_id' => 1, 'title' => 'Chat 1'],
                ['chat_id' => 2, 'title' => 'Chat 2'],
            ],
            'marker' => 100,
        ], 'chats');

        $this->assertEquals(2, $result->count());
        $this->assertTrue($result->hasMore());
        $this->assertEquals(100, $result->getMarker());
    }

    public function testPaginatedResultWithDtoMapping()
    {
        $result = PaginatedResult::fromApiResponse([
            'chats' => [
                ['chat_id' => 1, 'title' => 'A'],
                ['chat_id' => 2, 'title' => 'B'],
            ],
        ], 'chats', Chat::class);

        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertInstanceOf(Chat::class, $items[0]);
        $this->assertEquals('A', $items[0]->getTitle());
    }

    public function testPaginatedResultNoMarker()
    {
        $result = PaginatedResult::fromArray([
            'items' => [1, 2, 3],
        ]);

        $this->assertEquals(3, $result->count());
        $this->assertFalse($result->hasMore());
        $this->assertNull($result->getMarker());
    }

    public function testPaginatedResultEmpty()
    {
        $result = PaginatedResult::fromApiResponse([], 'items');
        $this->assertEquals(0, $result->count());
        $this->assertFalse($result->hasMore());
    }

    public function testPaginatedResultCountable()
    {
        $result = PaginatedResult::fromArray([
            'items' => [1, 2, 3],
        ]);
        $this->assertCount(3, $result);
    }

    public function testPaginatedResultIteratorAggregate()
    {
        $result = PaginatedResult::fromArray([
            'items' => ['a', 'b', 'c'],
        ]);

        $collected = [];
        foreach ($result as $item) {
            $collected[] = $item;
        }
        $this->assertEquals(['a', 'b', 'c'], $collected);
    }

    // --- ActionResult ---

    public function testActionResultFromArray()
    {
        $result = ActionResult::fromArray(['success' => true]);
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getMessage());
    }

    public function testActionResultWithMessage()
    {
        $result = ActionResult::fromArray([
            'success' => true,
            'message' => 'Операция выполнена',
        ]);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Операция выполнена', $result->getMessage());
    }

    public function testActionResultSuccess()
    {
        $result = ActionResult::success();
        $this->assertTrue($result->isSuccess());
    }

    // --- UpdatesResult ---

    public function testUpdatesResultFromArray()
    {
        $result = UpdatesResult::fromArray([
            'updates' => [
                ['update_type' => 'message_created', 'timestamp' => 123],
                ['update_type' => 'message_callback', 'timestamp' => 456],
            ],
            'marker' => 42,
        ]);

        $this->assertEquals(2, $result->count());
        $this->assertTrue($result->hasMore());
        $this->assertEquals(42, $result->getMarker());
        $this->assertInstanceOf(Update::class, $result->getUpdates()[0]);
    }

    public function testUpdatesResultEmpty()
    {
        $result = UpdatesResult::fromArray([]);
        $this->assertEquals(0, $result->count());
        $this->assertFalse($result->hasMore());
    }

    // --- VideoInfo ---

    public function testVideoInfoFromArray()
    {
        $info = VideoInfo::fromArray([
            'token'    => 'vid_abc',
            'url'      => 'https://cdn.max.ru/video.mp4',
            'width'    => 1920,
            'height'   => 1080,
            'duration' => 120,
            'thumbnail' => ['url' => 'https://cdn.max.ru/thumb.jpg'],
        ]);

        $this->assertEquals('vid_abc', $info->getToken());
        $this->assertEquals('https://cdn.max.ru/video.mp4', $info->getUrl());
        $this->assertEquals(1920, $info->getWidth());
        $this->assertEquals(1080, $info->getHeight());
        $this->assertEquals(120, $info->getDuration());
        $this->assertNotNull($info->getThumbnail());
    }

    public function testVideoInfoFromEmptyArray()
    {
        $info = VideoInfo::fromArray([]);
        $this->assertEquals('', $info->getToken());
        $this->assertEquals('', $info->getUrl());
        $this->assertNull($info->getWidth());
        $this->assertNull($info->getHeight());
        $this->assertNull($info->getDuration());
        $this->assertNull($info->getThumbnail());
    }

    public function testVideoInfoToArray()
    {
        $data = [
            'token'     => 'vid_xyz',
            'url'       => 'https://cdn.max.ru/v2.mp4',
            'width'     => 1280,
            'height'    => 720,
            'duration'  => 60,
            'thumbnail' => null,
        ];
        $info = VideoInfo::fromArray($data);
        $arr = $info->toArray();
        $this->assertEquals('vid_xyz', $arr['token']);
        $this->assertEquals(1280, $arr['width']);
        $this->assertEquals(720, $arr['height']);
        $this->assertEquals(60, $arr['duration']);
    }

    // --- AbstractDto: protected helper methods via concrete DTOs ---

    public function testAbstractDtoGetStringDefault()
    {
        // User::fromArray with missing 'name' → getString returns ''
        $user = User::fromArray(['user_id' => 1]);
        $this->assertEquals('', $user->getName());
    }

    public function testAbstractDtoGetIntDefault()
    {
        // User::fromArray with missing 'user_id' → getInt returns 0
        $user = User::fromArray([]);
        $this->assertEquals(0, $user->getUserId());
    }

    public function testAbstractDtoGetBoolDefault()
    {
        // User::fromArray with missing 'is_bot' → getBool returns false
        $user = User::fromArray(['user_id' => 1]);
        $this->assertFalse($user->isBot());
    }

    public function testAbstractDtoGetArrayDefault()
    {
        // Subscription::fromArray with missing 'update_types' → getArray returns []
        $sub = Subscription::fromArray(['url' => 'https://ex.com']);
        $this->assertEquals([], $sub->getUpdateTypes());
    }

    // --- Subscription: round-trip toArray ---

    public function testSubscriptionToArrayRoundTrip()
    {
        $sub = Subscription::fromArray([
            'url'          => 'https://example.com/wh',
            'time'         => 1234567890,
            'update_types' => ['message_created'],
            'version'      => '0.1.8',
        ]);

        $arr = $sub->toArray();
        $this->assertEquals('https://example.com/wh', $arr['url']);
        $this->assertEquals(1234567890, $arr['time']);
        $this->assertCount(1, $arr['update_types']);
    }

    // --- ChatMember: additional fields ---

    public function testChatMemberToString()
    {
        $member = ChatMember::fromArray([
            'user_id' => 789,
            'name'    => 'TestUser',
        ]);
        $str = (string) $member;
        $this->assertStringContainsString('TestUser', $str);
    }

    public function testChatMemberFromEmptyArray()
    {
        $member = ChatMember::fromArray([]);
        $this->assertEquals(0, $member->getUserId());
        $this->assertEquals('', $member->getName());
        $this->assertFalse($member->isAdmin());
        $this->assertFalse($member->isOwner());
    }

    // --- User: toString ---

    public function testUserToString()
    {
        $user = User::fromArray([
            'user_id' => 42,
            'name'    => 'BotName',
        ]);
        $str = (string) $user;
        $this->assertStringContainsString('BotName', $str);
    }

    // --- Chat: toString ---

    public function testChatToString()
    {
        $chat = Chat::fromArray([
            'chat_id' => 123,
            'title'   => 'MyChat',
        ]);
        $str = (string) $chat;
        $this->assertStringContainsString('MyChat', $str);
    }

    // --- Chat: toArray round-trip ---

    public function testChatToArrayRoundTrip()
    {
        $chat = Chat::fromArray([
            'chat_id' => 456,
            'type'    => 'chat',
            'title'   => 'Test',
            'status'  => 'active',
        ]);
        $arr = $chat->toArray();
        $this->assertEquals(456, $arr['chat_id']);
        $this->assertEquals('chat', $arr['type']);
        $this->assertEquals('Test', $arr['title']);
    }

    // --- ActionResult: failure ---

    public function testActionResultFailure()
    {
        $result = ActionResult::fromArray(['success' => false, 'message' => 'Ошибка']);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Ошибка', $result->getMessage());
    }

    // --- UpdatesResult: IteratorAggregate ---

    public function testUpdatesResultIteratorAggregate()
    {
        $result = UpdatesResult::fromArray([
            'updates' => [
                ['update_type' => 'message_created', 'timestamp' => 123],
                ['update_type' => 'message_callback', 'timestamp' => 456],
            ],
        ]);

        $collected = [];
        foreach ($result as $update) {
            $collected[] = $update;
        }
        $this->assertCount(2, $collected);
        $this->assertInstanceOf(Update::class, $collected[0]);
    }
}
