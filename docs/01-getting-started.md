# 01. Начало работы с MAX Bot API SDK

## Установка

```bash
composer require pipelac/max-bot-sdk
```

## Создание клиента

### Вариант 1: С токеном напрямую

```phpuse MaxBotSdk\ClientFactory;

$client = ClientFactory::create('ВАШ_ТОКЕН_БОТА');
```

### Вариант 2: С логгером

```phpuse MaxBotSdk\ClientFactory;

// $logger — объект, реализующий LoggerInterface (4 метода: debug, info, warning, error)
$client = ClientFactory::create('ВАШ_ТОКЕН', $logger);
```

> Подробные примеры логгеров (файловый, PSR-3/Monolog, ConfigBuilder) — см. [Расширенные возможности](06-advanced-features.md#подключение-логгера).

### Вариант 3: С кастомным HTTP-клиентом

```phpuse MaxBotSdk\ClientFactory;

// $httpClient — объект, реализующий HttpClientInterface (3 метода: request, getLastStatusCode, getBaseUrl)
$client = ClientFactory::create('ВАШ_ТОКЕН', null, $httpClient);

// С логгером и кастомным HTTP-клиентом:
$client = ClientFactory::create('ВАШ_ТОКЕН', $logger, $httpClient);
```

> Примеры адаптеров (Guzzle, мок-клиент для тестов) — см. [Расширенные возможности](06-advanced-features.md#подключение-кастомного-http-клиента).

### Вариант 4: Из INI-файла

```ini
; cfg/config.ini
[max]
token = "YOUR_TOKEN"
timeout = 30
retries = 3
rate_limit = 30
verify_ssl = true
log_requests = true
app_name = "MaxBot"
```

```phpuse MaxBotSdk\ClientFactory;

// Автоматически ищет cfg/config.ini:
$client = ClientFactory::createFromIni();

// Или с указанием пути:
$client = ClientFactory::createFromIni('/path/to/config.ini');
```

### Вариант 5: Из переменных окружения (12-Factor App)

```phpuse MaxBotSdk\ClientFactory;

// Требуется: MAX_BOT_TOKEN
// Опционально: MAX_BOT_TIMEOUT, MAX_BOT_RETRIES, MAX_BOT_RATE_LIMIT,
//              MAX_BOT_VERIFY_SSL, MAX_BOT_LOG_REQUESTS, MAX_BOT_APP_NAME
$client = ClientFactory::createFromEnvironment();
```

### Вариант 6: Через ConfigBuilder (максимальная гибкость)

```phpuse MaxBotSdk\ConfigBuilder;
use MaxBotSdk\ClientFactory;

$config = ConfigBuilder::create('TOKEN')
    ->withTimeout(60)
    ->withRetries(5)
    ->withRateLimit(20)
    ->withVerifySsl(true)
    ->withLogRequests(true)
    ->withAppName('МойБот')
    ->withLogger($logger)
    ->build();

$client = ClientFactory::createFromConfig($config);

// С кастомным HTTP-клиентом:
$client = ClientFactory::createFromConfig($config, $httpClient);
```

## Проверка подключения

```phptry {
    $me = $client->bot()->getMe();
    echo 'Бот подключён: ' . $me->getName() . ' (@' . $me->getUsername() . ')';
} catch (\MaxBotSdk\Exception\MaxApiException $e) {
    echo 'Ошибка API: ' . $e->getMessage();
} catch (\MaxBotSdk\Exception\MaxConnectionException $e) {
    echo 'Ошибка сети: ' . $e->getMessage();
}
```

## Отправка сообщений

```php// Текстовое сообщение в чат:
$message = $client->messages()->sendMessage(
    ['text' => 'Привет из MAX Bot API SDK!'],
    null,    // notify (null = по умолчанию)
    $chatId  // ID чата
);
echo 'Отправлено, ID: ' . $message->getMessageId();

// Быстрая отправка текста (shortcut):
$message = $client->messages()->sendText('Привет!', $chatId);
```

## Доступные ресурсы

```php$client->bot()           // Информация о боте → User DTO
$client->chats()         // Управление чатами → Chat / PaginatedResult
$client->members()       // Участники чатов → ChatMember / PaginatedResult
$client->messages()      // Сообщения → Message / PaginatedResult
$client->subscriptions() // Webhooks и Long Polling → Subscription[] / UpdatesResult
$client->uploads()       // Загрузка файлов → UploadResult / string (token)
$client->callbacks()     // Ответы на callback-кнопки → ActionResult
```

## Настройка Webhook

```php// Подписка на все обновления:
$client->subscriptions()->subscribe('https://example.com/webhook');

// Подписка на определённые типы:
$client->subscriptions()->subscribe(
    'https://example.com/webhook',
    ['message_created', 'message_callback'],
    null,               // версия API
    'my_secret_key'     // секрет для верификации
);
```

## Обработка Webhook

```phpuse MaxBotSdk\Utils\WebhookHandler;

$handler = new WebhookHandler();

// Проверка подлинности
$secret = isset($_SERVER['HTTP_X_MAX_BOT_API_SECRET'])
    ? $_SERVER['HTTP_X_MAX_BOT_API_SECRET']
    : '';
if (!$handler->verifySecret('my_secret_key', $secret)) {
    http_response_code(403);
    exit;
}

// Парсинг Update → возвращает DTO\Update
$update = $handler->parseUpdate(file_get_contents('php://input'));
if ($update === null) {
    http_response_code(200);
    exit;
}

switch ($update->getUpdateType()) {
    case 'message_created':
        $text = $update->getMessage()->getText();
        $chatId = $update->getMessage()->getChatId();
        $client->messages()->sendMessage(
            ['text' => 'Вы написали: ' . $text],
            null, $chatId
        );
        break;

    case 'message_callback':
        $callbackId = $update->getCallbackId();
        $client->callbacks()->answerCallback($callbackId, null, 'Принято!');
        break;
}

http_response_code(200);
```
