# Обработка ошибок

## Иерархия исключений

```
MaxException (базовый)
├── MaxApiException        — Ошибки API (4xx/5xx)
├── MaxConfigException     — Ошибки конфигурации
├── MaxConnectionException — Ошибки сети/соединения
├── MaxFileException       — Ошибки загрузки файлов
└── MaxValidationException — Ошибки валидации входных данных
```

## Обработка ошибок API

```php
use MaxBotSdk\Exception\MaxApiException;

try {
    $me = $client->bot()->getMe();
} catch (MaxApiException $e) {
    echo 'Ошибка API: ' . $e->getMessage();
    echo 'Код статуса: ' . $e->getStatusCode();
    echo 'Описание: ' . $e->getDescription();
    echo 'Код ошибки: ' . $e->getErrorCode();

    // Типичные коды:
    // 401 — Невалидный токен
    // 403 — Бот не имеет прав
    // 404 — Ресурс не найден
    // 429 — Превышение лимита запросов (SDK автоматически повторяет)
    // 500 — Внутренняя ошибка сервера (SDK автоматически повторяет)
}
```

## Обработка ошибок конфигурации

```php
use MaxBotSdk\Exception\MaxConfigException;

try {
    $config = new Config(''); // Пустой токен
} catch (MaxConfigException $e) {
    echo 'Ошибка конфигурации: ' . $e->getMessage();
}

try {
    $config = Config::fromEnvironment(); // MAX_BOT_TOKEN не задан
} catch (MaxConfigException $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
```

## Обработка сетевых ошибок

```php
use MaxBotSdk\Exception\MaxConnectionException;

try {
    $chats = $client->chats()->getChats();
} catch (MaxConnectionException $e) {
    echo 'Сетевая ошибка: ' . $e->getMessage();
    // Таймаут, DNS-ошибка, отказ в соединении и т.д.
}
```

## Обработка ошибок загрузки

```php
use MaxBotSdk\Exception\MaxFileException;

try {
    $token = $client->uploads()->uploadFile(UploadType::Image, '/path/to/photo.jpg');
} catch (MaxFileException $e) {
    echo 'Ошибка файла: ' . $e->getMessage();
    // Файл не найден, не читается, ошибка при загрузке
}
```

## Обработка ошибок валидации

```php
use MaxBotSdk\Exception\MaxValidationException;
use MaxBotSdk\Utils\KeyboardBuilder;

try {
    // Слишком много кнопок
    $keyboard = KeyboardBuilder::build($tooManyRows);
} catch (MaxValidationException $e) {
    echo 'Ошибка валидации: ' . $e->getMessage();
}
```

## Полный обработчик (рекомендуемый шаблон)

```php
use MaxBotSdk\Exception\MaxException;
use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\Exception\MaxConnectionException;

try {
    $client = ClientFactory::create($token);
    $me = $client->bot()->getMe();
    echo 'Подключён: ' . $me->getName();

} catch (MaxApiException $e) {
    // Ошибка API (неверный токен, нет прав, лимиты)
    error_log('API ошибка: [' . $e->getStatusCode() . '] ' . $e->getMessage());

} catch (MaxConnectionException $e) {
    // Сетевая ошибка
    error_log('Сетевая ошибка: ' . $e->getMessage());

} catch (MaxException $e) {
    // Другие ошибки SDK
    error_log('SDK ошибка: ' . $e->getMessage());
}
```

## Автоматические повторные попытки (RetryHandler)

SDK автоматически повторяет запросы при транзиентных ошибках:

- **429** (Too Many Requests) — ✅ повтор
- **500** (Internal Server Error) — ✅ повтор
- **502** (Bad Gateway) — ✅ повтор
- **503** (Service Unavailable) — ✅ повтор
- **504** (Gateway Timeout) — ✅ повтор
- **4xx** (кроме 429) — ❌ без повтора

Стратегия: exponential backoff с jitter. Количество повторов задаётся через `ConfigBuilder`:

```php
use MaxBotSdk\ConfigBuilder;
use MaxBotSdk\ClientFactory;

$config = ConfigBuilder::create('TOKEN')
    ->withRetries(5) // По умолчанию 3
    ->build();

$client = ClientFactory::fromConfig($config);
```
