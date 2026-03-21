# Webhooks и Long Polling

## Webhook (рекомендуется для production)

### Настройка webhook

```php// Подписка на все основные типы событий
$client->subscriptions()->subscribe(
    'https://your-domain.com/max-webhook',
    [
        'message_created',
        'message_callback',
        'message_edited',
        'message_removed',
        'bot_started',
        'bot_added',
        'bot_removed',
        'user_added',
        'user_removed',
        'chat_title_changed',
    ],
    null,  // версия API (null = текущая)
    'your_secret_key_5_to_256_chars'
);
```

### Требования к endpoint

- **Протокол** — HTTPS
- **Порт** — 443
- **TLS-сертификат** — от CA (не самоподписанный)
- **Время ответа** — HTTP 200 за ≤30 секунд

### Безопасность webhook

```phpuse MaxBotSdk\Utils\WebhookHandler;

$handler = new WebhookHandler();
$secret = isset($_SERVER['HTTP_X_MAX_BOT_API_SECRET'])
    ? $_SERVER['HTTP_X_MAX_BOT_API_SECRET']
    : '';

if (!$handler->verifySecret('your_secret_key', $secret)) {
    http_response_code(403);
    exit('Forbidden');
}
```

MAX API передаёт секрет в заголовке `X-Max-Bot-Api-Secret`. `WebhookHandler` использует `hash_equals()` для timing-safe сравнения.

### Обработка webhook

```phpuse MaxBotSdk\Utils\WebhookHandler;

$handler = new WebhookHandler();
$update = $handler->parseUpdate(file_get_contents('php://input'));

if ($update === null) {
    http_response_code(200);
    exit;
}

switch ($update->getUpdateType()) {
    case 'message_created':
        $message = $update->getMessage();
        $text = $message->getText();
        $senderId = $message->getSender()->getUserId();
        $chatId = $message->getChatId();
        // Обработка...
        break;

    case 'message_callback':
        $callbackId = $update->getCallbackId();
        $payload = $update->getCallbackPayload();
        $client->callbacks()->answerCallback($callbackId, null, 'Обработано: ' . $payload);
        break;

    case 'bot_started':
        $userId = $update->getUser()->getUserId();
        $chatId = $update->getChatId();
        $client->messages()->sendMessage(
            ['text' => 'Добро пожаловать!'],
            null,
            $chatId
        );
        break;

    case 'message_edited':
        // Сообщение отредактировано
        break;

    case 'message_removed':
        // Сообщение удалено
        $removedId = $update->getMessageId();
        break;

    case 'bot_added':
        // Бот добавлен в чат
        break;

    case 'bot_removed':
        // Бот удалён из чата
        break;
}

http_response_code(200);
```

### Политика повторов MAX API

Если webhook endpoint не отвечает HTTP 200 в течение 30 секунд, MAX API повторно отправляет запрос. При многократных сбоях подписка может быть отключена.

### Управление подписками

```php// Получить список подписок → Subscription[]
$subscriptions = $client->subscriptions()->getSubscriptions();
foreach ($subscriptions as $sub) {
    echo $sub->getUrl() . ' — ' . implode(', ', $sub->getUpdateTypes()) . "\n";
}

// Удалить конкретную подписку
$client->subscriptions()->unsubscribe('https://your-domain.com/max-webhook');
```

## Long Polling (для разработки/тестирования)

> **Важно:** Long Polling не работает при наличии активной webhook-подписки. Для использования сначала удалите подписку: `$client->subscriptions()->unsubscribe('https://...')`.

### Базовый цикл

```php$marker = null;

while (true) {
    try {
        /** @var \MaxBotSdk\DTO\UpdatesResult $result */
        $result = $client->subscriptions()->getUpdates(100, 30, $marker);

        foreach ($result->getUpdates() as $update) {
            // $update — объект DTO\Update
            handleUpdate($update);
        }

        // Сохранить маркер для следующего запроса
        $marker = $result->getMarker();

    } catch (\MaxBotSdk\Exception\MaxApiException $e) {
        error_log('Ошибка polling: ' . $e->getMessage());
        sleep(5); // Пауза перед повтором
    }
}
```

### С фильтрацией типов

```php$result = $client->subscriptions()->getUpdates(
    50,     // limit
    30,     // timeout
    $marker,
    ['message_created', 'message_callback'] // типы событий
);

foreach ($result->getUpdates() as $update) {
    // ...
}
```

## Типы обновлений (update_types)

- `message_created` — новое сообщение
- `message_callback` — нажатие inline-кнопки
- `message_edited` — сообщение отредактировано
- `message_removed` — сообщение удалено
- `bot_started` — пользователь начал диалог с ботом
- `bot_added` — бот добавлен в чат
- `bot_removed` — бот удалён из чата
- `user_added` — пользователь добавлен в чат
- `user_removed` — пользователь удалён из чата
- `chat_title_changed` — изменён заголовок чата

## Следующие шаги

- [Загрузка файлов](04-file-uploads.md)
- [Обработка ошибок](05-error-handling.md)
