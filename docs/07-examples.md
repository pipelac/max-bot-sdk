# Примеры использования MAX Bot API SDK

Полноценные сценарии для быстрого старта. Все примеры используют реальные сигнатуры методов SDK.

---

## 1. Эхо-бот (Webhook)

Минимальный бот, который отвечает на каждое сообщение тем же текстом.

```php
<?php
require_once 'vendor/autoload.php';

use MaxBotSdk\ClientFactory;
use MaxBotSdk\Utils\WebhookHandler;

// --- Инициализация ---
$client = ClientFactory::create('ВАШ_ТОКЕН');
$handler = new WebhookHandler();

// --- Верификация секрета ---
$secret = isset($_SERVER['HTTP_X_MAX_BOT_API_SECRET'])
    ? $_SERVER['HTTP_X_MAX_BOT_API_SECRET']
    : '';

if (!$handler->verifySecret('my_webhook_secret', $secret)) {
    http_response_code(403);
    exit;
}

// --- Парсинг обновления ---
$update = $handler->parseUpdate(file_get_contents('php://input'));
if ($update === null) {
    http_response_code(200);
    exit;
}

// --- Обработка ---
if ($update->getUpdateType() === 'message_created') {
    $message = $update->getMessage();
    $text = $message->getText();
    $chatId = $message->getChatId();

    if ($text !== null && $chatId !== null) {
        $client->messages()->sendMessage(
            ['text' => 'Вы написали: ' . $text],
            null,
            $chatId
        );
    }
}

http_response_code(200);
```

---

## 2. Бот с командами и inline-клавиатурой

Бот обрабатывает текстовые команды и нажатия inline-кнопок.

```php
<?php
require_once 'vendor/autoload.php';

use MaxBotSdk\ClientFactory;
use MaxBotSdk\Utils\KeyboardBuilder;
use MaxBotSdk\Utils\WebhookHandler;

$client = ClientFactory::create('ВАШ_ТОКЕН');
$handler = new WebhookHandler();

$update = $handler->parseUpdate(file_get_contents('php://input'));
if ($update === null) {
    http_response_code(200);
    exit;
}

switch ($update->getUpdateType()) {
    case 'message_created':
        handleCommand($client, $update);
        break;

    case 'message_callback':
        handleCallback($client, $update);
        break;

    case 'bot_started':
        // Приветствие нового пользователя
        $chatId = $update->getChatId();
        if ($chatId !== null) {
            $client->messages()->sendMessage(
                ['text' => '👋 Добро пожаловать! Напишите /help для списка команд.'],
                null,
                $chatId
            );
        }
        break;
}

http_response_code(200);

// --- Обработка текстовых команд ---
function handleCommand($client, $update)
{
    $message = $update->getMessage();
    if ($message === null) {
        return;
    }

    $text = $message->getText();
    $chatId = $message->getChatId();

    if ($text === null || $chatId === null) {
        return;
    }

    switch ($text) {
        case '/start':
        case '/help':
            $client->messages()->sendMessage([
                'text' => "📋 **Доступные команды:**\n\n"
                    . "/help — показать справку\n"
                    . "/menu — главное меню\n"
                    . "/info — информация о боте",
                'format' => 'markdown',
            ], null, $chatId);
            break;

        case '/menu':
            $keyboard = KeyboardBuilder::build([
                [
                    ['type' => 'callback', 'text' => '📊 Статистика', 'payload' => 'stats'],
                    ['type' => 'callback', 'text' => '⚙️ Настройки', 'payload' => 'settings'],
                ],
                [
                    ['type' => 'callback', 'text' => 'ℹ️ О боте', 'payload' => 'about'],
                ],
                [
                    ['type' => 'link', 'text' => '📖 Документация', 'url' => 'https://dev.max.ru/docs-api'],
                ],
            ]);

            $client->messages()->sendMessage([
                'text'        => '🏠 **Главное меню**',
                'format'      => 'markdown',
                'attachments' => [$keyboard],
            ], null, $chatId);
            break;

        case '/info':
            $me = $client->bot()->getMe();
            $client->messages()->sendMessage([
                'text' => '🤖 Бот: ' . $me->getName() . "\n"
                    . '👤 Username: @' . $me->getUsername() . "\n"
                    . '🆔 ID: ' . $me->getUserId(),
            ], null, $chatId);
            break;

        default:
            if (strpos($text, '/') === 0) {
                $client->messages()->sendMessage(
                    ['text' => '❓ Неизвестная команда. Напишите /help'],
                    null,
                    $chatId
                );
            }
            break;
    }
}

// --- Обработка callback-нажатий ---
function handleCallback($client, $update)
{
    $callbackId = $update->getCallbackId();
    $payload = $update->getCallbackPayload();

    if ($callbackId === null) {
        return;
    }

    switch ($payload) {
        case 'stats':
            $client->callbacks()->answerCallback(
                $callbackId,
                ['text' => '📊 Статистика пока недоступна.'],
                'Загрузка статистики...'
            );
            break;

        case 'settings':
            // Обновить сообщение на меню настроек
            $settingsKeyboard = KeyboardBuilder::build([
                [
                    ['type' => 'callback', 'text' => '🔔 Уведомления', 'payload' => 'notif_toggle'],
                    ['type' => 'callback', 'text' => '🌐 Язык', 'payload' => 'lang_select'],
                ],
                [
                    ['type' => 'callback', 'text' => '◀️ Назад', 'payload' => 'back_to_menu'],
                ],
            ]);

            $client->callbacks()->answerCallback(
                $callbackId,
                [
                    'text'        => '⚙️ **Настройки**',
                    'attachments' => [$settingsKeyboard],
                ]
            );
            break;

        case 'about':
            $client->callbacks()->answerCallback($callbackId, null, '🤖 MAX Bot SDK v1.0.0');
            break;

        default:
            $client->callbacks()->answerCallback($callbackId, null, 'Действие: ' . $payload);
            break;
    }
}
```

---

## 3. Загрузка и отправка файлов

Полный цикл: загрузка файла на сервер MAX → отправка в чат.

```php
<?php
require_once 'vendor/autoload.php';

use MaxBotSdk\ClientFactory;
use MaxBotSdk\Exception\MaxFileException;

$client = ClientFactory::create('ВАШ_ТОКЕН');
$chatId = 12345;

// --- Способ 1: Загрузка одним вызовом ---

try {
    // uploadFile() объединяет getUploadUrl() + uploadFileToUrl()
    $token = $client->uploads()->uploadFile('image', '/path/to/photo.jpg');

    $client->messages()->sendMessage([
        'text'        => '📷 Вот ваше фото!',
        'attachments' => [
            [
                'type'    => 'image',
                'payload' => ['token' => $token],
            ],
        ],
    ], null, $chatId);

} catch (MaxFileException $e) {
    echo 'Ошибка загрузки: ' . $e->getMessage();
}

// --- Способ 2: Пошаговая загрузка ---

try {
    // Шаг 1: Получить URL для загрузки
    $uploadResult = $client->uploads()->getUploadUrl('video');
    $uploadUrl = $uploadResult->getUrl();

    // Шаг 2: Загрузить файл по полученному URL
    $fileResult = $client->uploads()->uploadFileToUrl($uploadUrl, '/path/to/video.mp4');
    $videoToken = $fileResult->getToken();

    // Шаг 3: Отправить с вложением
    $client->messages()->sendMessage([
        'text'        => '🎬 Видео загружено!',
        'attachments' => [
            [
                'type'    => 'video',
                'payload' => ['token' => $videoToken],
            ],
        ],
    ], null, $chatId);

} catch (MaxFileException $e) {
    echo 'Ошибка: ' . $e->getMessage();
}

// --- Загрузка разных типов ---

// Аудио
$audioToken = $client->uploads()->uploadFile('audio', '/path/to/music.mp3');

// Документ
$docToken = $client->uploads()->uploadFile('file', '/path/to/report.pdf');

// Отправка нескольких вложений
$client->messages()->sendMessage([
    'text'        => '📎 Файлы:',
    'attachments' => [
        ['type' => 'audio', 'payload' => ['token' => $audioToken]],
        ['type' => 'file',  'payload' => ['token' => $docToken]],
    ],
], null, $chatId);
```

---

## 4. Long Polling бот

Для разработки и тестирования — без webhook.

```php
<?php
require_once 'vendor/autoload.php';

use MaxBotSdk\ClientFactory;
use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\Exception\MaxConnectionException;

$client = ClientFactory::create('ВАШ_ТОКЕН');

// Убедитесь, что нет активных webhook-подписок
// $client->subscriptions()->unsubscribe('https://...');

echo "Бот запущен (Long Polling)...\n";

$marker = null;

while (true) {
    try {
        // Получить обновления (таймаут 30 сек)
        $result = $client->subscriptions()->getUpdates(
            100,     // limit
            30,      // timeout
            $marker, // маркер
            ['message_created', 'message_callback'] // фильтр типов
        );

        foreach ($result->getUpdates() as $update) {
            processUpdate($client, $update);
        }

        // Обновить маркер для следующей итерации
        $marker = $result->getMarker();

    } catch (MaxApiException $e) {
        echo 'Ошибка API: ' . $e->getMessage() . "\n";
        sleep(5);
    } catch (MaxConnectionException $e) {
        echo 'Ошибка сети: ' . $e->getMessage() . "\n";
        sleep(10);
    }
}

function processUpdate($client, $update)
{
    echo '[' . date('H:i:s') . '] ' . $update->getUpdateType() . "\n";

    switch ($update->getUpdateType()) {
        case 'message_created':
            $message = $update->getMessage();
            if ($message === null) {
                break;
            }
            $text = $message->getText();
            $chatId = $message->getChatId();
            echo "  Текст: {$text}\n";

            if ($text !== null && $chatId !== null) {
                // Эхо-ответ
                $client->messages()->sendMessage(
                    ['text' => '🔁 ' . $text],
                    null,
                    $chatId
                );
            }
            break;

        case 'message_callback':
            $callbackId = $update->getCallbackId();
            $payload = $update->getCallbackPayload();
            echo "  Callback: {$payload}\n";

            if ($callbackId !== null) {
                $client->callbacks()->answerCallback(
                    $callbackId,
                    null,
                    'Получено: ' . $payload
                );
            }
            break;
    }
}
```

---

## 5. Рассылка по чатам с пагинацией

Перебирает все чаты бота и отправляет объявление.

```php
<?php
require_once 'vendor/autoload.php';

use MaxBotSdk\ClientFactory;
use MaxBotSdk\Exception\MaxApiException;

$client = ClientFactory::create('ВАШ_ТОКЕН');

$announcement = '📢 **Важное объявление!**' . "\n\n"
    . 'Обновление системы запланировано на завтра в 03:00 MSK.';

$sent = 0;
$failed = 0;
$marker = null;

do {
    // Получить страницу чатов
    $result = $client->chats()->getChats(50, $marker);

    foreach ($result->getItems() as $chat) {
        try {
            $client->messages()->sendMessage([
                'text'   => $announcement,
                'format' => 'markdown',
            ], null, $chat->getChatId());

            $sent++;
            echo '✅ Отправлено в: ' . $chat->getTitle() . "\n";
        } catch (MaxApiException $e) {
            $failed++;
            echo '❌ Ошибка (' . $chat->getTitle() . '): ' . $e->getMessage() . "\n";
        }

        // Пауза для соблюдения rate limit
        usleep(100000); // 100ms
    }

    $marker = $result->getMarker();
} while ($result->hasMore());

echo "\nИтого: отправлено {$sent}, ошибок {$failed}\n";
```

---

## 6. Обработка ошибок с retry и fallback

Паттерн надёжной обработки с каскадным перехватом.

```php
<?php
require_once 'vendor/autoload.php';

use MaxBotSdk\ConfigBuilder;
use MaxBotSdk\ClientFactory;
use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\Exception\MaxConnectionException;
use MaxBotSdk\Exception\MaxFileException;
use MaxBotSdk\Exception\MaxValidationException;
use MaxBotSdk\Exception\MaxException;

// Конфигурация с увеличенным retry
$config = ConfigBuilder::create('ВАШ_ТОКЕН')
    ->withTimeout(60)
    ->withRetries(5)
    ->withRateLimit(20)
    ->build();

$client = ClientFactory::createFromConfig($config);

/**
 * Отправить сообщение с обработкой всех ошибок.
 *
 * @param object $client
 * @param int    $chatId
 * @param string $text
 * @return bool Успешно ли отправлено.
 */
function sendSafely($client, $chatId, $text)
{
    try {
        $client->messages()->sendMessage(
            ['text' => $text],
            null,
            $chatId
        );
        return true;

    } catch (MaxValidationException $e) {
        // Ошибка валидации — не повторять
        error_log('Валидация: ' . $e->getMessage());
        return false;

    } catch (MaxApiException $e) {
        // Ошибка API
        $code = $e->getStatusCode();
        error_log("API [{$code}]: " . $e->getMessage());

        if ($code === 403) {
            // Бот удалён из чата — пропустить
            return false;
        }

        // 429 и 5xx уже обработаны RetryHandler автоматически
        // Если дошли сюда, все retry исчерпаны
        return false;

    } catch (MaxConnectionException $e) {
        error_log('Сеть: ' . $e->getMessage());
        return false;

    } catch (MaxException $e) {
        error_log('SDK: ' . $e->getMessage());
        return false;
    }
}

// Использование
$success = sendSafely($client, 12345, 'Привет!');
echo $success ? 'Отправлено' : 'Ошибка отправки';
```

---

## 7. Модерация чата

Управление участниками, админами, закреплёнными сообщениями.

```php
<?php
require_once 'vendor/autoload.php';

use MaxBotSdk\ClientFactory;

$client = ClientFactory::create('ВАШ_ТОКЕН');
$chatId = 12345;

// --- Получить информацию о чате ---
$chat = $client->chats()->getChat($chatId);
echo 'Чат: ' . $chat->getTitle() . "\n";
echo 'Тип: ' . $chat->getType() . "\n";
echo 'Участников: ' . $chat->getParticipantsCount() . "\n";

// --- Список участников (с пагинацией) ---
$marker = null;
do {
    $result = $client->members()->getMembers($chatId, 100, $marker);
    foreach ($result->getItems() as $member) {
        echo '  - ' . $member->getName() . ' (ID: ' . $member->getUserId() . ")\n";
    }
    $marker = $result->getMarker();
} while ($result->hasMore());

// --- Управление админами ---

// Назначить админа
$client->members()->addAdmin($chatId, $userId);

// Список админов
$admins = $client->members()->getAdmins($chatId);
foreach ($admins->getItems() as $admin) {
    echo 'Админ: ' . $admin->getName() . "\n";
}

// Снять админа
$client->members()->removeAdmin($chatId, $userId);

// --- Добавить/удалить участников ---
$client->members()->addMembers($chatId, [$userId1, $userId2]);
$client->members()->removeMember($chatId, $userId3);

// --- Закреплённые сообщения ---

// Закрепить
$client->chats()->pinMessage($chatId, $messageId);

// Получить закреплённое
$pinned = $client->chats()->getPinnedMessage($chatId);
if ($pinned !== null) {
    echo 'Закреплено: ' . $pinned->getText() . "\n";
}

// Открепить
$client->chats()->unpinMessage($chatId);

// --- Действия в чате ---

// Показать «печатает...»
$client->chats()->sendAction($chatId, 'typing_on');

// Редактировать чат
$client->chats()->editChat($chatId, [
    'title'       => '🏠 Обновлённое название',
    'description' => 'Описание обновлено через SDK',
]);

// --- Покинуть чат ---
$client->members()->leaveChat($chatId);
```

---

## Следующие шаги

- [Начало работы](01-getting-started.md) — установка и первый клиент
- [Работа с сообщениями](02-working-with-messages.md) — форматирование, reply, forward
- [Webhooks и Polling](03-webhooks-and-polling.md) — подписки и обработка
- [Загрузка файлов](04-file-uploads.md) — image, video, audio, file
- [Обработка ошибок](05-error-handling.md) — иерархия исключений
- [Расширенные возможности](06-advanced-features.md) — пагинация, retry, rate limit
