# Расширенные возможности

## Пагинация

Методы, возвращающие списки данных, используют `PaginatedResult`:

```php$result = $client->chats()->getChats(50);

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

```phpuse MaxBotSdk\ConfigBuilder;
use MaxBotSdk\ClientFactory;

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

```phpuse MaxBotSdk\ConfigBuilder;

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

```php$config = ConfigBuilder::create('TOKEN')
    ->withRateLimit(20) // Макс. 20 запросов/сек (по умолчанию 30)
    ->build();
```

MAX API рекомендует не более 30 запросов в секунду.

## Webhook обработка (Advanced)

### Подписка с фильтрацией типов обновлений

```php$client->subscriptions()->subscribe(
    'https://example.com/webhook',
    ['message_created', 'message_callback'], // Только эти типы
    null,  // версия API
    'my_secret_key_256_chars_max' // Секрет для верификации (5–256 символов)
);
```

### Верификация секрета

```phpuse MaxBotSdk\Utils\WebhookHandler;

$handler = new WebhookHandler();

// Timing-safe сравнение (защита от timing attacks)
$isValid = $handler->verifySecret(
    'my_secret',
    $_SERVER['HTTP_X_MAX_BOT_API_SECRET']
);
```

### Обработка разных типов обновлений

```php$update = $handler->parseUpdate(file_get_contents('php://input'));

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

```php// Способ 1: Автоматический (шаги 1+2)
$token = $client->uploads()->uploadFile('image', '/path/to/photo.jpg');

// Способ 2: Пошаговый
$uploadResult = $client->uploads()->getUploadUrl('video');
$fileResult = $client->uploads()->uploadFileToUrl($uploadResult->getUrl(), '/path/to/video.mp4');
$token = $fileResult->getToken();

// Использование token в сообщении
$client->messages()->sendMessage([
    'text'        => 'Фото',
    'attachments' => [
        ['type' => 'image', 'payload' => ['token' => $token)),
    ),
], null, $chatId);
```

## Inline-клавиатуры

```phpuse MaxBotSdk\Utils\KeyboardBuilder;

// Строим клавиатуру
$keyboard = KeyboardBuilder::build([
    // Ряд 1
    [
        ['type' => 'callback', 'text' => 'Да', 'payload' => 'yes'),
        ['type' => 'callback', 'text' => 'Нет', 'payload' => 'no'),
    ),
    // Ряд 2
    [
        ['type' => 'link', 'text' => 'Сайт', 'url' => 'https://example.com'),
    ),
]);

// Лимиты MAX API:
// - Максимум 210 кнопок
// - Максимум 30 рядов
// - Максимум 7 кнопок в ряду
```

## Подключение логгера

SDK принимает любой логгер, реализующий `MaxBotSdk\Contracts\LoggerInterface` — 4 метода:

```phpinterface LoggerInterface
{
    public function debug($message, array $context = []);
    public function info($message, array $context = []);
    public function warning($message, array $context = []);
    public function error($message, array $context = []);
}
```

### Вариант 1: Простой файловый логгер

```phpuse MaxBotSdk\Contracts\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private $file;

    public function __construct($path)
    {
        $this->file = $path;
    }

    public function debug($message, array $context = [])
    {
        $this->write('DEBUG', $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->write('INFO', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->write('WARNING', $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->write('ERROR', $message, $context);
    }

    private function write($level, $message, array $context)
    {
        $line = date('Y-m-d H:i:s') . " [{$level}] {$message}";
        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        file_put_contents($this->file, $line . "\n", FILE_APPEND);
    }
}

// Использование:
$logger = new FileLogger('/var/log/max-bot.log');
$client = ClientFactory::create('TOKEN', $logger);
```

### Вариант 2: PSR-3 адаптер (Monolog, etc.)

Если у вас уже есть PSR-3 логгер (Monolog, Symfony Logger и др.):

```phpuse MaxBotSdk\Contracts\LoggerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

class Psr3Adapter implements LoggerInterface
{
    private $logger;

    public function __construct(PsrLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function debug($message, array $context = [])
    {
        $this->logger->debug($message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->logger->info($message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->logger->warning($message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->logger->error($message, $context);
    }
}

// Monolog:
$monolog = new \Monolog\Logger('max-bot');
$monolog->pushHandler(new \Monolog\Handler\StreamHandler('/var/log/max-bot.log'));

$logger = new Psr3Adapter($monolog);
$client = ClientFactory::create('TOKEN', $logger);
```

### Вариант 3: Через ConfigBuilder

```php$config = ConfigBuilder::create('TOKEN')
    ->withLogger($logger)
    ->withLogRequests(true)   // Логировать все HTTP-запросы
    ->withAppName('МойБот')   // Префикс в логах: "МойБот: ..."
    ->build();

$client = ClientFactory::createFromConfig($config);
```

### Вариант 4: Null-логгер (отключить логирование)

Если логгер не передан, SDK **не логирует** ничего — это поведение по умолчанию.

```php// Без логирования:
$client = ClientFactory::create('TOKEN');

// Или явно:
$config = ConfigBuilder::create('TOKEN')
    ->withLogRequests(false)
    ->build();
```

## Подключение кастомного HTTP-клиента

По умолчанию SDK использует встроенный `CurlHttpClient`. Любой метод `ClientFactory` принимает кастомный HTTP-клиент, реализующий `HttpClientInterface`:

```phpinterface HttpClientInterface
{
    /**
     * @param string $method  HTTP-метод (GET, POST, PUT, PATCH, DELETE).
     * @param string $url     URL или путь эндпоинта.
     * @param array  $options Опции: headers, json, query, multipart.
     * @return array ['status_code' => int, 'body' => string]
     */
    public function request($method, $url, array $options = []);

    /** @return int */
    public function getLastStatusCode();

    /** @return string */
    public function getBaseUrl();
}
```

### Пример: адаптер для Guzzle

```phpuse MaxBotSdk\Contracts\HttpClientInterface;
use GuzzleHttp\Client as GuzzleClient;

class GuzzleAdapter implements HttpClientInterface
{
    private $guzzle;
    private $baseUrl;
    private $lastStatusCode = 0;

    public function __construct($token, $baseUrl = 'https://platform-api.max.ru/api')
    {
        $this->baseUrl = $baseUrl;
        $this->guzzle = new GuzzleClient([
            'base_uri' => $baseUrl,
            'headers'  => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
        ]);
    }

    public function request($method, $url, array $options = [])
    {
        $guzzleOptions = [];

        if (isset($options['json'])) {
            $guzzleOptions['json'] = $options['json'];
        }
        if (isset($options['query'])) {
            $guzzleOptions['query'] = $options['query'];
        }

        $response = $this->guzzle->request($method, $url, $guzzleOptions);
        $this->lastStatusCode = $response->getStatusCode();

        return [
            'status_code' => $this->lastStatusCode,
            'body'        => (string) $response->getBody(),
        ];
    }

    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}

// Использование:
$httpClient = new GuzzleAdapter('TOKEN');
$client = ClientFactory::create('TOKEN', null, $httpClient);

// С логгером и кастомным HTTP:
$client = ClientFactory::create('TOKEN', $logger, $httpClient);

// Через Config:
$client = ClientFactory::createFromConfig($config, $httpClient);
```

### Мок-клиент для тестирования

```phpclass MockHttpClient implements HttpClientInterface
{
    private $responses = [];
    private $lastStatusCode = 200;

    public function addResponse($method, $url, $statusCode, $body)
    {
        $key = $method . ':' . $url;
        $this->responses[$key] = [
            'status_code' => $statusCode,
            'body'        => $body,
        ];
    }

    public function request($method, $url, array $options = [])
    {
        $key = $method . ':' . $url;
        if (isset($this->responses[$key])) {
            $this->lastStatusCode = $this->responses[$key]['status_code'];
            return $this->responses[$key];
        }
        $this->lastStatusCode = 200;
        return ['status_code' => 200, 'body' => '{}'];
    }

    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }

    public function getBaseUrl()
    {
        return 'https://mock-api.test';
    }
}

// В тестах:
$mock = new MockHttpClient();
$mock->addResponse('GET', '/me', 200, '{"user_id":1,"name":"TestBot","is_bot":true}');
$client = ClientFactory::create('test-token', null, $mock);

$me = $client->bot()->getMe();
// $me->getName() === 'TestBot'
```

## Дополнительные ресурсы

### Управление чатами

```php// Удалить чат
$client->chats()->deleteChat($chatId);

// Отправить действие (typing indicator)
$client->chats()->sendAction($chatId, 'typing_on');

// Закрепить сообщение
$client->chats()->pinMessage($chatId, $messageId);

// Получить закреплённое сообщение
$pinned = $client->chats()->getPinnedMessage($chatId);
```

### Управление участниками

```php// Администраторы
$admins = $client->members()->getAdmins($chatId);
$client->members()->addAdmin($chatId, $userId);
$client->members()->removeAdmin($chatId, $userId);

// Участие бота
$myMembership = $client->members()->getMyMembership($chatId);
$client->members()->leaveChat($chatId);
```
