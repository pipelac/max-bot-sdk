<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

use JsonSerializable;
use MaxBotSdk\Enum\TextFormat;
use MaxBotSdk\Utils\InputValidator;

/**
 * Value Object для создания и редактирования полезной нагрузки комментария (NewCommentBody).
 *
 * Предоставляет fluent-интерфейс для безопасного формирования тела комментария.
 *
 * @since 2.3.0
 */
final class CommentPayload implements JsonSerializable
{
    private string $text;
    private ?TextFormat $format = null;
    /** @var array<string, mixed>|null */
    private ?array $link = null;

    private function __construct(string $text)
    {
        $this->text = InputValidator::validateText($text);
    }

    /**
     * Создать новый payload комментария с текстом.
     */
    public static function create(string $text): self
    {
        return new self($text);
    }

    /**
     * Задать формат разметки текста комментария (markdown или html).
     */
    public function withFormat(TextFormat|string|null $format): self
    {
        $clone = clone $this;
        if ($format === null) {
            $clone->format = null;
        } elseif ($format instanceof TextFormat) {
            $clone->format = $format;
        } else {
            $clone->format = TextFormat::tryFrom(strtolower(trim($format)));
        }
        return $clone;
    }

    /**
     * Задать цитирование / ответ на конкретный комментарий по его mid.
     */
    public function withReplyTo(string $commentId): self
    {
        $clone = clone $this;
        $clone->link = [
            'type' => 'reply',
            'mid'  => InputValidator::validateNotEmpty($commentId, 'Comment ID'),
        ];
        return $clone;
    }

    /**
     * Задать объект ссылки напрямую (NewMessageLink).
     *
     * @param array<string, mixed>|null $link
     */
    public function withLink(?array $link): self
    {
        $clone = clone $this;
        $clone->link = $link;
        return $clone;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getFormat(): ?TextFormat
    {
        return $this->format;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLink(): ?array
    {
        return $this->link;
    }

    /**
     * Преобразовать в массив для отправки в API (NewCommentBody).
     *
     * @return array{text: string, format?: string, link?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $payload = ['text' => $this->text];

        if ($this->format !== null) {
            $payload['format'] = $this->format->value;
        }

        if ($this->link !== null) {
            $payload['link'] = $this->link;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
