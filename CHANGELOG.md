# CHANGELOG

Все значимые изменения в этом проекте документируются в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/),
проект следует [семантическому версионированию](https://semver.org/lang/ru/).

## [2.0.1] — 2026-04-04

### Исправлено
- Корректный парсинг `Message`: теперь SDK правильно обратывает вложенную структуру данных (включая массив `body`) для поступающих через Webhook сообщений (тип `message_created`).
- Добавлен fallback в `Update::getChatId()`: если `chat_id` отсутствует на верхнем уровне массива, метод попытается извлечь его из вложенного объекта сообщения.

## [2.0.0] — 2026-03-22

Мажорный релиз: полный рефакторинг SDK для PHP 8.1+ без обратной совместимости.

### Breaking Changes

- **Минимальная версия PHP: 8.1** (ранее 5.6)
- PHPUnit обновлён до ^10.5 (ранее ^9.6)
- PHPStan level повышен до 9 (ранее 5)
- `declare(strict_types=1)` во всех файлах
- Метод `ClientFactory::createFromConfig()` → `ClientFactory::fromConfig()`
- Метод `ClientFactory::createFromBuilder()` → `ClientFactory::fromBuilder()`
- Метод `ClientFactory::createFromEnvironment()` → `ClientFactory::fromEnvironment()`
- Метод `ClientFactory::createFromIni()` → `ClientFactory::fromIni()`
- `Uploads::getUploadUrl(string $type)` → `Uploads::getUploadUrl(UploadType $type)` (enum)
- `Uploads::uploadFile(string $type, ...)` → `Uploads::uploadFile(UploadType $type, ...)` (enum)
- `Message::getChatId()` удалён — используйте `Message::getRecipient()['chat_id']`
- `Update::getCallbackId()` удалён — используйте `Update::getCallback()['callback_id']`
- `Update::getCallbackPayload()` удалён — используйте `Update::getCallback()['payload']`
- Все свойства Config, DTOs, Exceptions — `readonly`
- Все исключения-листья помечены `final`
- PHP-CS-Fixer обновлён с PSR-2 до PER-CS 2.0

### Добавлено

- **Enums**: `HttpMethod`, `UploadType`, `LogLevel`, `UpdateType` — backed string enums
- **Typed properties**: `readonly` typed properties во всех классах
- **Constructor promotion**: Config, RetryHandler, RateLimiter, Exceptions
- **`match` expression**: Config `toBool()`, CurlHttpClient, Client log level
- **Null-safe operator `?->`**: Client, ResponseDecoder
- `str_starts_with()` / `str_contains()`: InputValidator, CurlHttpClient
- `never` return type: ResponseDecoder `handleError()`
- `\CurlHandle` type hint: CurlHttpClient
- `random_int()`: RetryHandler jitter (вместо `mt_rand`)
- `#[Test]` attributes: все тесты (вместо `/** @test */` / `testPrefix`)
- `@template` generics: PaginatedResult, RetryHandler
- **HMAC-верификация**: `hash_equals()` в WebhookHandler для timing-safe сравнения
- **CURLOPT_FOLLOWLOCATION = false**: отключены автоматические HTTP-редиректы
- Ветка `1.x` для поддержки PHP 5.6+
- CI-бейдж и таблица совместимости веток в README
- Полный набор docs/ гайдов для v2

### Инфраструктура

- CI матрица: PHP 8.1, 8.2, 8.3
- PHPStan phpVersion: 80100, level: 9
- CI триггеры: `master`, `1.x`, `develop`
- PHPUnit: 275 тестов, 569 assertions

---

## [1.0.0] — 2026-03-21

Первый стабильный релиз MAX Bot API SDK.

### Архитектура

- Модульная архитектура с 7 ресурсами: Bot, Chats, Messages, Members, Subscriptions, Uploads, Callbacks
- `Client` — главный фасад с явными методами доступа к ресурсам и ленивой инициализацией
- `ClientFactory` — фабрика клиентов (из токена, INI-файла, ENV переменных)
- `Config` / `ConfigBuilder` — конфигурация с fluent-интерфейсом
- `ResourceAbstract` — базовый класс ресурсов, зависит от `ClientInterface` (DIP)
- 6 контрактов: `ClientInterface`, `ConfigInterface`, `HttpClientInterface`, `LoggerInterface`, `ResourceInterface`, `ResponseDecoderInterface`

### Ресурсы API

Полное покрытие всех 29 эндпоинтов MAX Bot API:

- `Resource\Bot` — информация о боте (`getMe`)
- `Resource\Chats` — управление чатами (CRUD, pin/unpin, actions)
- `Resource\Members` — участники и администраторы чатов
- `Resource\Messages` — отправка, получение, редактирование, удаление сообщений
- `Resource\Subscriptions` — webhooks и long polling
- `Resource\Uploads` — загрузка файлов (3-этапный процесс) и видео
- `Resource\Callbacks` — ответы на callback-нажатия inline-кнопок

### DTO (Data Transfer Objects)

Типизированные immutable-объекты: User, Chat, ChatMember, Message, Attachment, Update, Subscription, UploadResult, PaginatedResult, UpdatesResult, ActionResult, VideoInfo, AbstractDto

### HTTP-слой

- `CurlHttpClient` — cURL-транспорт с маскированием токена
- `Http\RetryHandler` — автоматические повторные попытки с exponential backoff и jitter (429, 5xx)
- `ResponseDecoder` — декодирование JSON-ответов с обработкой ошибок API и HTTP 204

### Утилиты

- `Utils\KeyboardBuilder` — построение inline-клавиатур с валидацией лимитов MAX API
- `Utils\WebhookHandler` — обработка webhook-запросов (timing-safe верификация)
- `Utils\InputValidator` — валидация ID, текста, URL, типов файлов, callback_id

### Обработка ошибок

- `MaxException` → `MaxApiException`, `MaxConfigException`, `MaxConnectionException`, `MaxFileException`, `MaxValidationException`

### Инфраструктура

- `composer.json` — PSR-4 autoload, PHP 5.6+
- PHPStan level 5
- PHPUnit тесты (214 тестов, 430 assertions)
- CI/CD: GitHub Actions (PHP 7.3/7.4/8.0/8.1/8.2/8.3)
