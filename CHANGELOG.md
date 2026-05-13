# CHANGELOG

Все значимые изменения в этом проекте документируются в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/),
проект следует [семантическому версионированию](https://semver.org/lang/ru/).

## [1.1.0] — 2026-05-13

### Добавлено
- `ContactValidator`: новая утилита для безопасной верификации контактов (тип `request_contact`) через механизм HMAC-SHA256, с защитой от тайминг-атак при помощи `hash_equals()`.

### Изменено
- `KeyboardBuilder`: обновлена логика построения inline-клавиатур. Теперь строго соблюдается лимит в 3 кнопки на ряд для типов `link`, `open_app`, `request_geo_location` и `request_contact` (для остальных типов лимит остаётся 7). При превышении выбрасывается `MaxValidationException`.
- Документация (`docs/02-working-with-messages.md`, `docs/06-advanced-features.md`): добавлены примеры работы с новыми типами кнопок (`open_app`, `message`, `clipboard`) и инструкция по верификации контактов `request_contact`.

## [1.0.1] — 2026-04-04

### Исправлено
- Корректный парсинг `Message`: теперь SDK правильно обратывает вложенную структуру данных (включая массив `body`) для поступающих через Webhook сообщений (тип `message_created`).
- Добавлен fallback в `Update::getChatId()`: если `chat_id` отсутствует на верхнем уровне массива, метод попытается извлечь его из вложенного объекта сообщения.

## [1.0.0] — 2026-03-21

Первый стабильный релиз MAX Bot API SDK.

### Архитектура

- Модульная архитектура с 7 ресурсами: Bot, Chats, Messages, Members, Subscriptions, Uploads, Callbacks
- `Client` — главный фасад с явными методами доступа к ресурсам и ленивой инициализацией
- `ClientFactory` — фабрика клиентов (из токена, INI-файла, ENV переменных)
- `Config` / `ConfigBuilder` — конфигурация с fluent-интерфейсом и заморозкой
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

Convenience-методы:

- `Messages::sendText($text, $chatId, $format)` — быстрая отправка текста
- `Messages::sendTextWithKeyboard($text, $chatId, $rows)` — отправка с inline-клавиатурой

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
- PHPStan level 6 с целевой версией PHP 5.6
- PHPUnit тесты (214 тестов, 430 assertions)
- CI/CD: GitHub Actions (PHP 7.3/7.4/8.0/8.1/8.2/8.3)
- `.php-cs-fixer.dist.php`, `.editorconfig`, `phpstan.neon`
