<?php

declare(strict_types=1);

namespace MaxBotSdk\Contracts;

use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\CommentMessage;
use MaxBotSdk\DTO\CommentPayload;
use MaxBotSdk\DTO\PaginatedResult;
use MaxBotSdk\Enum\TextFormat;

/**
 * Интерфейс ресурса работы с комментариями к постам в каналах.
 *
 * @since 2.3.0
 */
interface CommentsResourceInterface extends ResourceInterface
{
    /**
     * Получить список комментариев к посту.
     *
     * @param string             $messageId  ID поста в канале.
     * @param int|null           $count      Количество комментариев (от 1 до 100, по умолчанию 50).
     * @param string|null        $before     Маркер пагинации: получить комментарии до указанного.
     * @param string|null        $after      Маркер пагинации: получить комментарии после указанного.
     * @param list<string>|null  $commentIds Список конкретных идентификаторов комментариев.
     * @return PaginatedResult<CommentMessage>
     */
    public function getComments(
        string $messageId,
        ?int $count = null,
        ?string $before = null,
        ?string $after = null,
        ?array $commentIds = null,
    ): PaginatedResult;

    /**
     * Получить конкретный комментарий по его идентификатору.
     *
     * @param string $messageId ID поста в канале.
     * @param string $commentId ID комментария (mid).
     */
    public function getComment(string $messageId, string $commentId): CommentMessage;

    /**
     * Отправить комментарий к посту в канале.
     *
     * @param string                 $messageId ID поста в канале.
     * @param string|CommentPayload  $comment   Текст комментария или готовый объект CommentPayload.
     * @param TextFormat|string|null $format    Формат разметки (используется, если $comment - строка).
     * @param array<string, mixed>|null $link   Ссылка для ответа/цитирования.
     */
    public function addComment(
        string $messageId,
        string|CommentPayload $comment,
        TextFormat|string|null $format = null,
        ?array $link = null,
    ): CommentMessage;

    /**
     * Отредактировать комментарий к посту (доступно в течение 26 часов с момента публикации).
     *
     * @param string                 $messageId ID поста в канале.
     * @param string                 $commentId ID редактируемого комментария.
     * @param string|CommentPayload  $comment   Новый текст комментария или объект CommentPayload.
     * @param TextFormat|string|null $format    Формат разметки (используется, если $comment - строка).
     * @param array<string, mixed>|null $link   Ссылка для ответа/цитирования.
     */
    public function editComment(
        string $messageId,
        string $commentId,
        string|CommentPayload $comment,
        TextFormat|string|null $format = null,
        ?array $link = null,
    ): ActionResult;

    /**
     * Удалить комментарий к посту.
     *
     * @param string $messageId ID поста в канале.
     * @param string $commentId ID удаляемого комментария.
     */
    public function deleteComment(string $messageId, string $commentId): ActionResult;
}
