# MAX Bot API SDK

[![PHP](https://img.shields.io/badge/PHP-5.6%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.0-orange.svg)](https://github.com/pipelac/max-bot-sdk/releases/tag/v1.0.0)

PHP SDK для создания ботов в мессенджере **MAX** через официальный [MAX Bot API](https://dev.max.ru/docs-api).

**Версия:** 1.0.0 | **PHP:** ≥ 5.6 | **Лицензия:** MIT

---

## О мессенджере MAX

**[MAX](https://max.ru)** — российский мессенджер и цифровая платформа, разработанный компанией **VK**. Позиционируется как национальный мессенджер России и входит в список программ, обязательных для предустановки на электронные устройства на территории РФ.

**Возможности MAX:**
- 💬 Личные и групповые чаты
- 📞 Голосовые и видеозвонки
- 📢 Каналы и сообщества
- 🤖 Платформа ботов (Bot API)
- 🔐 Шифрование переписок, хранение данных на территории России
- 🏛️ Интеграция с государственными сервисами

**Официальные ресурсы:**

- 🌐 [max.ru](https://max.ru) — официальный сайт
- 📖 [dev.max.ru/docs-api](https://dev.max.ru/docs-api) — документация Bot API
- 📱 [Google Play](https://play.google.com/store/apps/details?id=ru.ok.max) — Android
- 🍎 [App Store](https://apps.apple.com/ru/app/max/id6504507395) — iOS
- 🖥️ [max.ru](https://max.ru) — Desktop (Windows / macOS)

---

## Что умеет этот SDK

- 🏗️ **Модульная архитектура** — 7 ресурсов (Bot, Chats, Messages, Members, Subscriptions, Uploads, Callbacks)
- 📦 **Типизированные DTO** — `User`, `Chat`, `Message`, `Update`, `PaginatedResult` и др. вместо сырых массивов
- 📄 **Пагинация** — маркерный обход списков с `PaginatedResult` (`hasMore()`, `getMarker()`)
- 🔄 **Автоматический retry** — exponential backoff для 429/5xx ошибок с настраиваемым числом попыток
- 🚦 **Rate Limiting** — контроль скорости запросов (1–100 req/sec)
- ⌨️ **Keyboard Builder** — построение inline-клавиатур с валидацией лимитов MAX API (210 кнопок, 30 рядов, 7 в ряду)
- 📎 **Загрузка файлов** — image, video, audio, file — автоматический и пошаговый режим
- 🔐 **Webhook + Long Polling** — два режима получения обновлений, парсинг и проверка подлинности запросов
- ⚙️ **Гибкая конфигурация** — из кода, INI-файла, ENV-переменных или через ConfigBuilder
- 🔌 **Инъекция зависимостей** — подключайте свой HTTP-клиент (Guzzle и др.) и любой PSR-3 логгер
- 🛡️ **Безопасность** — маскирование токенов в логах, `InputValidator`, timing-safe сравнение секретов
- ✅ **Качество кода** — PHPUnit-тесты + PHPStan level 6

## Требования

- PHP ≥ 5.6
- расширения: `curl`, `json`, `mbstring`
- доступ до `platform-api.max.ru` (HTTPS)

## Установка

```bash
composer require app/max-bot-sdk
```

Или добавьте в `composer.json`:

```json
{
    "require": {
        "app/max-bot-sdk": "^1.0"
    }
}
```

## Быстрый старт

```php
<?php
require_once 'vendor/autoload.php';

use App\Component\Max\ClientFactory;

// Создание клиента (минимальный вариант)
$client = ClientFactory::create('ВАШ_ТОКЕН');

// Информация о боте
$me = $client->bot()->getMe();
echo $me->getName(); // 'MyBot'
echo $me->getUserId(); // 123456

// Отправка сообщения в чат
$msg = $client->messages()->sendMessage(
    array('text' => 'Привет, мир!'),
    null,    // notify (null = по умолчанию)
    12345    // chatId
);
echo $msg->getMessageId();
```

## Конфигурация

SDK поддерживает несколько способов конфигурации — от простого создания через код до 12-Factor-совместимых ENV-переменных.

### Из кода (самый простой)

```php
use App\Component\Max\Config;

// Config иммутабелен — все параметры задаются при создании:
$config = new Config('YOUR_BOT_TOKEN', 60, 5); // token, timeout, retries
```

### Через ConfigBuilder (fluent API)

```php
use App\Component\Max\ConfigBuilder;
use App\Component\Max\ClientFactory;

$config = ConfigBuilder::create('YOUR_BOT_TOKEN')
    ->withTimeout(60)
    ->withRetries(5)
    ->withRateLimit(30)
    ->withVerifySsl(true)
    ->withLogRequests(true)
    ->withAppName('МойБот')
    ->build();

$client = ClientFactory::createFromConfig($config);
```

### Из ENV-переменных (12-Factor App)

```php
use App\Component\Max\ClientFactory;

// Читает: MAX_BOT_TOKEN, MAX_BOT_TIMEOUT, MAX_BOT_RETRIES, ...
$client = ClientFactory::createFromEnvironment();
```

**Поддерживаемые переменные окружения:**

- `MAX_BOT_TOKEN` — токен бота (**обязательно**)
- `MAX_BOT_TIMEOUT` — таймаут HTTP-запросов в секундах (по умолчанию: `30`)
- `MAX_BOT_RETRIES` — число повторных попыток (по умолчанию: `3`)
- `MAX_BOT_RATE_LIMIT` — лимит запросов/сек (по умолчанию: `30`)
- `MAX_BOT_VERIFY_SSL` — проверка SSL (по умолчанию: `true`)

### Из INI-файла

```php
use App\Component\Max\ClientFactory;

// Автоматически ищет cfg/config.ini:
$client = ClientFactory::createFromIni();

// Или с указанием пути:
$client = ClientFactory::createFromIni('/path/to/config.ini');
```

Пример INI-файла (см. `cfg/config.ini.example`):

```ini
[max]
token = "YOUR_BOT_TOKEN_HERE"
timeout = 30
retries = 3
rate_limit = 30
verify_ssl = true
```

## Работа с ресурсами

### Bot — информация о боте

```php
// Получить информацию о текущем боте
$me = $client->bot()->getMe();
echo $me->getName();
echo $me->getUsername();
echo $me->getUserId();
```

### Messages — отправка и получение сообщений

```php
// Отправить текстовое сообщение в чат
$msg = $client->messages()->sendMessage(
    array('text' => 'Привет!'),
    null,     // notify
    $chatId   // ID чата
);

// Быстрая отправка текста (shortcut)
$msg = $client->messages()->sendText('Привет!', $chatId);

// Отправить с inline-клавиатурой
use App\Component\Max\Utils\KeyboardBuilder;

$keyboard = KeyboardBuilder::build(array(
    // Ряд 1
    array(
        array('type' => 'callback', 'text' => 'Кнопка 1', 'payload' => 'callback_1'),
        array('type' => 'callback', 'text' => 'Кнопка 2', 'payload' => 'callback_2'),
    ),
    // Ряд 2
    array(
        array('type' => 'link', 'text' => '🔗 Ссылка', 'url' => 'https://example.com'),
    ),
));

$msg = $client->messages()->sendMessage(
    array(
        'text'        => 'Выберите действие:',
        'attachments' => array($keyboard),
    ),
    null,
    $chatId
);

// Или shortcut с клавиатурой
$msg = $client->messages()->sendTextWithKeyboard('Выберите:', $chatId, array(
    array(
        array('type' => 'callback', 'text' => '✅ Да', 'payload' => 'yes'),
        array('type' => 'callback', 'text' => '❌ Нет', 'payload' => 'no'),
    ),
));

// Получить сообщение по ID
$msg = $client->messages()->getMessage($messageId);
echo $msg->getText();
echo $msg->getSender()->getName();

// Получить список сообщений (с пагинацией)
$result = $client->messages()->getMessages($chatId, 50);
foreach ($result->getItems() as $message) {
    echo $message->getText() . PHP_EOL;
}

// Редактировать сообщение
$client->messages()->editMessage($messageId, array('text' => 'Обновлённый текст'));

// Удалить сообщение
$client->messages()->deleteMessage($messageId);
```

### Chats — работа с чатами

```php
// Получить список чатов (PaginatedResult<Chat>)
$result = $client->chats()->getChats(50);
foreach ($result->getItems() as $chat) {
    echo $chat->getTitle() . ' (ID: ' . $chat->getChatId() . ')' . PHP_EOL;
}

// Навигация по страницам
if ($result->hasMore()) {
    $nextPage = $client->chats()->getChats(50, $result->getMarker());
}

// Получить конкретный чат
$chat = $client->chats()->getChat($chatId);
echo $chat->getTitle();
echo $chat->getType();         // 'dialog', 'chat', 'channel'
echo $chat->getMembersCount();

// Редактировать чат
$client->chats()->editChat($chatId, array(
    'title' => 'Новое название',
    'description' => 'Новое описание'
));

// Закрепить сообщение
$client->chats()->pinMessage($chatId, $messageId);

// Открепить сообщение
$client->chats()->unpinMessage($chatId);
```

### Members — участники чатов

```php
// Получить участников чата (PaginatedResult<ChatMember>)
$result = $client->members()->getMembers($chatId, 100);
foreach ($result->getItems() as $member) {
    echo $member->getUserId() . ': ' . $member->getName() . PHP_EOL;
}

// Добавить участников
$client->members()->addMembers($chatId, array($userId1, $userId2));

// Удалить участника
$client->members()->removeMember($chatId, $userId);

// Получить список админов
$admins = $client->members()->getAdmins($chatId);

// Назначить админа
$client->members()->addAdmin($chatId, $userId);
```

### Subscriptions — подписки на обновления

```php
// Подписаться на обновления (webhook)
$client->subscriptions()->subscribe(
    'https://your-server.com/webhook',
    array('message_created', 'message_callback'), // типы событий
    null,                  // версия API
    'your_webhook_secret'  // секрет для верификации
);

// Получить текущие подписки
$subscriptions = $client->subscriptions()->getSubscriptions();
foreach ($subscriptions as $sub) {
    echo $sub->getUrl() . PHP_EOL;
}

// Отписаться
$client->subscriptions()->unsubscribe('https://your-server.com/webhook');

// Long Polling (альтернатива webhook)
$result = $client->subscriptions()->getUpdates(100, $timeout, $marker);
foreach ($result->getUpdates() as $update) {
    echo $update->getUpdateType() . ': ' . $update->getTimestamp() . PHP_EOL;
}
$marker = $result->getMarker();
```

### Uploads — загрузка файлов

```php
// Загрузить файл одним вызовом (шаги 1+2 автоматически)
$token = $client->uploads()->uploadFile('image', '/path/to/file.jpg');

// Или пошагово:
$uploadResult = $client->uploads()->getUploadUrl('image');
$url = $uploadResult->getUrl();
$fileResult = $client->uploads()->uploadFileToUrl($url, '/path/to/file.jpg');
$token = $fileResult->getToken();

// Использовать token в сообщении
$client->messages()->sendMessage(array(
    'text'        => 'Смотрите фото!',
    'attachments' => array(
        array('type' => 'image', 'payload' => array('token' => $token)),
    ),
), null, $chatId);

// Получить информацию о видео
$videoInfo = $client->uploads()->getVideoInfo($videoToken);
```

### Callbacks — обработка callback-запросов из inline-клавиатур

```php
// Ответить на callback с обновлением сообщения и уведомлением
$client->callbacks()->answerCallback(
    $callbackId,
    array('text' => 'Обновлённое сообщение'),
    'Принято!'  // уведомление пользователю
);

// Только уведомление (без обновления сообщения)
$client->callbacks()->answerCallback($callbackId, null, 'Принято!');
```

## Webhook-обработка

```php
use App\Component\Max\Utils\WebhookHandler;

$handler = new WebhookHandler();

// Верификация секрета
$secret = isset($_SERVER['HTTP_X_MAX_BOT_API_SECRET'])
    ? $_SERVER['HTTP_X_MAX_BOT_API_SECRET']
    : '';

if (!$handler->verifySecret('your_secret_key', $secret)) {
    http_response_code(403);
    exit('Forbidden');
}

// Парсинг обновления из тела запроса
$update = $handler->parseUpdate(file_get_contents('php://input'));

if ($update !== null) {
    switch ($update->getUpdateType()) {
        case 'message_created':
            $message = $update->getMessage();
            $text = $message->getText();
            $chatId = $message->getChatId();

            // Эхо-бот
            $client->messages()->sendMessage(
                array('text' => 'Вы написали: ' . $text),
                null,
                $chatId
            );
            break;

        case 'message_callback':
            $callbackId = $update->getCallbackId();
            $payload = $update->getCallbackPayload();

            $client->callbacks()->answerCallback(
                $callbackId,
                null,
                'Обработано: ' . $payload
            );
            break;
    }
}

http_response_code(200);
```

## DTO (Data Transfer Objects)

Все API-методы возвращают типизированные объекты вместо сырых массивов:

- `bot()->getMe()` → **User** — информация о боте
- `chats()->getChats()` → **PaginatedResult\<Chat\>** — список чатов
- `chats()->getChat($id)` → **Chat** — конкретный чат
- `messages()->sendMessage()` → **Message** — отправленное сообщение
- `messages()->getMessage($id)` → **Message** — конкретное сообщение
- `messages()->getMessages()` → **PaginatedResult\<Message\>** — список сообщений
- `members()->getMembers($id)` → **PaginatedResult\<ChatMember\>** — участники чата
- `subscriptions()->getSubscriptions()` → **Subscription[]** — подписки
- `subscriptions()->getUpdates()` → **UpdatesResult** — обновления long polling
- `uploads()->getUploadUrl()` → **UploadResult** — URL для загрузки
- `uploads()->uploadFile()` → **string** — token файла
- `uploads()->getVideoInfo($t)` → **VideoInfo** — информация о видео

Каждый DTO имеет:
- `fromArray(array $data)` — создание из данных API
- `toArray()` — обратная сериализация
- Типизированные геттеры (`getName()`, `getChatId()`, и т.д.)

## Обработка ошибок

SDK использует иерархию исключений для точной обработки различных типов ошибок:

```php
use App\Component\Max\Exception\MaxApiException;
use App\Component\Max\Exception\MaxConnectionException;
use App\Component\Max\Exception\MaxValidationException;
use App\Component\Max\Exception\MaxConfigException;

try {
    $msg = $client->messages()->sendMessage($body, null, $chatId);
} catch (MaxValidationException $e) {
    // Ошибка валидации входных данных (400)
    echo 'Ошибка валидации: ' . $e->getMessage();
} catch (MaxApiException $e) {
    // Ошибка API (401, 403, 404, 429, 5xx)
    echo 'Ошибка API [' . $e->getCode() . ']: ' . $e->getMessage();
} catch (MaxConnectionException $e) {
    // Ошибка сети (таймаут, DNS и т.д.)
    echo 'Ошибка соединения: ' . $e->getMessage();
} catch (MaxConfigException $e) {
    // Ошибка конфигурации
    echo 'Ошибка конфига: ' . $e->getMessage();
}
```

## Inline-клавиатуры

```php
use App\Component\Max\Utils\KeyboardBuilder;

// Простая клавиатура
$keyboard = KeyboardBuilder::build(array(
    array(
        array('type' => 'callback', 'text' => 'Да', 'payload' => 'yes'),
        array('type' => 'callback', 'text' => 'Нет', 'payload' => 'no'),
    ),
));

// Многострочная с ссылками
$keyboard = KeyboardBuilder::build(array(
    array(
        array('type' => 'callback', 'text' => '✅ Подтвердить', 'payload' => 'confirm'),
        array('type' => 'callback', 'text' => '❌ Отмена', 'payload' => 'cancel'),
    ),
    array(
        array('type' => 'link', 'text' => '📖 Документация', 'url' => 'https://dev.max.ru/docs-api'),
    ),
));

// Лимиты MAX API: 210 кнопок, 30 рядов, 7 кнопок в ряду
```

## Структура проекта

```
Max/
├── src/
│   ├── Client.php              # Главный клиент
│   ├── ClientFactory.php       # Фабрика клиентов
│   ├── Config.php              # Конфигурация
│   ├── ConfigBuilder.php       # Fluent-построитель конфига
│   ├── ResponseDecoder.php     # Декодер JSON-ответов
│   ├── Contracts/              # Интерфейсы
│   ├── DTO/                    # Data Transfer Objects
│   │   ├── AbstractDto.php
│   │   ├── User.php, Chat.php, Message.php, ...
│   │   ├── PaginatedResult.php
│   │   └── UpdatesResult.php
│   ├── Exception/              # Иерархия исключений
│   ├── Http/                   # HTTP-слой (CurlHttpClient, RetryHandler)
│   ├── Resource/               # 7 API-ресурсов
│   │   ├── Bot.php, Chats.php, Messages.php, Members.php
│   │   ├── Subscriptions.php, Uploads.php, Callbacks.php
│   │   └── ResourceAbstract.php
│   └── Utils/                  # Утилиты
│       ├── InputValidator.php
│       ├── KeyboardBuilder.php
│       └── WebhookHandler.php
├── tests/                      # PHPUnit тесты
├── docs/                       # Документация
├── cfg/
│   └── config.ini.example      # Пример конфигурации
├── composer.json
├── phpstan.neon
└── phpunit.xml.dist
```

## Тестирование

```bash
# Установка зависимостей
composer install

# Запуск тестов
composer test

# Статический анализ (PHPStan level 6)
composer analyse

# Проверка стиля кода
composer cs-check

# Автоисправление стиля
composer cs-fix
```

## Документация

- [Начало работы](docs/01-getting-started.md)
- [Работа с сообщениями](docs/02-working-with-messages.md)
- [Webhooks и Polling](docs/03-webhooks-and-polling.md)
- [Загрузка файлов](docs/04-file-uploads.md)
- [Обработка ошибок](docs/05-error-handling.md)
- [Расширенные возможности](docs/06-advanced-features.md)
- [Примеры использования](docs/07-examples.md)
- [CHANGELOG](CHANGELOG.md)
- [CONTRIBUTING](CONTRIBUTING.md)

## Лицензия

MIT License. Подробности см. в файле [LICENSE](LICENSE).
