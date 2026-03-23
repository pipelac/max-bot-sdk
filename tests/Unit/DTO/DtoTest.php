<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DtoTest extends TestCase
{
    #[Test]
    public function allDtosExtendAbstractDto(): void
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
            self::assertInstanceOf(AbstractDto::class, $dto);
        }
    }

    // --- User ---

    #[Test]
    public function userFromArray(): void
    {
        $user = User::fromArray([
            'user_id'  => 123,
            'name'     => 'TestBot',
            'username' => 'test_bot',
            'is_bot'   => true,
        ]);

        self::assertSame(123, $user->getUserId());
        self::assertSame('TestBot', $user->getName());
        self::assertSame('test_bot', $user->getUsername());
        self::assertTrue($user->isBot());
    }

    #[Test]
    public function userFromEmptyArray(): void
    {
        $user = User::fromArray([]);
        self::assertSame(0, $user->getUserId());
        self::assertSame('', $user->getName());
        self::assertNull($user->getUsername());
        self::assertFalse($user->isBot());
    }

    #[Test]
    public function userToArray(): void
    {
        $data = [
            'user_id'  => 42,
            'name'     => 'Bot',
            'username' => 'bot42',
            'is_bot'   => true,
        ];
        $user = User::fromArray($data);
        $arr = $user->toArray();
        self::assertSame(42, $arr['user_id']);
        self::assertSame('Bot', $arr['name']);
    }

    // --- Chat ---

    #[Test]
    public function chatFromArray(): void
    {
        $chat = Chat::fromArray([
            'chat_id'            => 456,
            'type'               => 'chat',
            'status'             => 'active',
            'title'              => 'Test Chat',
            'participants_count' => 5,
            'is_public'          => true,
        ]);

        self::assertSame(456, $chat->getChatId());
        self::assertSame('chat', $chat->getType());
        self::assertSame('active', $chat->getStatus());
        self::assertSame('Test Chat', $chat->getTitle());
        self::assertSame(5, $chat->getParticipantsCount());
        self::assertTrue($chat->isPublic());
    }

    #[Test]
    public function chatFromEmptyArray(): void
    {
        $chat = Chat::fromArray([]);
        self::assertSame(0, $chat->getChatId());
        self::assertNull($chat->getTitle());
        self::assertFalse($chat->isPublic());
    }

    // --- ChatMember ---

    #[Test]
    public function chatMemberFromArray(): void
    {
        $member = ChatMember::fromArray([
            'user_id'  => 789,
            'name'     => 'User1',
            'is_admin' => true,
            'is_owner' => false,
        ]);

        self::assertSame(789, $member->getUserId());
        self::assertSame('User1', $member->getName());
        self::assertTrue($member->isAdmin());
        self::assertFalse($member->isOwner());
    }

    // --- Attachment ---

    #[Test]
    public function attachmentFromArray(): void
    {
        $att = Attachment::fromArray([
            'type'    => 'image',
            'payload' => ['token' => 'abc123'],
        ]);

        self::assertSame('image', $att->getType());
        self::assertSame(['token' => 'abc123'], $att->getPayload());
        self::assertSame('abc123', $att->getPayloadValue('token'));
        self::assertNull($att->getPayloadValue('nonexistent'));
        self::assertSame('default', $att->getPayloadValue('nonexistent', 'default'));
    }

    // --- Message ---

    #[Test]
    public function messageFromArray(): void
    {
        $msg = Message::fromArray([
            'message' => [
                'mid'  => 'msg_001',
                'text' => 'Привет!',
                'sender' => [
                    'user_id' => 1,
                    'name'    => 'Bot',
                ],
                'recipient' => [
                    'chat_id' => 100,
                ],
            ],
            'timestamp' => 1234567890,
        ]);

        self::assertSame('msg_001', $msg->getMessageId());
        self::assertSame('Привет!', $msg->getText());
        self::assertNotNull($msg->getSender());
        self::assertSame(1, $msg->getSender()->getUserId());
        self::assertSame(1234567890, $msg->getTimestamp());
    }

    #[Test]
    public function messageFromEmptyArray(): void
    {
        $msg = Message::fromArray([]);
        self::assertSame('', $msg->getMessageId());
        self::assertNull($msg->getText());
        self::assertNull($msg->getSender());
        self::assertEmpty($msg->getAttachments());
    }

    #[Test]
    public function messageWithAttachments(): void
    {
        $msg = Message::fromArray([
            'message' => [
                'text'        => 'С картинкой',
                'attachments' => [
                    ['type' => 'image', 'payload' => ['token' => 'img1']],
                    ['type' => 'file', 'payload' => ['token' => 'file1']],
                ],
            ],
        ]);

        self::assertCount(2, $msg->getAttachments());
        self::assertSame('image', $msg->getAttachments()[0]->getType());
    }

    #[Test]
    public function messageToArrayReconstructs(): void
    {
        $msg = Message::fromArray([
            'message'   => ['mid' => 'm1', 'text' => 'Hello'],
            'sender'    => ['user_id' => 1, 'name' => 'Bot'],
            'timestamp' => 999,
        ]);

        $arr = $msg->toArray();
        self::assertSame('m1', $arr['message_id']);
        self::assertSame('Hello', $arr['text']);
    }

    // --- Update ---

    #[Test]
    public function updateFromArray(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp'   => 1111111111,
            'message'     => [
                'text'   => 'Hello',
                'sender' => ['user_id' => 1, 'name' => 'Bot'],
            ],
        ]);

        self::assertSame('message_created', $upd->getUpdateType());
        self::assertSame(1111111111, $upd->getTimestamp());
        self::assertNotNull($upd->getMessage());
        self::assertSame('Hello', $upd->getMessage()->getText());
    }

    #[Test]
    public function updateCallbackShortcuts(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'message_callback',
            'timestamp'   => 222,
            'callback'    => [
                'callback_id' => 'cb_123',
                'payload'     => 'btn_click',
            ],
        ]);

        $callback = $upd->getCallback();
        self::assertNotNull($callback);
        self::assertSame('cb_123', $callback['callback_id']);
        self::assertSame('btn_click', $callback['payload']);
    }

    #[Test]
    public function updateBotStarted(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'bot_started',
            'timestamp'   => 333,
            'user'        => ['user_id' => 99, 'name' => 'NewUser'],
        ]);

        self::assertSame('bot_started', $upd->getUpdateType());
        self::assertNotNull($upd->getUser());
        self::assertSame(99, $upd->getUser()->getUserId());
    }

    #[Test]
    public function updateToArrayReconstructs(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp'   => 555,
            'message'     => [
                'text' => 'Test',
            ],
        ]);

        $arr = $upd->toArray();
        self::assertSame('message_created', $arr['update_type']);
        self::assertSame(555, $arr['timestamp']);
        self::assertArrayHasKey('message', $arr);
    }

    // --- Subscription ---

    #[Test]
    public function subscriptionFromArray(): void
    {
        $sub = Subscription::fromArray([
            'url'          => 'https://example.com/webhook',
            'time'         => 1234567890,
            'update_types' => ['message_created', 'message_callback'],
        ]);

        self::assertSame('https://example.com/webhook', $sub->getUrl());
        self::assertSame(1234567890, $sub->getTime());
        self::assertCount(2, $sub->getUpdateTypes());
    }

    // --- UploadResult ---

    #[Test]
    public function uploadResultFromArray(): void
    {
        $res = UploadResult::fromArray([
            'url'   => 'https://upload.example.com/abc',
            'token' => 'tok_123',
        ]);

        self::assertSame('https://upload.example.com/abc', $res->getUrl());
        self::assertSame('tok_123', $res->getToken());
    }

    #[Test]
    public function uploadResultFromUrl(): void
    {
        $res = UploadResult::fromUrl('https://upload.example.com/xyz');
        self::assertSame('https://upload.example.com/xyz', $res->getUrl());
        self::assertSame('', $res->getToken());
    }

    #[Test]
    public function uploadResultFromToken(): void
    {
        $res = UploadResult::fromToken('tok_abc');
        self::assertSame('', $res->getUrl());
        self::assertSame('tok_abc', $res->getToken());
    }

    // --- PaginatedResult ---

    #[Test]
    public function paginatedResultWithDtoMapping(): void
    {
        $result = PaginatedResult::fromApiResponse([
            'chats'  => [
                ['chat_id' => 1, 'title' => 'A'],
                ['chat_id' => 2, 'title' => 'B'],
            ],
            'marker' => 100,
        ], 'chats', Chat::class);

        self::assertSame(2, $result->count());
        self::assertTrue($result->hasMore());
        self::assertSame(100, $result->getMarker());
        $items = $result->getItems();
        self::assertCount(2, $items);
        self::assertInstanceOf(Chat::class, $items[0]);
        self::assertSame('A', $items[0]->getTitle());
    }

    #[Test]
    public function paginatedResultNoMarker(): void
    {
        $result = PaginatedResult::fromApiResponse([
            'items' => [
                ['chat_id' => 1],
                ['chat_id' => 2],
                ['chat_id' => 3],
            ],
        ], 'items', Chat::class);

        self::assertSame(3, $result->count());
        self::assertFalse($result->hasMore());
        self::assertNull($result->getMarker());
    }

    #[Test]
    public function paginatedResultEmpty(): void
    {
        $result = PaginatedResult::fromApiResponse([], 'items', Chat::class);
        self::assertSame(0, $result->count());
        self::assertFalse($result->hasMore());
    }

    // --- ActionResult ---

    #[Test]
    public function actionResultFromArray(): void
    {
        $result = ActionResult::fromArray(['success' => true]);
        self::assertTrue($result->isSuccess());
        self::assertNull($result->getMessage());
    }

    #[Test]
    public function actionResultWithMessage(): void
    {
        $result = ActionResult::fromArray([
            'success' => true,
            'message' => 'Операция выполнена',
        ]);
        self::assertTrue($result->isSuccess());
        self::assertSame('Операция выполнена', $result->getMessage());
    }

    #[Test]
    public function actionResultSuccess(): void
    {
        $result = ActionResult::success();
        self::assertTrue($result->isSuccess());
    }

    // --- UpdatesResult ---

    #[Test]
    public function updatesResultFromArray(): void
    {
        $result = UpdatesResult::fromArray([
            'updates' => [
                ['update_type' => 'message_created', 'timestamp' => 123],
                ['update_type' => 'message_callback', 'timestamp' => 456],
            ],
            'marker' => 42,
        ]);

        self::assertSame(2, $result->count());
        self::assertTrue($result->hasMore());
        self::assertSame(42, $result->getMarker());
        self::assertInstanceOf(Update::class, $result->getUpdates()[0]);
    }

    #[Test]
    public function updatesResultEmpty(): void
    {
        $result = UpdatesResult::fromArray([]);
        self::assertSame(0, $result->count());
        self::assertFalse($result->hasMore());
    }

    // --- VideoInfo ---

    #[Test]
    public function videoInfoFromArray(): void
    {
        $info = VideoInfo::fromArray([
            'token'     => 'vid_abc',
            'url'       => 'https://cdn.max.ru/video.mp4',
            'width'     => 1920,
            'height'    => 1080,
            'duration'  => 120,
            'thumbnail' => ['url' => 'https://cdn.max.ru/thumb.jpg'],
        ]);

        self::assertSame('vid_abc', $info->getToken());
        self::assertSame('https://cdn.max.ru/video.mp4', $info->getUrl());
        self::assertSame(1920, $info->getWidth());
        self::assertSame(1080, $info->getHeight());
        self::assertSame(120, $info->getDuration());
        self::assertNotNull($info->getThumbnail());
    }

    #[Test]
    public function videoInfoFromEmptyArray(): void
    {
        $info = VideoInfo::fromArray([]);
        self::assertSame('', $info->getToken());
        self::assertSame('', $info->getUrl());
        self::assertNull($info->getWidth());
        self::assertNull($info->getHeight());
        self::assertNull($info->getDuration());
        self::assertNull($info->getThumbnail());
    }

    #[Test]
    public function videoInfoToArray(): void
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
        self::assertSame('vid_xyz', $arr['token']);
        self::assertSame(1280, $arr['width']);
        self::assertSame(720, $arr['height']);
        self::assertSame(60, $arr['duration']);
    }

    // =====================================================================
    // Edge Cases — Nullable Getters
    // =====================================================================

    // --- User edge cases ---

    #[Test]
    public function userGetAvatarUrl(): void
    {
        $user = User::fromArray([
            'user_id'    => 1,
            'name'       => 'Bot',
            'avatar_url' => 'https://cdn.max.ru/ava.jpg',
        ]);
        self::assertSame('https://cdn.max.ru/ava.jpg', $user->getAvatarUrl());
    }

    #[Test]
    public function userGetAvatarUrlNull(): void
    {
        $user = User::fromArray(['user_id' => 1, 'name' => 'Bot']);
        self::assertNull($user->getAvatarUrl());
    }

    #[Test]
    public function userGetDescription(): void
    {
        $user = User::fromArray([
            'user_id'     => 1,
            'name'        => 'Bot',
            'description' => 'Мой бот',
        ]);
        self::assertSame('Мой бот', $user->getDescription());
    }

    #[Test]
    public function userGetLastActivityTime(): void
    {
        $user = User::fromArray([
            'user_id'            => 1,
            'name'               => 'Bot',
            'last_activity_time' => 1700000000,
        ]);
        self::assertSame(1700000000, $user->getLastActivityTime());
    }

    #[Test]
    public function userGetLastActivityTimeNull(): void
    {
        $user = User::fromArray(['user_id' => 1, 'name' => 'Bot']);
        self::assertNull($user->getLastActivityTime());
    }

    // --- Chat edge cases ---

    #[Test]
    public function chatGetDescription(): void
    {
        $chat = Chat::fromArray([
            'chat_id'     => 1,
            'type'        => 'chat',
            'description' => 'Описание чата',
        ]);
        self::assertSame('Описание чата', $chat->getDescription());
    }

    #[Test]
    public function chatGetDescriptionNull(): void
    {
        $chat = Chat::fromArray(['chat_id' => 1, 'type' => 'chat']);
        self::assertNull($chat->getDescription());
    }

    #[Test]
    public function chatGetOwner(): void
    {
        $chat = Chat::fromArray([
            'chat_id'  => 1,
            'type'     => 'chat',
            'owner_id' => 42,
        ]);
        self::assertNotNull($chat->getOwner());
        self::assertInstanceOf(User::class, $chat->getOwner());
        self::assertSame(42, $chat->getOwner()->getUserId());
    }

    #[Test]
    public function chatGetOwnerNull(): void
    {
        $chat = Chat::fromArray(['chat_id' => 1, 'type' => 'chat']);
        self::assertNull($chat->getOwner());
    }

    #[Test]
    public function chatGetOwnerFromNestedData(): void
    {
        $chat = Chat::fromArray([
            'chat_id' => 1,
            'type'    => 'chat',
            'owner'   => ['user_id' => 99, 'name' => 'Admin'],
        ]);
        self::assertNotNull($chat->getOwner());
        self::assertSame(99, $chat->getOwner()->getUserId());
        self::assertSame('Admin', $chat->getOwner()->getName());
    }

    #[Test]
    public function chatGetIcon(): void
    {
        $chat = Chat::fromArray([
            'chat_id' => 1,
            'type'    => 'chat',
            'icon'    => ['url' => 'https://cdn.max.ru/icon.png'],
        ]);
        self::assertIsArray($chat->getIcon());
        self::assertSame('https://cdn.max.ru/icon.png', $chat->getIcon()['url']);
    }

    #[Test]
    public function chatGetIconNull(): void
    {
        $chat = Chat::fromArray(['chat_id' => 1]);
        self::assertNull($chat->getIcon());
    }

    // --- ChatMember edge cases ---

    #[Test]
    public function chatMemberFromEmptyArray(): void
    {
        $member = ChatMember::fromArray([]);
        self::assertSame(0, $member->getUserId());
        self::assertSame('', $member->getName());
        self::assertNull($member->getUsername());
        self::assertNull($member->getAvatarUrl());
        self::assertFalse($member->isOwner());
        self::assertFalse($member->isAdmin());
    }

    #[Test]
    public function chatMemberAllFields(): void
    {
        $member = ChatMember::fromArray([
            'user_id'    => 100,
            'name'       => 'Admin',
            'username'   => 'admin_user',
            'avatar_url' => 'https://cdn.max.ru/admin.jpg',
            'is_owner'   => true,
            'is_admin'   => true,
        ]);
        self::assertSame(100, $member->getUserId());
        self::assertSame('admin_user', $member->getUsername());
        self::assertSame('https://cdn.max.ru/admin.jpg', $member->getAvatarUrl());
        self::assertTrue($member->isOwner());
        self::assertTrue($member->isAdmin());
    }

    // --- Message edge cases ---

    #[Test]
    public function messageGetFormat(): void
    {
        $msg = Message::fromArray([
            'message' => ['text' => 'Bold', 'format' => 'markdown'],
        ]);
        self::assertSame('markdown', $msg->getFormat());
    }

    #[Test]
    public function messageGetFormatNull(): void
    {
        $msg = Message::fromArray(['message' => ['text' => 'Plain']]);
        self::assertNull($msg->getFormat());
    }

    #[Test]
    public function messageGetRecipient(): void
    {
        $msg = Message::fromArray([
            'message'   => ['text' => 'Hi'],
            'recipient' => ['chat_id' => 555],
        ]);
        self::assertNotNull($msg->getRecipient());
        self::assertSame(555, $msg->getRecipient()['chat_id']);
    }

    #[Test]
    public function messageGetRecipientNull(): void
    {
        $msg = Message::fromArray(['message' => ['text' => 'Hi']]);
        self::assertNull($msg->getRecipient());
    }

    #[Test]
    public function messageGetLink(): void
    {
        $msg = Message::fromArray([
            'message' => ['text' => 'Hi'],
            'link'    => 'https://max.ru/chat/123/msg/456',
        ]);
        self::assertSame('https://max.ru/chat/123/msg/456', $msg->getLink());
    }

    #[Test]
    public function messageGetLinkNull(): void
    {
        $msg = Message::fromArray(['message' => ['text' => 'Hi']]);
        self::assertNull($msg->getLink());
    }

    #[Test]
    public function messageGetStat(): void
    {
        $msg = Message::fromArray([
            'message' => ['text' => 'Hi'],
            'stat'    => ['views' => 100],
        ]);
        self::assertNotNull($msg->getStat());
        self::assertSame(100, $msg->getStat()['views']);
    }

    #[Test]
    public function messageGetStatNull(): void
    {
        $msg = Message::fromArray(['message' => ['text' => 'Hi']]);
        self::assertNull($msg->getStat());
    }

    // --- Update edge cases ---

    #[Test]
    public function updateGetBody(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp'   => 111,
            'message'     => ['text' => 'Hi'],
        ]);
        self::assertIsArray($upd->getBody());
        self::assertArrayHasKey('message', $upd->getBody());
    }

    #[Test]
    public function updateGetMessageId(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp'   => 111,
            'message_id'  => 'mid_abc',
        ]);
        self::assertSame('mid_abc', $upd->getMessageId());
    }

    #[Test]
    public function updateGetMessageIdNull(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp'   => 111,
        ]);
        self::assertNull($upd->getMessageId());
    }

    #[Test]
    public function updateGetChatId(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp'   => 111,
            'chat_id'     => 777,
        ]);
        self::assertSame(777, $upd->getChatId());
    }

    #[Test]
    public function updateGetUserId(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'bot_started',
            'timestamp'   => 111,
            'user_id'     => 55,
        ]);
        self::assertSame(55, $upd->getUserId());
    }

    #[Test]
    public function updateGetUserIdNull(): void
    {
        $upd = Update::fromArray([
            'update_type' => 'bot_started',
            'timestamp'   => 111,
        ]);
        self::assertNull($upd->getUserId());
    }

    #[Test]
    public function updateFromEmptyArray(): void
    {
        $upd = Update::fromArray([]);
        self::assertSame('', $upd->getUpdateType());
        self::assertSame(0, $upd->getTimestamp());
        self::assertNull($upd->getMessage());
        self::assertNull($upd->getCallback());
        self::assertNull($upd->getUser());
    }

    // --- Subscription edge cases ---

    #[Test]
    public function subscriptionFromEmptyArray(): void
    {
        $sub = Subscription::fromArray([]);
        self::assertSame('', $sub->getUrl());
        self::assertNull($sub->getTime());
        self::assertEmpty($sub->getUpdateTypes());
        self::assertNull($sub->getVersion());
    }

    #[Test]
    public function subscriptionGetVersion(): void
    {
        $sub = Subscription::fromArray([
            'url'     => 'https://example.com',
            'version' => '0.1.8',
        ]);
        self::assertSame('0.1.8', $sub->getVersion());
    }

    #[Test]
    public function subscriptionToArray(): void
    {
        $sub = Subscription::fromArray([
            'url'          => 'https://example.com/webhook',
            'time'         => 1234567890,
            'update_types' => ['message_created'],
            'version'      => '0.1.8',
        ]);
        $arr = $sub->toArray();
        self::assertSame('https://example.com/webhook', $arr['url']);
        self::assertSame(1234567890, $arr['time']);
        self::assertContains('message_created', $arr['update_types']);
    }

    // --- Attachment edge cases ---

    #[Test]
    public function attachmentFromEmptyArray(): void
    {
        $att = Attachment::fromArray([]);
        self::assertSame('', $att->getType());
        self::assertEmpty($att->getPayload());
    }

    #[Test]
    public function attachmentToArray(): void
    {
        $att = Attachment::fromArray([
            'type'    => 'video',
            'payload' => ['token' => 'vid_1', 'url' => 'https://cdn.max.ru/v.mp4'],
        ]);
        $arr = $att->toArray();
        self::assertSame('video', $arr['type']);
        self::assertSame('vid_1', $arr['payload']['token']);
    }

    // --- PaginatedResult iteration ---

    #[Test]
    public function paginatedResultIterator(): void
    {
        $result = PaginatedResult::fromApiResponse([
            'chats' => [
                ['chat_id' => 1, 'title' => 'A'],
                ['chat_id' => 2, 'title' => 'B'],
            ],
        ], 'chats', Chat::class);

        $titles = [];
        foreach ($result as $chat) {
            self::assertInstanceOf(Chat::class, $chat);
            $titles[] = $chat->getTitle();
        }
        self::assertSame(['A', 'B'], $titles);
    }

    // --- UpdatesResult iteration ---

    #[Test]
    public function updatesResultIterator(): void
    {
        $result = UpdatesResult::fromArray([
            'updates' => [
                ['update_type' => 'message_created', 'timestamp' => 100],
                ['update_type' => 'bot_started', 'timestamp' => 200],
            ],
        ]);

        $types = [];
        foreach ($result as $update) {
            self::assertInstanceOf(Update::class, $update);
            $types[] = $update->getUpdateType();
        }
        self::assertSame(['message_created', 'bot_started'], $types);
    }
}
