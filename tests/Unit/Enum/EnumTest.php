<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit\Enum;

use MaxBotSdk\Enum\HttpMethod;
use MaxBotSdk\Enum\LogLevel;
use MaxBotSdk\Enum\TextFormat;
use MaxBotSdk\Enum\UpdateType;
use MaxBotSdk\Enum\UploadType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Тесты Backed Enum классов.
 */
final class EnumTest extends TestCase
{
    // ─── HttpMethod ─────────────────────────────────────────────

    #[Test]
    public function httpMethodHasFiveCases(): void
    {
        $cases = HttpMethod::cases();
        self::assertCount(5, $cases);
    }

    #[Test]
    public function httpMethodValues(): void
    {
        self::assertSame('GET', HttpMethod::GET->value);
        self::assertSame('POST', HttpMethod::POST->value);
        self::assertSame('PUT', HttpMethod::PUT->value);
        self::assertSame('PATCH', HttpMethod::PATCH->value);
        self::assertSame('DELETE', HttpMethod::DELETE->value);
    }

    #[Test]
    public function httpMethodFromString(): void
    {
        self::assertSame(HttpMethod::GET, HttpMethod::from('GET'));
        self::assertSame(HttpMethod::POST, HttpMethod::from('POST'));
    }

    #[Test]
    public function httpMethodTryFromInvalid(): void
    {
        self::assertNull(HttpMethod::tryFrom('INVALID'));
        self::assertNull(HttpMethod::tryFrom('get'));
    }

    // ─── UploadType ─────────────────────────────────────────────

    #[Test]
    public function uploadTypeHasFourCases(): void
    {
        $cases = UploadType::cases();
        self::assertCount(4, $cases);
    }

    #[Test]
    public function uploadTypeValues(): void
    {
        self::assertSame('image', UploadType::Image->value);
        self::assertSame('video', UploadType::Video->value);
        self::assertSame('audio', UploadType::Audio->value);
        self::assertSame('file', UploadType::File->value);
    }

    #[Test]
    public function uploadTypeFromString(): void
    {
        self::assertSame(UploadType::Image, UploadType::from('image'));
        self::assertSame(UploadType::File, UploadType::from('file'));
    }

    #[Test]
    public function uploadTypeTryFromInvalid(): void
    {
        self::assertNull(UploadType::tryFrom('document'));
        self::assertNull(UploadType::tryFrom(''));
    }

    // ─── LogLevel ───────────────────────────────────────────────

    #[Test]
    public function logLevelHasFourCases(): void
    {
        $cases = LogLevel::cases();
        self::assertCount(4, $cases);
    }

    #[Test]
    public function logLevelValues(): void
    {
        self::assertSame('debug', LogLevel::Debug->value);
        self::assertSame('info', LogLevel::Info->value);
        self::assertSame('warning', LogLevel::Warning->value);
        self::assertSame('error', LogLevel::Error->value);
    }

    #[Test]
    public function logLevelFromString(): void
    {
        self::assertSame(LogLevel::Debug, LogLevel::from('debug'));
        self::assertSame(LogLevel::Error, LogLevel::from('error'));
    }

    #[Test]
    public function logLevelTryFromInvalid(): void
    {
        self::assertNull(LogLevel::tryFrom('critical'));
        self::assertNull(LogLevel::tryFrom('WARN'));
    }

    // ─── UpdateType ─────────────────────────────────────────────

    #[Test]
    public function updateTypeHasNineteenCases(): void
    {
        $cases = UpdateType::cases();
        self::assertCount(19, $cases);
    }

    #[Test]
    public function updateTypeValues(): void
    {
        self::assertSame('message_created', UpdateType::MessageCreated->value);
        self::assertSame('message_callback', UpdateType::MessageCallback->value);
        self::assertSame('message_edited', UpdateType::MessageEdited->value);
        self::assertSame('message_removed', UpdateType::MessageRemoved->value);
        self::assertSame('bot_started', UpdateType::BotStarted->value);
        self::assertSame('bot_added', UpdateType::BotAdded->value);
        self::assertSame('bot_removed', UpdateType::BotRemoved->value);
        self::assertSame('user_added', UpdateType::UserAdded->value);
        self::assertSame('user_removed', UpdateType::UserRemoved->value);
        self::assertSame('chat_title_changed', UpdateType::ChatTitleChanged->value);

        // v2.3.0
        self::assertSame('bot_admin_permissions_changed', UpdateType::BotAdminPermissionsChanged->value);
        self::assertSame('comment_created', UpdateType::CommentCreated->value);
        self::assertSame('comment_edited', UpdateType::CommentEdited->value);
        self::assertSame('comment_removed', UpdateType::CommentRemoved->value);
        self::assertSame('bot_stopped', UpdateType::BotStopped->value);
        self::assertSame('dialog_cleared', UpdateType::DialogCleared->value);
        self::assertSame('dialog_muted', UpdateType::DialogMuted->value);
        self::assertSame('dialog_unmuted', UpdateType::DialogUnmuted->value);
        self::assertSame('dialog_removed', UpdateType::DialogRemoved->value);
    }

    #[Test]
    public function updateTypeClassificationMethods(): void
    {
        // Comments
        self::assertTrue(UpdateType::CommentCreated->isCommentEvent());
        self::assertTrue(UpdateType::CommentEdited->isCommentEvent());
        self::assertTrue(UpdateType::CommentRemoved->isCommentEvent());
        self::assertFalse(UpdateType::MessageCreated->isCommentEvent());

        // Dialogs
        self::assertTrue(UpdateType::DialogCleared->isDialogEvent());
        self::assertTrue(UpdateType::DialogMuted->isDialogEvent());
        self::assertTrue(UpdateType::DialogUnmuted->isDialogEvent());
        self::assertTrue(UpdateType::DialogRemoved->isDialogEvent());
        self::assertFalse(UpdateType::BotStarted->isDialogEvent());

        // Bot lifecycle
        self::assertTrue(UpdateType::BotStarted->isBotLifecycleEvent());
        self::assertTrue(UpdateType::BotStopped->isBotLifecycleEvent());
        self::assertTrue(UpdateType::BotAdded->isBotLifecycleEvent());
        self::assertTrue(UpdateType::BotRemoved->isBotLifecycleEvent());
        self::assertFalse(UpdateType::UserAdded->isBotLifecycleEvent());

        // Messages
        self::assertTrue(UpdateType::MessageCreated->isMessageEvent());
        self::assertTrue(UpdateType::MessageCallback->isMessageEvent());
        self::assertTrue(UpdateType::MessageEdited->isMessageEvent());
        self::assertTrue(UpdateType::MessageRemoved->isMessageEvent());
        self::assertFalse(UpdateType::CommentCreated->isMessageEvent());

        // Membership
        self::assertTrue(UpdateType::UserAdded->isMembershipEvent());
        self::assertTrue(UpdateType::UserRemoved->isMembershipEvent());
        self::assertTrue(UpdateType::BotAdded->isMembershipEvent());
        self::assertTrue(UpdateType::BotRemoved->isMembershipEvent());
        self::assertFalse(UpdateType::ChatTitleChanged->isMembershipEvent());
    }

    #[Test]
    public function updateTypeFromString(): void
    {
        self::assertSame(UpdateType::MessageCreated, UpdateType::from('message_created'));
        self::assertSame(UpdateType::BotStarted, UpdateType::from('bot_started'));
        self::assertSame(UpdateType::CommentCreated, UpdateType::from('comment_created'));
        self::assertSame(UpdateType::BotAdminPermissionsChanged, UpdateType::from('bot_admin_permissions_changed'));
    }

    #[Test]
    public function updateTypeTryFromInvalid(): void
    {
        self::assertNull(UpdateType::tryFrom('unknown_event'));
        self::assertNull(UpdateType::tryFrom(''));
    }

    // ─── TextFormat ─────────────────────────────────────────────

    #[Test]
    public function textFormatCases(): void
    {
        $cases = TextFormat::cases();
        self::assertCount(2, $cases);
        self::assertSame('markdown', TextFormat::Markdown->value);
        self::assertSame('html', TextFormat::Html->value);
        self::assertSame(TextFormat::Markdown, TextFormat::from('markdown'));
        self::assertSame(TextFormat::Html, TextFormat::from('html'));
        self::assertNull(TextFormat::tryFrom('bbcode'));
    }
}
