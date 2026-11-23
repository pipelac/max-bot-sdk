# CHANGELOG

Все значимые изменения в этом проекте документируются в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/),
проект следует [семантическому версионированию](https://semver.org/lang/ru/).

## [2.4.0] — 2026-11-23

Релиз синхронизации с актуальной спецификацией **MAX Bot API OpenAPI v0.0.34** (ноябрь 2026) и прекращения поддержки устаревшей ветки PHP 5.6+.

### Добавлено
- **Унификация обработки ошибок (`SimpleQueryResult.code`, ноябрь 2026):**
  - `ActionResult::getCode(): ?string` и `ActionResult::hasError(): bool` для чтения машиночитаемого кода ошибки `code` (в том числе из ответов HTTP `200` с `success: false`).
  - `Subscription::isSuccess(): bool`, `Subscription::getCode(): ?string`, `Subscription::getMessage(): ?string` для корректной обработки ответов `POST /subscriptions` в формате `SimpleQueryResult`.
  - `ResponseDecoder`: добавлены описания статусов `403` («Доступ запрещён») и `500` («Внутренняя ошибка сервера»).
- **Новые Backed String Enums (OpenAPI v0.0.34):**
  - `ChatAdminPermission` (`src/Enum/ChatAdminPermission.php`) — все 11 официальных прав администратора чата/канала.
  - `SenderAction` (`src/Enum/SenderAction.php`) — все 6 действий бота в чате (`typing_on`, `sending_photo`, `sending_video`, `sending_audio`, `sending_file`, `mark_seen`).
- **Расширение DTO `Update` (октябрь–ноябрь 2026):**
  - Новые типизированные геттеры: `getBotId()`, `isAdmin()`, `isChannel()`, `getPostId()`, `getMutedUntil()`, `getUserLocale()`, `getPayload()`.
  - `UpdateType::BotAdminPermissionsChanged` включён в `UpdateType::isBotLifecycleEvent()`.
  - Задокументировано изменение ноября 2026: в событии `bot_added` API больше не возвращает поле `user` (`Update::getUser()` возвращает `null`).
- **Расширение ресурсов под OpenAPI v0.0.34:**
  - `Members::setAdmins(int $chatId, array $admins)` — массовое назначение администраторов (`ChatAdminsList`).
  - `Members::getMembers()`: добавлен параметр `?array $userIds` для фильтрации участников.
  - `Messages::sendMessage()`: добавлены параметры `?int $userId` (отправка в личный диалог) и `?bool $disableLinkPreview`, а `$notify` расширен до `bool|array|null`.
  - `Messages::getMessages()`: `$chatId` стал опциональным (`?int`), добавлены параметры `$messageIds`, `$before`, `$after`.
  - `Comments::addComment()`: добавлен параметр `?bool $disableLinkPreview = null`; в `Comments::getComments()` параметры `$before` / `$after` расширены до `int|string|null`.
  - `Chats::pinMessage()`: добавлен опциональный параметр `?bool $notify = null`; `Chats::sendAction()` принимает `SenderAction|string`.
- **Телеметрия HTTP-клиента (`CurlHttpClient`):**
  - Обновлена константа `SDK_VERSION = '2.4.0'`.
  - Добавлены заголовки `X-Max-Client-Name`, `X-Max-Client-Version`, `X-Max-Client-Runtime` (по аналогии с официальными TS/Go SDK MAX).

### Исправлено
- **`Comments::getComments()`**: исправлен ключ разбора списка комментариев с `'comments'` на `'messages'` согласно `CommentMessageList` в OpenAPI v0.0.34 (сохранён fallback на `'comments'` для обратной совместимости).
- **`Members::addAdmin()`**: исправлен формат тела запроса `POST /chats/{chatId}/members/admins` на `ChatAdminsList` (`{"admins": [{"user_id": ..., "permissions": [...], "alias"?: ...}]}`).
- **`User` и `ChatMember` DTO**: поддержаны поля `first_name`, `last_name` (с автоматическим формированием `getName()`), `full_avatar_url`, `commands` (для `User`) и `last_access_time`, `alias` (для `ChatMember`).
- **`VideoInfo` DTO (`GET /videos/{videoToken}`)**: поддержан объект `urls` (`mp4_1080`..`mp4_144`, `hls`) с методами `getUrls()` и `getUrlByQuality()` и автоматическим fallback в `getUrl()`.
- **`Attachment` DTO**: сохраняются все поля верхнего уровня вложений (`filename`, `size`, `width`, `height`, `duration`, `video_type`, `thumbnail`, `latitude`, `longitude`, `transcription`, `title`, `description`, `image_url`) + добавлен `getAttribute()`.
- **`Message` DTO**: добавлены `getSeq()`, `getUrl()`, `getMarkup()`, `getLinkData()` (для структурированного `LinkedMessage`) с сохранением обратной совместимости `getLink()`.
- **`Uploads::uploadFile()`**: поддержан предварительный `token` из ответа шага 1 (`POST /uploads` для видео/аудио), если CDN на шаге 2 возвращает не-JSON (например XML).
- **Документация**: исправлены названия событий в `docs/03-webhooks-and-polling.md`, `docs/08-comments.md` и `CHANGELOG.md` (v2.3.0), заменены локальные `file:///` ссылки в `docs/07-comments.md` и `docs/08-comments.md`, обновлён домен в `composer.json` на `platform-api2.max.ru`.

### Удалено
- **Ветка `1.x` (поддержка PHP 5.6+):** полностью удалена из локального проекта, GitHub, GitLab, `README.md` и CI-триггеров (`.github/workflows/ci.yml`). Актуальная поддерживаемая линейка SDK — **PHP ≥ 8.1 (`master`, v2.x)**.

## [2.3.0] — 2026-10-08

### Добавлено
- **Comments API**: Полная поддержка работы с комментариями к публикациям каналов (`/messages/{messageId}/comments` и `/messages/{messageId}/comments/{commentId}`).
  - `Comments` (`src/Resource/Comments.php`) — ресурс со всеми CRUD-операциями (`getComments()`, `getComment()`, `addComment()`, `editComment()`, `deleteComment()`).
  - `CommentsResourceInterface` (`src/Contracts/CommentsResourceInterface.php`) — контракт ресурса комментариев (Interface Segregation Principle).
  - `Client::comments()` — ленивый аксессор ресурса комментариев в главном фасаде.
  - `CommentMessage` (`src/DTO/CommentMessage.php`) — типизированный DTO комментария (без attachments, с поддержкой body, ссылок, автора канала и конвертации в `DateTimeImmutable`).
  - `CommentPayload` (`src/DTO/CommentPayload.php`) — Value Object / Fluent Builder для создания и редактирования комментариев (`create()`, `withFormat()`, `withReplyTo()`, `withLink()`).
  - `TextFormat` (`src/Enum/TextFormat.php`) — Backed String Enum форматов разметки (`Markdown = 'markdown'`, `Html = 'html'`).
- **UpdateType (расширение до 19 событий)**:
  - Комментарии: `comment_created`, `comment_edited`, `comment_removed`.
  - Жизненный цикл бота: `bot_stopped`, `bot_admin_permissions_changed`.
  - Диалоги: `dialog_cleared`, `dialog_removed`, `dialog_muted`, `dialog_unmuted`.
  - Доменные предикаты: `isCommentEvent()`, `isDialogEvent()`, `isBotLifecycleEvent()`, `isMessageEvent()`, `isMembershipEvent()`.
- **DTO Update (новые методы)**:
  - `getType(): ?UpdateType` — получение типизированного Enum события.
  - `getComment(): ?CommentMessage` — получение объекта комментария.
  - `getAdminPermissions(): ?array` — права администратора при добавлении бота.
  - Предикаты `isComment()`, `isDialog()`, `isBotLifecycle()`.
- **Callbacks**:
  - `Callbacks::answerCallback()`: добавлен параметр `?bool $disableLinkPreview = null` для отключения превью ссылок.
- **InputValidator**:
  - Добавлены валидаторы: `validateMessageId()`, `validateCommentId()`, `validateCommentCount()`.
- **Документация**:
  - Создано подробное руководство `docs/08-comments.md` (и `docs/07-comments.md`).
  - Актуализированы `docs/03-webhooks-and-polling.md`, `docs/06-advanced-features.md`, `README.md`.

### Устарело (Deprecated)
- `Members::addMembers()`: метод помечен как `@deprecated` (с вызовом `E_USER_DEPRECATED`), так как соответствующий эндпоинт `POST /chats/{chatId}/members` закрыт/ограничен в официальном MAX Bot API.

## [2.2.0] — 2026-08-12

### Добавлено
- `Bot::patchCommands(array $commands)`: добавлен метод взаимодействия с `PATCH /me/commands` для обновления и настройки команд бота.

### Изменено
- `CurlHttpClient`: базовый домен по умолчанию обновлён на `https://platform-api2.max.ru` согласно актуальным спецификациям MAX Bot API.
- `docs` & `tests`: актуализирована документация и тесты под домен `platform-api2.max.ru` и требование доверенных сертификатов Минцифры.

## [2.1.0] — 2026-05-13

### Добавлено
- `ContactValidator`: новая утилита для проверки подлинности контактов (request_contact) через механизм HMAC-SHA256 (`hash_equals`).

### Изменено
- `KeyboardBuilder`: добавлено строгое ограничение лимита кнопок (до 3 в ряду), если в ряду используются кнопки типов `link`, `open_app`, `request_geo_location` или `request_contact` (согласно новым лимитам API).
- `docs`: актуализирована документация. Добавлены новые типы кнопок (`open_app`, `message`, `clipboard`), описано правило лимита в 3 кнопки для ряда специфичных типов, добавлен пример верификации контактов через `ContactValidator`.

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
