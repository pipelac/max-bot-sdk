<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\Contracts\CommentsResourceInterface;
use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\CommentMessage;
use MaxBotSdk\DTO\CommentPayload;
use MaxBotSdk\DTO\PaginatedResult;
use MaxBotSdk\Enum\TextFormat;
use MaxBotSdk\Utils\InputValidator;

/**
 * Ресурс: управление комментариями к постам в каналах MAX Bot API.
 *
 * Предоставляет полный набор CRUD-методов для взаимодействия с API комментариев.
 *
 * @since 2.3.0
 */
final class Comments extends ResourceAbstract implements CommentsResourceInterface
{
    public const PATH_COMMENTS = '/messages/%s/comments';
    public const PATH_COMMENT = '/messages/%s/comments/%s';
    public const DEFAULT_COUNT = 50;
    public const MAX_COMMENT_LENGTH = 4000;

    /**
     * @return PaginatedResult<CommentMessage>
     */
    public function getComments(
        string $messageId,
        ?int $count = null,
        ?string $before = null,
        ?string $after = null,
        ?array $commentIds = null,
    ): PaginatedResult {
        $messageId = InputValidator::validateMessageId($messageId);
        $count = InputValidator::validateCommentCount($count);

        $query = [];
        if ($count !== null) {
            $query['count'] = $count;
        }
        if ($before !== null && trim($before) !== '') {
            $query['before'] = trim($before);
        }
        if ($after !== null && trim($after) !== '') {
            $query['after'] = trim($after);
        }
        if ($commentIds !== null && $commentIds !== []) {
            $query['comment_ids'] = implode(',', $commentIds);
        }

        $endpoint = \sprintf(self::PATH_COMMENTS, $messageId);
        $data = $this->get($endpoint, $query);

        return PaginatedResult::fromApiResponse($data, 'comments', CommentMessage::class);
    }

    public function getComment(string $messageId, string $commentId): CommentMessage
    {
        $messageId = InputValidator::validateMessageId($messageId);
        $commentId = InputValidator::validateCommentId($commentId);

        $endpoint = \sprintf(self::PATH_COMMENT, $messageId, $commentId);
        $data = $this->get($endpoint);

        return CommentMessage::fromArray($data);
    }

    public function addComment(
        string $messageId,
        string|CommentPayload $comment,
        TextFormat|string|null $format = null,
        ?array $link = null,
    ): CommentMessage {
        $messageId = InputValidator::validateMessageId($messageId);
        $payload = $this->resolvePayload($comment, $format, $link);

        $endpoint = \sprintf(self::PATH_COMMENTS, $messageId);
        $data = $this->post($endpoint, $payload);

        return CommentMessage::fromArray($data);
    }

    public function editComment(
        string $messageId,
        string $commentId,
        string|CommentPayload $comment,
        TextFormat|string|null $format = null,
        ?array $link = null,
    ): ActionResult {
        $messageId = InputValidator::validateMessageId($messageId);
        $commentId = InputValidator::validateCommentId($commentId);
        $payload = $this->resolvePayload($comment, $format, $link);

        $endpoint = \sprintf(self::PATH_COMMENTS, $messageId);
        $data = $this->put($endpoint, $payload, ['comment_id' => $commentId]);

        return ActionResult::fromArray($data);
    }

    public function deleteComment(string $messageId, string $commentId): ActionResult
    {
        $messageId = InputValidator::validateMessageId($messageId);
        $commentId = InputValidator::validateCommentId($commentId);

        $endpoint = \sprintf(self::PATH_COMMENTS, $messageId);
        $data = $this->delete($endpoint, ['comment_id' => $commentId]);

        return ActionResult::fromArray($data);
    }

    /**
     * Преобразовать входящие данные в массив NewCommentBody.
     *
     * @param string|CommentPayload  $comment
     * @param TextFormat|string|null $format
     * @param array<string, mixed>|null $link
     * @return array<string, mixed>
     */
    private function resolvePayload(
        string|CommentPayload $comment,
        TextFormat|string|null $format = null,
        ?array $link = null,
    ): array {
        if ($comment instanceof CommentPayload) {
            return $comment->toArray();
        }

        $text = InputValidator::validateText($comment);
        $payload = ['text' => $text];

        if ($format !== null) {
            $payload['format'] = $format instanceof TextFormat ? $format->value : (string) $format;
        }

        if ($link !== null) {
            $payload['link'] = $link;
        }

        return $payload;
    }
}
