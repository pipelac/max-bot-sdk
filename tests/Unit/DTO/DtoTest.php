<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit\DTO;

use MaxBotSdk\DTO\AbstractDto;
use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\Attachment;
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
use MaxBotSdk\Enum\UpdateType;
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
            CommentMessage::fromArray([]),
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
        $firstItem = $items[0];
        self::assertInstanceOf(Chat::class, $firstItem);
        self::assertSame('A', $firstItem->getTitle());
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

    // --- CommentMessage ---

    #[Test]
    public function commentMessageFromArrayFull(): void
    {
        $data = [
            'sender'    => ['user_id' => 123, 'name' => 'Alice'],
            'recipient' => ['chat_id' => 456, 'chat_type' => 'channel'],
            'timestamp' => 1700000000000,
            'link'      => ['type' => 'reply', 'mid' => 'mid.parent_1'],
            'body'      => [
                'mid'    => 'mid.comm_999',
                'seq'    => 5,
                'text'   => 'Текст комментария',
                'format' => 'markdown',
            ],
        ];

        $comment = CommentMessage::fromArray($data);

        self::assertSame('mid.comm_999', $comment->getCommentId());
        self::assertSame('Текст комментария', $comment->getText());
        self::assertSame(TextFormat::Markdown, $comment->getFormat());
        self::assertSame('markdown', $comment->getFormatString());
        self::assertSame(5, $comment->getSeq());
        self::assertNotNull($comment->getSender());
        self::assertSame(123, $comment->getSender()?->getUserId());
        self::assertFalse($comment->isChannelPost());
        self::assertSame(['chat_id' => 456, 'chat_type' => 'channel'], $comment->getRecipient());
        self::assertSame(456, $comment->getRecipientChatId());
        self::assertSame(1700000000000, $comment->getTimestamp());
        self::assertSame(1700000000, $comment->getDateTime()->getTimestamp());
        self::assertTrue($comment->hasReply());
        self::assertSame(['type' => 'reply', 'mid' => 'mid.parent_1'], $comment->getLink());
        self::assertSame($data, $comment->toArray());
    }

    #[Test]
    public function commentMessageWrappedInMessageKey(): void
    {
        $wrapped = [
            'message' => [
                'sender'    => null,
                'timestamp' => 1600000000000,
                'body'      => [
                    'mid'  => 'mid.comm_channel',
                    'text' => 'Сообщение от канала',
                ],
            ],
        ];

        $comment = CommentMessage::fromArray($wrapped);

        self::assertSame('mid.comm_channel', $comment->getCommentId());
        self::assertSame('Сообщение от канала', $comment->getText());
        self::assertNull($comment->getSender());
        self::assertTrue($comment->isChannelPost());
        self::assertFalse($comment->hasReply());
        self::assertNull($comment->getFormat());
        self::assertSame(['mid' => 'mid.comm_channel', 'text' => 'Сообщение от канала'], $comment->getBody());
        self::assertNull($comment->getRecipient());
    }

    // --- CommentPayload ---

    #[Test]
    public function commentPayloadCreationAndFluentBuilder(): void
    {
        $payload = CommentPayload::create('Привет мир!')
            ->withFormat(TextFormat::Html)
            ->withReplyTo('mid.parent_123');

        self::assertSame('Привет мир!', $payload->getText());
        self::assertSame(TextFormat::Html, $payload->getFormat());
        self::assertSame(['type' => 'reply', 'mid' => 'mid.parent_123'], $payload->getLink());

        $expected = [
            'text'   => 'Привет мир!',
            'format' => 'html',
            'link'   => ['type' => 'reply', 'mid' => 'mid.parent_123'],
        ];

        self::assertSame($expected, $payload->toArray());
        self::assertSame($expected, $payload->jsonSerialize());
    }

    #[Test]
    public function commentPayloadWithStringFormat(): void
    {
        $payload = CommentPayload::create('Text')->withFormat('markdown');
        self::assertSame(TextFormat::Markdown, $payload->getFormat());

        $nullFormat = $payload->withFormat(null);
        self::assertNull($nullFormat->getFormat());

        $withLink = CommentPayload::create('Link test')->withLink(['type' => 'reply', 'mid' => 'm99']);
        self::assertSame(['type' => 'reply', 'mid' => 'm99'], $withLink->getLink());
    }

    // --- Update with Comment & Admin Permissions ---

    #[Test]
    public function updateWithCommentAndAdminPermissions(): void
    {
        $data = [
            'update_type'       => 'comment_created',
            'timestamp'         => 1700000000,
            'comment'           => [
                'body' => ['mid' => 'mid.c1', 'text' => 'Новый комментарий'],
            ],
            'admin_permissions' => ['read_all_messages' => true, 'write' => true],
        ];

        $update = Update::fromArray($data);

        self::assertSame(UpdateType::CommentCreated, $update->getType());
        self::assertTrue($update->isComment());
        self::assertFalse($update->isDialog());
        self::assertFalse($update->isBotLifecycle());

        $comment = $update->getComment();
        self::assertNotNull($comment);
        self::assertSame('mid.c1', $comment->getCommentId());
        self::assertSame('Новый комментарий', $comment->getText());

        self::assertSame(['read_all_messages' => true, 'write' => true], $update->getAdminPermissions());
    }

    #[Test]
    public function updateDialogAndLifecycleHelpers(): void
    {
        $dialogUpdate = Update::fromArray(['update_type' => 'dialog_cleared', 'timestamp' => 100]);
        self::assertTrue($dialogUpdate->isDialog());
        self::assertFalse($dialogUpdate->isComment());

        $botUpdate = Update::fromArray(['update_type' => 'bot_stopped', 'timestamp' => 200]);
        self::assertTrue($botUpdate->isBotLifecycle());
        self::assertFalse($botUpdate->isComment());
    }

    // =====================================================================
    // v2.4.0 — OpenAPI v0.0.34 & November 2026 API Updates
    // =====================================================================

    #[Test]
    public function actionResultWithErrorCode(): void
    {
        $result = ActionResult::fromArray([
            'success' => false,
            'code'    => 'chat.denied',
            'message' => 'Access denied',
        ]);

        self::assertFalse($result->isSuccess());
        self::assertTrue($result->hasError());
        self::assertSame('chat.denied', $result->getCode());
        self::assertSame('Access denied', $result->getMessage());
        self::assertSame([
            'success' => false,
            'message' => 'Access denied',
            'code'    => 'chat.denied',
        ], $result->toArray());
    }

    #[Test]
    public function actionResultSuccessHasNullCode(): void
    {
        $result = ActionResult::success();

        self::assertTrue($result->isSuccess());
        self::assertFalse($result->hasError());
        self::assertNull($result->getCode());
    }

    #[Test]
    public function userFromOpenApiFirstAndLastName(): void
    {
        $user = User::fromArray([
            'user_id'         => 10,
            'first_name'      => 'Иван',
            'last_name'       => 'Иванов',
            'username'        => 'ivan',
            'is_bot'          => false,
            'full_avatar_url' => 'https://cdn.max.ru/full.jpg',
            'commands'        => [
                ['name' => 'start', 'description' => 'Запуск'],
            ],
        ]);

        self::assertSame(10, $user->getUserId());
        self::assertSame('Иван', $user->getFirstName());
        self::assertSame('Иванов', $user->getLastName());
        self::assertSame('Иван Иванов', $user->getName());
        self::assertSame('https://cdn.max.ru/full.jpg', $user->getFullAvatarUrl());
        self::assertSame([['name' => 'start', 'description' => 'Запуск']], $user->getCommands());

        $single = User::fromArray([
            'user_id'    => 11,
            'first_name' => 'Анна',
            'is_bot'     => false,
        ]);
        self::assertSame('Анна', $single->getName());
        self::assertSame('Анна', $single->getFirstName());
        self::assertNull($single->getLastName());
    }

    #[Test]
    public function chatMemberFromOpenApiFields(): void
    {
        $member = ChatMember::fromArray([
            'user_id'          => 55,
            'first_name'       => 'Пётр',
            'last_name'        => 'Петров',
            'full_avatar_url'  => 'https://cdn.max.ru/full_p.jpg',
            'last_access_time' => 1760000000000,
            'is_owner'         => false,
            'is_admin'         => true,
            'join_time'        => 1750000000000,
            'permissions'      => ['write', 'pin_message'],
            'alias'            => 'Модератор',
        ]);

        self::assertSame('Пётр Петров', $member->getName());
        self::assertSame('Пётр', $member->getFirstName());
        self::assertSame('Петров', $member->getLastName());
        self::assertSame('https://cdn.max.ru/full_p.jpg', $member->getFullAvatarUrl());
        self::assertSame(1760000000000, $member->getLastAccessTime());
        self::assertSame('Модератор', $member->getAlias());
    }

    #[Test]
    public function videoInfoFromOpenApiUrlsObject(): void
    {
        $info = VideoInfo::fromArray([
            'token'    => 'vid_openapi',
            'urls'     => [
                'mp4_720'  => 'https://cdn.max.ru/720.mp4',
                'mp4_1080' => 'https://cdn.max.ru/1080.mp4',
                'hls'      => 'https://cdn.max.ru/stream.m3u8',
            ],
            'width'    => 1920,
            'height'   => 1080,
            'duration' => 95,
        ]);

        self::assertSame('https://cdn.max.ru/1080.mp4', $info->getUrl());
        self::assertSame('https://cdn.max.ru/720.mp4', $info->getUrlByQuality('mp4_720'));
        self::assertSame('https://cdn.max.ru/stream.m3u8', $info->getUrlByQuality('hls'));
        self::assertNull($info->getUrlByQuality('mp4_480'));
        self::assertCount(3, $info->getUrls() ?? []);
    }

    #[Test]
    public function attachmentPreservesOpenApiTopLevelFields(): void
    {
        $video = Attachment::fromArray([
            'type'       => 'video',
            'payload'    => ['token' => 'vid_tok', 'url' => 'https://cdn.max.ru/v.mp4'],
            'width'      => 1280,
            'height'     => 720,
            'duration'   => 45,
            'video_type' => 'video_message',
            'thumbnail'  => ['url' => 'https://cdn.max.ru/t.jpg'],
        ]);

        self::assertSame(1280, $video->getWidth());
        self::assertSame(720, $video->getHeight());
        self::assertSame(45, $video->getDuration());
        self::assertSame('video_message', $video->getVideoType());
        self::assertSame(['url' => 'https://cdn.max.ru/t.jpg'], $video->getThumbnail());
        self::assertSame('video_message', $video->getAttribute('video_type'));
        self::assertSame(45, $video->toArray()['duration']);

        $file = Attachment::fromArray([
            'type'     => 'file',
            'payload'  => ['token' => 'f_tok'],
            'filename' => 'report.pdf',
            'size'     => 204800,
        ]);
        self::assertSame('report.pdf', $file->getFilename());
        self::assertSame(204800, $file->getSize());

        $location = Attachment::fromArray([
            'type'      => 'location',
            'latitude'  => 55.7558,
            'longitude' => 37.6173,
        ]);
        self::assertSame(55.7558, $location->getLatitude());
        self::assertSame(37.6173, $location->getLongitude());

        $audio = Attachment::fromArray([
            'type'          => 'audio',
            'payload'       => ['token' => 'a_tok'],
            'transcription' => 'Привет, мир',
        ]);
        self::assertSame('Привет, мир', $audio->getTranscription());

        $share = Attachment::fromArray([
            'type'        => 'share',
            'payload'     => ['url' => 'https://example.com'],
            'title'       => 'Example',
            'description' => 'Desc',
            'image_url'   => 'https://example.com/og.png',
        ]);
        self::assertSame('Example', $share->getTitle());
        self::assertSame('Desc', $share->getDescription());
        self::assertSame('https://example.com/og.png', $share->getImageUrl());
    }

    #[Test]
    public function subscriptionWithSimpleQueryResultResponse(): void
    {
        $sub = Subscription::fromArray([
            'success' => false,
            'code'    => 'webhook.invalid_url',
            'message' => 'Invalid HTTPS URL',
        ]);

        self::assertFalse($sub->isSuccess());
        self::assertSame('webhook.invalid_url', $sub->getCode());
        self::assertSame('Invalid HTTPS URL', $sub->getMessage());
    }

    #[Test]
    public function updateBotAddedWithoutUserAndAdminPermissionsChanged(): void
    {
        // Ноябрь 2026: bot_added больше не содержит поле user
        $botAdded = Update::fromArray([
            'update_type' => 'bot_added',
            'timestamp'   => 1763000000000,
            'chat_id'     => -100123,
            'is_channel'  => true,
        ]);

        self::assertSame(UpdateType::BotAdded, $botAdded->getType());
        self::assertSame(-100123, $botAdded->getChatId());
        self::assertNull($botAdded->getUser());
        self::assertTrue($botAdded->isChannel());

        // Октябрь 2026: bot_admin_permissions_changed
        $permUpdate = Update::fromArray([
            'update_type' => 'bot_admin_permissions_changed',
            'timestamp'   => 1763000001000,
            'chat_id'     => -100123,
            'user_id'     => 777,
            'bot_id'      => 888,
            'is_channel'  => true,
            'is_admin'    => true,
            'permissions' => ['read_all_messages', 'write'],
        ]);

        self::assertSame(UpdateType::BotAdminPermissionsChanged, $permUpdate->getType());
        self::assertTrue($permUpdate->isBotLifecycle());
        self::assertSame(888, $permUpdate->getBotId());
        self::assertTrue($permUpdate->isAdmin());
        self::assertTrue($permUpdate->isChannel());
        self::assertSame(['read_all_messages', 'write'], $permUpdate->getAdminPermissions());

        // comment_removed, dialog_muted, bot_started дополнительные поля
        $commentRemoved = Update::fromArray([
            'update_type' => 'comment_removed',
            'timestamp'   => 1763000002000,
            'chat_id'     => -100123,
            'message_id'  => 'mid.c1',
            'post_id'     => 'mid.p1',
            'user_id'     => 42,
        ]);
        self::assertSame('mid.p1', $commentRemoved->getPostId());

        $dialogMuted = Update::fromArray([
            'update_type' => 'dialog_muted',
            'timestamp'   => 1763000003000,
            'chat_id'     => 500,
            'muted_until' => 1763999999000,
            'user_locale' => 'ru',
        ]);
        self::assertSame(1763999999000, $dialogMuted->getMutedUntil());
        self::assertSame('ru', $dialogMuted->getUserLocale());

        $botStarted = Update::fromArray([
            'update_type' => 'bot_started',
            'timestamp'   => 1763000004000,
            'chat_id'     => 500,
            'payload'     => 'ref_campaign_42',
            'user_locale' => 'ru',
        ]);
        self::assertSame('ref_campaign_42', $botStarted->getPayload());
    }

    #[Test]
    public function messageOpenApiFields(): void
    {
        $msg = Message::fromArray([
            'sender'    => ['user_id' => 1, 'first_name' => 'Bot', 'is_bot' => true],
            'recipient' => ['chat_id' => 100],
            'timestamp' => 1763000000000,
            'url'       => 'https://max.ru/c/100/1',
            'link'      => ['type' => 'reply', 'message' => ['mid' => 'mid.orig']],
            'body'      => [
                'mid'    => 'mid.new',
                'seq'    => 42,
                'text'   => 'Hello world',
                'markup' => [
                    ['type' => 'strong', 'from' => 0, 'length' => 5],
                ],
            ],
        ]);

        self::assertSame('mid.new', $msg->getMessageId());
        self::assertSame(42, $msg->getSeq());
        self::assertSame('https://max.ru/c/100/1', $msg->getUrl());
        self::assertNull($msg->getLink());
        self::assertSame(['type' => 'reply', 'message' => ['mid' => 'mid.orig']], $msg->getLinkData());
        self::assertSame([['type' => 'strong', 'from' => 0, 'length' => 5]], $msg->getMarkup());
    }
}
