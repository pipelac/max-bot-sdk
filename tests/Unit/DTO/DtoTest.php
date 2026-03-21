<?php

namespace App\Component\Max\Tests\Unit\DTO;

use App\Component\Max\DTO\AbstractDto;
use App\Component\Max\DTO\ActionResult;
use App\Component\Max\DTO\User;
use App\Component\Max\DTO\Chat;
use App\Component\Max\DTO\ChatMember;
use App\Component\Max\DTO\Attachment;
use App\Component\Max\DTO\Message;
use App\Component\Max\DTO\Update;
use App\Component\Max\DTO\UpdatesResult;
use App\Component\Max\DTO\Subscription;
use App\Component\Max\DTO\UploadResult;
use App\Component\Max\DTO\PaginatedResult;
use App\Component\Max\DTO\VideoInfo;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для всех DTO классов.
 */
class DtoTest extends TestCase
{
    // --- AbstractDto ---

    public function testAllDtosExtendAbstractDto()
    {
        $dtos = array(
            User::fromArray(array()),
            Chat::fromArray(array()),
            ChatMember::fromArray(array()),
            Attachment::fromArray(array()),
            Message::fromArray(array()),
            Update::fromArray(array()),
            Subscription::fromArray(array()),
            UploadResult::fromArray(array()),
            VideoInfo::fromArray(array()),
            ActionResult::fromArray(array()),
        );

        foreach ($dtos as $dto) {
            $this->assertInstanceOf(AbstractDto::class, $dto);
        }
    }

    // --- User ---

    public function testUserFromArray()
    {
        $user = User::fromArray(array(
            'user_id'  => 123,
            'name'     => 'TestBot',
            'username' => 'test_bot',
            'is_bot'   => true,
        ));

        $this->assertEquals(123, $user->getUserId());
        $this->assertEquals('TestBot', $user->getName());
        $this->assertEquals('test_bot', $user->getUsername());
        $this->assertTrue($user->isBot());
    }

    public function testUserFromEmptyArray()
    {
        $user = User::fromArray(array());
        $this->assertEquals(0, $user->getUserId());
        $this->assertEquals('', $user->getName());
        $this->assertNull($user->getUsername());
        $this->assertFalse($user->isBot());
    }

    public function testUserToArray()
    {
        $data = array(
            'user_id'  => 42,
            'name'     => 'Bot',
            'username' => 'bot42',
            'is_bot'   => true,
        );
        $user = User::fromArray($data);
        $arr = $user->toArray();
        $this->assertEquals(42, $arr['user_id']);
        $this->assertEquals('Bot', $arr['name']);
    }

    // --- Chat ---

    public function testChatFromArray()
    {
        $chat = Chat::fromArray(array(
            'chat_id'            => 456,
            'type'               => 'chat',
            'status'             => 'active',
            'title'              => 'Test Chat',
            'participants_count' => 5,
            'is_public'          => true,
        ));

        $this->assertEquals(456, $chat->getChatId());
        $this->assertEquals('chat', $chat->getType());
        $this->assertEquals('active', $chat->getStatus());
        $this->assertEquals('Test Chat', $chat->getTitle());
        $this->assertEquals(5, $chat->getParticipantsCount());
        $this->assertTrue($chat->isPublic());
    }

    public function testChatFromEmptyArray()
    {
        $chat = Chat::fromArray(array());
        $this->assertEquals(0, $chat->getChatId());
        $this->assertEquals('', $chat->getTitle());
        $this->assertFalse($chat->isPublic());
    }

    // --- ChatMember ---

    public function testChatMemberFromArray()
    {
        $member = ChatMember::fromArray(array(
            'user_id'  => 789,
            'name'     => 'User1',
            'is_admin' => true,
            'is_owner' => false,
        ));

        $this->assertEquals(789, $member->getUserId());
        $this->assertEquals('User1', $member->getName());
        $this->assertTrue($member->isAdmin());
        $this->assertFalse($member->isOwner());
    }

    // --- Attachment ---

    public function testAttachmentFromArray()
    {
        $att = Attachment::fromArray(array(
            'type'    => 'image',
            'payload' => array('token' => 'abc123'),
        ));

        $this->assertEquals('image', $att->getType());
        $this->assertEquals(array('token' => 'abc123'), $att->getPayload());
        $this->assertEquals('abc123', $att->getPayloadValue('token'));
        $this->assertNull($att->getPayloadValue('nonexistent'));
        $this->assertEquals('default', $att->getPayloadValue('nonexistent', 'default'));
    }

    // --- Message ---

    public function testMessageFromArray()
    {
        $msg = Message::fromArray(array(
            'body' => array(
                'mid'  => 'msg_001',
                'text' => 'Привет!',
            ),
            'sender' => array(
                'user_id' => 1,
                'name'    => 'Bot',
            ),
            'recipient' => array(
                'chat_id' => 100,
            ),
            'timestamp' => 1234567890,
        ));

        $this->assertEquals('msg_001', $msg->getMessageId());
        $this->assertEquals('Привет!', $msg->getText());
        $this->assertNotNull($msg->getSender());
        $this->assertEquals(1, $msg->getSender()->getUserId());
        $this->assertEquals(100, $msg->getChatId());
        $this->assertEquals(1234567890, $msg->getTimestamp());
    }

    public function testMessageFromEmptyArray()
    {
        $msg = Message::fromArray(array());
        $this->assertNull($msg->getMessageId());
        $this->assertNull($msg->getText());
        $this->assertNull($msg->getSender());
        $this->assertEmpty($msg->getAttachments());
    }

    public function testMessageWithAttachments()
    {
        $msg = Message::fromArray(array(
            'body' => array(
                'text' => 'С картинкой',
                'attachments' => array(
                    array('type' => 'image', 'payload' => array('token' => 'img1')),
                    array('type' => 'file', 'payload' => array('token' => 'file1')),
                ),
            ),
        ));

        $this->assertCount(2, $msg->getAttachments());
        $this->assertEquals('image', $msg->getAttachments()[0]->getType());
    }

    public function testMessageToArrayReconstructs()
    {
        $msg = Message::fromArray(array(
            'body' => array('mid' => 'm1', 'text' => 'Hello'),
            'sender' => array('user_id' => 1, 'name' => 'Bot'),
            'timestamp' => 999,
        ));

        $arr = $msg->toArray();
        $this->assertEquals('m1', $arr['message_id']);
        $this->assertEquals('Hello', $arr['text']);
        $this->assertNotNull($arr['sender']);
        $this->assertEquals(999, $arr['timestamp']);
    }

    // --- Update ---

    public function testUpdateFromArray()
    {
        $upd = Update::fromArray(array(
            'update_type' => 'message_created',
            'timestamp'   => 1111111111,
            'message'     => array(
                'body' => array('text' => 'Hello'),
                'sender' => array('user_id' => 1, 'name' => 'Bot'),
            ),
        ));

        $this->assertEquals('message_created', $upd->getUpdateType());
        $this->assertEquals(1111111111, $upd->getTimestamp());
        $this->assertNotNull($upd->getMessage());
        $this->assertEquals('Hello', $upd->getMessage()->getText());
    }

    public function testUpdateCallbackShortcuts()
    {
        $upd = Update::fromArray(array(
            'update_type' => 'message_callback',
            'timestamp'   => 222,
            'callback'    => array(
                'callback_id' => 'cb_123',
                'payload'     => 'btn_click',
            ),
        ));

        $this->assertEquals('cb_123', $upd->getCallbackId());
        $this->assertEquals('btn_click', $upd->getCallbackPayload());
    }

    public function testUpdateBotStarted()
    {
        $upd = Update::fromArray(array(
            'update_type' => 'bot_started',
            'timestamp'   => 333,
            'user'        => array('user_id' => 99, 'name' => 'NewUser'),
        ));

        $this->assertEquals('bot_started', $upd->getUpdateType());
        $this->assertNotNull($upd->getUser());
        $this->assertEquals(99, $upd->getUser()->getUserId());
    }

    public function testUpdateToArrayReconstructs()
    {
        $upd = Update::fromArray(array(
            'update_type' => 'message_created',
            'timestamp'   => 555,
            'message'     => array(
                'body' => array('text' => 'Test'),
            ),
        ));

        $arr = $upd->toArray();
        $this->assertEquals('message_created', $arr['update_type']);
        $this->assertEquals(555, $arr['timestamp']);
        $this->assertArrayHasKey('message', $arr);
    }

    // --- Subscription ---

    public function testSubscriptionFromArray()
    {
        $sub = Subscription::fromArray(array(
            'url'          => 'https://example.com/webhook',
            'time'         => 1234567890,
            'update_types' => array('message_created', 'message_callback'),
        ));

        $this->assertEquals('https://example.com/webhook', $sub->getUrl());
        $this->assertEquals(1234567890, $sub->getTime());
        $this->assertCount(2, $sub->getUpdateTypes());
    }

    // --- UploadResult ---

    public function testUploadResultFromArray()
    {
        $res = UploadResult::fromArray(array(
            'url'   => 'https://upload.example.com/abc',
            'token' => 'tok_123',
        ));

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
        $result = PaginatedResult::fromApiResponse(array(
            'chats'  => array(
                array('chat_id' => 1, 'title' => 'Chat 1'),
                array('chat_id' => 2, 'title' => 'Chat 2'),
            ),
            'marker' => 100,
        ), 'chats');

        $this->assertEquals(2, $result->count());
        $this->assertTrue($result->hasMore());
        $this->assertEquals(100, $result->getMarker());
    }

    public function testPaginatedResultWithDtoMapping()
    {
        $result = PaginatedResult::fromApiResponse(array(
            'chats' => array(
                array('chat_id' => 1, 'title' => 'A'),
                array('chat_id' => 2, 'title' => 'B'),
            ),
        ), 'chats', Chat::class);

        $items = $result->getItems();
        $this->assertCount(2, $items);
        $this->assertInstanceOf(Chat::class, $items[0]);
        $this->assertEquals('A', $items[0]->getTitle());
    }

    public function testPaginatedResultNoMarker()
    {
        $result = PaginatedResult::fromArray(array(
            'items' => array(1, 2, 3),
        ));

        $this->assertEquals(3, $result->count());
        $this->assertFalse($result->hasMore());
        $this->assertNull($result->getMarker());
    }

    public function testPaginatedResultEmpty()
    {
        $result = PaginatedResult::fromApiResponse(array(), 'items');
        $this->assertEquals(0, $result->count());
        $this->assertFalse($result->hasMore());
    }

    public function testPaginatedResultCountable()
    {
        $result = PaginatedResult::fromArray(array(
            'items' => array(1, 2, 3),
        ));
        $this->assertCount(3, $result);
    }

    public function testPaginatedResultIteratorAggregate()
    {
        $result = PaginatedResult::fromArray(array(
            'items' => array('a', 'b', 'c'),
        ));

        $collected = array();
        foreach ($result as $item) {
            $collected[] = $item;
        }
        $this->assertEquals(array('a', 'b', 'c'), $collected);
    }

    // --- ActionResult ---

    public function testActionResultFromArray()
    {
        $result = ActionResult::fromArray(array('success' => true));
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getMessage());
    }

    public function testActionResultWithMessage()
    {
        $result = ActionResult::fromArray(array(
            'success' => true,
            'message' => 'Операция выполнена',
        ));
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
        $result = UpdatesResult::fromArray(array(
            'updates' => array(
                array('update_type' => 'message_created', 'timestamp' => 123),
                array('update_type' => 'message_callback', 'timestamp' => 456),
            ),
            'marker' => 42,
        ));

        $this->assertEquals(2, $result->count());
        $this->assertTrue($result->hasMore());
        $this->assertEquals(42, $result->getMarker());
        $this->assertInstanceOf(Update::class, $result->getUpdates()[0]);
    }

    public function testUpdatesResultEmpty()
    {
        $result = UpdatesResult::fromArray(array());
        $this->assertEquals(0, $result->count());
        $this->assertFalse($result->hasMore());
    }

    // --- VideoInfo ---

    public function testVideoInfoFromArray()
    {
        $info = VideoInfo::fromArray(array(
            'token'    => 'vid_abc',
            'url'      => 'https://cdn.max.ru/video.mp4',
            'width'    => 1920,
            'height'   => 1080,
            'duration' => 120,
            'thumbnail' => array('url' => 'https://cdn.max.ru/thumb.jpg'),
        ));

        $this->assertEquals('vid_abc', $info->getToken());
        $this->assertEquals('https://cdn.max.ru/video.mp4', $info->getUrl());
        $this->assertEquals(1920, $info->getWidth());
        $this->assertEquals(1080, $info->getHeight());
        $this->assertEquals(120, $info->getDuration());
        $this->assertNotNull($info->getThumbnail());
    }

    public function testVideoInfoFromEmptyArray()
    {
        $info = VideoInfo::fromArray(array());
        $this->assertEquals('', $info->getToken());
        $this->assertEquals('', $info->getUrl());
        $this->assertNull($info->getWidth());
        $this->assertNull($info->getHeight());
        $this->assertNull($info->getDuration());
        $this->assertNull($info->getThumbnail());
    }

    public function testVideoInfoToArray()
    {
        $data = array(
            'token'     => 'vid_xyz',
            'url'       => 'https://cdn.max.ru/v2.mp4',
            'width'     => 1280,
            'height'    => 720,
            'duration'  => 60,
            'thumbnail' => null,
        );
        $info = VideoInfo::fromArray($data);
        $arr = $info->toArray();
        $this->assertEquals('vid_xyz', $arr['token']);
        $this->assertEquals(1280, $arr['width']);
        $this->assertEquals(720, $arr['height']);
        $this->assertEquals(60, $arr['duration']);
    }
}
