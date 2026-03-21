# Расширенные возможности

## Пагинация

Методы, возвращающие списки данных, используют `PaginatedResult`:

```php
$result = $client->chats()->getChats(50);

while (true) {
    foreach ($result->getItems() as $chat) {
        echo $chat->getTitle() . PHP_EOL;
    }

    if (!$result->hasMore()) {
        break;
    }

    $result = $client->chats()->getChats(50, $result->getMarker());
}
```

`PaginatedResult` предоставляет:
- `getItems()` — элементы текущей страницы (массив DTO)
- `count()` — количество элементов на текущей странице
- `hasMore()` — есть ли следующая страница
- `getMarker()` — маркер для запроса следующей страницы

## Retry-логика

SDK автоматически повторяет запросы при transient-ошибках (HTTP 429, 5xx):

```php
use App\Component\Max\ConfigBuilder;
use App\Component\Max\ClientFactory;

$config = ConfigBuilder::create('TOKEN')
    ->withRetries(5)  // Максимум 5 повторных попыток (по умолчанию 3, макс. 10)
    ->build();

$client = ClientFactory::createFromConfig($config);
```

Поведение retry:
- **Exponential backoff**: задержка = base × 2^(attempt-1)
- **Jitter**: ±10% от рассчитанной задержки
- **Базовая задержка**: 1000 мс
- **Максимальная задержка**: 30 000 мс
- **Retryable ошибки**: HTTP 429 (Too Many Requests), HTTP 5xx (Server Error)

## ConfigBuilder (Fluent API)

Рекомендуемый способ создания конфигурации с нестандартными параметрами:

```php
use App\Component\Max\ConfigBuilder;

$config = ConfigBuilder::create('TOKEN')
    ->withTimeout(60)        // 5–300 сек (по умолчанию 30)
    ->withRetries(5)         // 0–10 (по умолчанию 3)
    ->withRateLimit(20)      // 1–100 запросов/сек (по умолчанию 30)
    ->withVerifySsl(true)    // Проверка SSL (по умолчанию true)
    ->withLogRequests(true)  // Логирование запросов (по умолчанию true)
    ->withAppName('MyApp')   // Имя для логов (по умолчанию 'MaxBot')
    ->withLogger($logger)    // LoggerInterface (опционально)
    ->build();
```

## Rate Limiting

SDK поддерживает ограничение количества запросов в секунду:

```php
$config = ConfigBuilder::create('TOKEN')
    ->withRateLimit(20) // Макс. 20 запросов/сек (по умолчанию 30)
    ->build();
```

MAX API рекомендует не более 30 запросов в секунду.

## Webhook обработка (Advanced)

### Подписка с фильтрацией типов обновлений

```php
$client->subscriptions()->subscribe(
    'https://example.com/webhook',
    array('message_created', 'message_callback'), // Только эти типы
    null,  // версия API
    'my_secret_key_256_chars_max' // Секрет для верификации (5–256 символов)
);
```

### Верификация секрета

```php
use App\Component\Max\Utils\WebhookHandler;

$handler = new WebhookHandler();

// Timing-safe сравнение (защита от timing attacks)
$isValid = $handler->verifySecret(
    'my_secret',
    $_SERVER['HTTP_X_MAX_BOT_API_SECRET']
);
```

### Обработка разных типов обновлений

```php
$update = $handler->parseUpdate(file_get_contents('php://input'));

if ($update !== null) {
    switch ($update->getUpdateType()) {
        case 'message_created':
            $text = $update->getMessage()->getText();
            $chatId = $update->getMessage()->getChatId();
            break;

        case 'message_callback':
            $callbackId = $update->getCallbackId();
            $payload = $update->getCallbackPayload();
            $client->callbacks()->answerCallback($callbackId, null, 'Принято!');
            break;

        case 'bot_started':
            $user = $update->getUser();
            echo 'Новый пользователь: ' . $user->getName();
            break;
    }
}
```

## Загрузка файлов

Полный 3-этапный процесс:

```php
// Способ 1: Автоматический (шаги 1+2)
$token = $client->uploads()->uploadFile('image', '/path/to/photo.jpg');

// Способ 2: Пошаговый
$uploadResult = $client->uploads()->getUploadUrl('video');
$fileResult = $client->uploads()->uploadFileToUrl($uploadResult->getUrl(), '/path/to/video.mp4');
$token = $fileResult->getToken();

// Использование token в сообщении
$client->messages()->sendMessage(array(
    'text'        => 'Фото',
    'attachments' => array(
        array('type' => 'image', 'payload' => array('token' => $token)),
    ),
), null, $chatId);
```

## Inline-клавиатуры

```php
use App\Component\Max\Utils\KeyboardBuilder;

// Строим клавиатуру
$keyboard = KeyboardBuilder::build(array(
    // Ряд 1
    array(
        array('type' => 'callback', 'text' => 'Да', 'payload' => 'yes'),
        array('type' => 'callback', 'text' => 'Нет', 'payload' => 'no'),
    ),
    // Ряд 2
    array(
        array('type' => 'link', 'text' => 'Сайт', 'url' => 'https://example.com'),
    ),
));

// Лимиты MAX API:
// - Максимум 210 кнопок
// - Максимум 30 рядов
// - Максимум 7 кнопок в ряду
```

## Конфигурация из переменных окружения

```bash
export MAX_BOT_TOKEN="your_token_here"
export MAX_BOT_TIMEOUT=60
export MAX_BOT_RETRIES=5
export MAX_BOT_RATE_LIMIT=20
export MAX_BOT_VERIFY_SSL=true
export MAX_BOT_LOG_REQUESTS=true
export MAX_BOT_APP_NAME="MyBot"
```

```php
use App\Component\Max\ClientFactory;

$client = ClientFactory::createFromEnvironment();
```

## Дополнительные ресурсы

### Управление чатами

```php
// Удалить чат
$client->chats()->deleteChat($chatId);

// Отправить действие (typing indicator)
$client->chats()->sendAction($chatId, 'typing_on');

// Закрепить сообщение
$client->chats()->pinMessage($chatId, $messageId);

// Получить закреплённое сообщение
$pinned = $client->chats()->getPinnedMessage($chatId);
```

### Управление участниками

```php
// Администраторы
$admins = $client->members()->getAdmins($chatId);
$client->members()->addAdmin($chatId, $userId);
$client->members()->removeAdmin($chatId, $userId);

// Участие бота
$myMembership = $client->members()->getMyMembership($chatId);
$client->members()->leaveChat($chatId);
```
