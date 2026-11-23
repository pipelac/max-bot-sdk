# Работа с комментариями в каналах (Comments API)

MAX Bot API предоставляет возможность ботам читать, публиковать, редактировать и модерировать комментарии к публикациям в каналах.

> **Важно:** Для работы с комментариями бот должен быть добавлен в канал в качестве администратора с правами:
> - `read_all_messages` — чтение всех сообщений и комментариев
> - `write` — публикация сообщений и ответов

---

## Архитектура работы с комментариями

В SDK за комментарии отвечает ресурс `$client->comments()`, реализующий интерфейс [CommentsResourceInterface](../src/Contracts/CommentsResourceInterface.php).

### Ключевые компоненты:
- [Comments](../src/Resource/Comments.php) — ресурс API со всеми CRUD-методами.
- [CommentMessage](../src/DTO/CommentMessage.php) — неизменяемый типизированный DTO комментария. В отличие от стандартного `Message`, комментарии не содержат вложений (`attachments`) и не поддерживают пересылку (`link.type = forward`).
- [CommentPayload](../src/DTO/CommentPayload.php) — Fluent Value Object / Builder для конструирования тела комментария (`NewCommentBody`).
- [TextFormat](../src/Enum/TextFormat.php) — Backed Enum для форматов разметки текста (`TextFormat::Markdown`, `TextFormat::Html`).

---

## Получение списка комментариев

Метод `getComments()` возвращает [PaginatedResult](../src/DTO/PaginatedResult.php) со списком объектов `CommentMessage`.

```php
use MaxBotSdk\ClientFactory;

$client = ClientFactory::create('YOUR_BOT_TOKEN');

// Получение первых 50 комментариев к публикации
$result = $client->comments()->getComments('message_id_12345');

foreach ($result->getItems() as $comment) {
    /** @var \MaxBotSdk\DTO\CommentMessage $comment */
    echo sprintf(
        "[%s] %s: %s\n",
        $comment->getDateTime()->format('Y-m-d H:i:s'),
        $comment->isChannelPost() ? 'Канал' : ($comment->getSender()?->getName() ?? 'Аноним'),
        $comment->getText()
    );
}
```

### Параметры пагинации и фильтрации

```php
$result = $client->comments()->getComments(
    messageId: 'message_id_12345',
    count: 20,                  // 1..100 (по умолчанию 50)
    before: 1763000099000,      // Unix timestamp (мс) или маркер: комментарии до указанной метки
    after: 1763000000000,       // Unix timestamp (мс) или маркер: комментарии после указанной метки
    commentIds: ['c_1', 'c_2']  // фильтр по конкретным ID комментариев
);
```

---

## Получение конкретного комментария

```php
$comment = $client->comments()->getComment('message_id_12345', 'comment_id_999');

echo 'ID: ' . $comment->getCommentId() . PHP_EOL;
echo 'Текст: ' . $comment->getText() . PHP_EOL;
echo 'Формат: ' . ($comment->getFormatString() ?? 'обычный текст') . PHP_EOL;
```

---

## Публикация комментария

SDK поддерживает два способа публикации комментария:
1. Простой строковый аргумент с опциональным форматом и ссылкой.
2. Fluent-билдер `CommentPayload`.

### Вариант 1: Простой строковый вызов

```php
use MaxBotSdk\Enum\TextFormat;

// Простой текстовый комментарий
$comment = $client->comments()->addComment(
    'message_id_12345',
    'Отличная публикация!'
);

// С Markdown-разметкой, ответом на предыдущий комментарий и отключённым превью ссылок
$comment = $client->comments()->addComment(
    'message_id_12345',
    'Спасибо за **обратную связь**! См. https://example.com',
    TextFormat::Markdown,
    ['type' => 'reply', 'mid' => 'comment_id_999'],
    true // disableLinkPreview
);
```

### Вариант 2: Использование CommentPayload Builder

```php
use MaxBotSdk\DTO\CommentPayload;
use MaxBotSdk\Enum\TextFormat;

$payload = CommentPayload::create('Здравствуйте, <b>коллеги</b>!')
    ->withFormat(TextFormat::Html)
    ->withReplyTo('comment_id_999');

$comment = $client->comments()->addComment('message_id_12345', $payload);
```

---

## Редактирование комментария

> **Лимит по времени:** Редактирование комментария доступно в течение **26 часов** с момента его публикации. При превышении этого времени API возвращает ошибку `422 Unprocessable Entity` (`comments.timeout_exceeded`).

```php
use MaxBotSdk\Enum\TextFormat;

// Через строку:
$result = $client->comments()->editComment(
    'message_id_12345',
    'comment_id_999',
    'Обновлённый текст комментария',
    TextFormat::Markdown
);

if ($result->isSuccess()) {
    echo 'Комментарий успешно отредактирован!';
}

// Через CommentPayload:
$payload = CommentPayload::create('Новый отредактированный текст');
$result = $client->comments()->editComment('message_id_12345', 'comment_id_999', $payload);
```

---

## Удаление комментария (модерация)

Бот-администратор канала может удалять любые нежелательные комментарии:

```php
$result = $client->comments()->deleteComment('message_id_12345', 'comment_id_spam');

if ($result->isSuccess()) {
    echo 'Спам-комментарий удалён';
}
```

---

## Обработка событий комментариев через Webhook

При подключении webhook или long polling события комментариев приходят в следующих типах `UpdateType`:
- `UpdateType::CommentCreated` (`comment_created`) — опубликован новый комментарий.
- `UpdateType::CommentEdited` (`comment_edited`) — комментарий был отредактирован.
- `UpdateType::CommentRemoved` (`comment_removed`) — комментарий удалён (ID родительского поста доступен через `$update->getPostId()`).

### Пример обработчика Webhook:

```php
use MaxBotSdk\Enum\UpdateType;
use MaxBotSdk\Utils\WebhookHandler;

$handler = new WebhookHandler();
$update = $handler->parseUpdate(file_get_contents('php://input'));

if ($update === null) {
    http_response_code(200);
    exit;
}

// Удобная проверка категории события
if ($update->isComment()) {
    $comment = $update->getComment();
    
    if ($comment !== null) {
        $parentMessageId = $comment->getMessageId();
        $text = $comment->getText();
        $author = $comment->getSender()?->getName() ?? 'Канал';

        // Проверка на запрещённые слова / спам
        if (str_contains(mb_strtolower($text), 'купить рекламу')) {
            // Удаляем спам
            if ($parentMessageId !== null) {
                $client->comments()->deleteComment($parentMessageId, $comment->getCommentId());
            }
        }
    }
}

// Строгий switch по типу события с использованием Enum
switch ($update->getType()) {
    case UpdateType::CommentCreated:
        // Новый комментарий
        break;

    case UpdateType::CommentEdited:
        // Комментарий изменён
        break;

    case UpdateType::CommentRemoved:
        // Комментарий удалён ($update->getPostId(), $update->getMessageId())
        break;

    default:
        break;
}

http_response_code(200);
```
