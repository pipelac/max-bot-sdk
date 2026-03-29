# Работа с сообщениями

## Отправка сообщений

### Текстовое сообщение

```php
// В чат
$message = $client->messages()->sendMessage(
    ['text' => 'Привет!'],
    null,     // notify
    $chatId   // ID чата
);
echo $message->getMessageId(); // ID отправленного сообщения

// Быстрая отправка текста (shortcut)
$message = $client->messages()->sendText('Привет!', $chatId);

// С указанием формата
$message = $client->messages()->sendText('**Жирный** и _курсив_', $chatId, 'markdown');
```

### Форматирование текста

```php
// Markdown
$client->messages()->sendMessage([
    'text'   => '**Жирный**, _курсив_, `код`, [ссылка](https://example.com)',
    'format' => 'markdown',
], null, $chatId);

// HTML
$client->messages()->sendMessage([
    'text'   => '<b>Жирный</b>, <i>курсив</i>, <code>код</code>',
    'format' => 'html',
], null, $chatId);
```

### Тихая отправка (без уведомления)

```php
$client->messages()->sendMessage([
    'text'   => 'Тихое сообщение',
    'notify' => false,
], null, $chatId);
```

### Ответ на сообщение (reply)

```php
$client->messages()->sendMessage([
    'text' => 'Это ответ на ваше сообщение',
    'link' => [
        'type'    => 'reply',
        'mid'     => $originalMessageId,
    ],
], null, $chatId);
```

### Пересылка сообщения (forward)

```php
$client->messages()->sendMessage([
    'text' => 'Пересланное сообщение',
    'link' => [
        'type'    => 'forward',
        'mid'     => $originalMessageId,
    ],
], null, $chatId);
```

## Inline-клавиатуры

### Callback-кнопки

```php
use MaxBotSdk\Utils\KeyboardBuilder;

$keyboard = KeyboardBuilder::build([
    [
        ['type' => 'callback', 'text' => 'Кнопка 1', 'payload' => 'btn_1'],
        ['type' => 'callback', 'text' => 'Кнопка 2', 'payload' => 'btn_2'],
    ],
]);

$client->messages()->sendMessage([
    'text'        => 'Нажмите кнопку:',
    'attachments' => [$keyboard],
], null, $chatId);

// Или shortcut:
$client->messages()->sendTextWithKeyboard('Нажмите кнопку:', $chatId, [
    [
        ['type' => 'callback', 'text' => 'Кнопка 1', 'payload' => 'btn_1'],
        ['type' => 'callback', 'text' => 'Кнопка 2', 'payload' => 'btn_2'],
    ],
]);
```

### Кнопка-ссылка

```php
$keyboard = KeyboardBuilder::build([
    [
        ['type' => 'link', 'text' => '🔗 Открыть', 'url' => 'https://example.com'],
    ],
]);
```

### Запрос контакта / геолокации

```php
$keyboard = KeyboardBuilder::build([
    [
        ['type' => 'request_contact', 'text' => '📱 Отправить контакт'],
    ],
    [
        ['type' => 'request_geo', 'text' => '📍 Отправить геолокацию'],
    ],
]);
```

### Ограничения клавиатуры

- Максимум **210 кнопок** в одном сообщении
- Максимум **30 рядов**
- Максимум **7 кнопок** в ряду

## Получение сообщений

### Одно сообщение по ID

```php
$message = $client->messages()->getMessage($messageId);
echo $message->getText();
echo $message->getSender()->getName();
echo $message->getSender()->getUsername();
```

### Список сообщений из чата

```php
$result = $client->messages()->getMessages($chatId, 20);

foreach ($result->getItems() as $msg) {
    echo $msg->getText() . "\n";
}

if ($result->hasMore()) {
    // Следующая страница — передаём маркер как параметр $from
    $next = $client->messages()->getMessages($chatId, 20, $result->getMarker());
}
```

### С фильтрацией по времени

```php
$result = $client->messages()->getMessages(
    $chatId,
    50,                      // count
    strtotime('-1 hour'),    // from
    time()                   // to
);
```

## Редактирование сообщений

> **Примечание:** Редактировать можно только сообщения, отправленные менее 24 часов назад.

```php
// Изменить текст
$client->messages()->editMessage($messageId, [
    'text' => 'Обновлённый текст',
]);

// Изменить текст и обновить клавиатуру
$newKeyboard = KeyboardBuilder::build([
    [
        ['type' => 'callback', 'text' => '✅ Готово', 'payload' => 'done'],
    ],
]);

$client->messages()->editMessage($messageId, [
    'text'        => 'Задача выполнена!',
    'attachments' => [$newKeyboard],
]);

// Удалить все вложения (пустой массив)
$client->messages()->editMessage($messageId, [
    'text'        => 'Текст без вложений',
    'attachments' => [],
]);
```

## Удаление сообщений

> **Примечание:** Удалять можно только сообщения, отправленные менее 24 часов назад.

```php
$client->messages()->deleteMessage($messageId);
```

## Обработка callback-ов

```php
// Ответ на нажатие inline-кнопки (обновление сообщения + уведомление)
$client->callbacks()->answerCallback($callbackId, [
    'text'        => 'Обновлённое сообщение',
    'attachments' => [$newKeyboard],
], 'Уведомление пользователю');

// Только уведомление (без обновления сообщения)
$client->callbacks()->answerCallback($callbackId, null, 'Принято!');

// Только обновить сообщение (без уведомления)
$client->callbacks()->answerCallback($callbackId, [
    'text' => 'Новый текст сообщения',
]);
```

## Следующие шаги

- [Webhooks и Long Polling](03-webhooks-and-polling.md)
- [Загрузка файлов](04-file-uploads.md)
- [Обработка ошибок](05-error-handling.md)
