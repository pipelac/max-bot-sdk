# Загрузка файлов

## Типы загрузки

- `image` — изображения
- `video` — видео
- `audio` — аудиозаписи
- `file` — произвольные файлы

## Простая загрузка (рекомендуется)

Метод `uploadFile()` выполняет оба шага автоматически:

```php
// Загрузить изображение и получить token
$token = $client->uploads()->uploadFile('image', '/path/to/photo.jpg');

// Отправить с вложением
$client->messages()->sendMessage([
    'text'        => 'Смотрите фото!',
    'attachments' => [
        [
            'type'    => 'image',
            'payload' => ['token' => $token],
        ],
    ],
], null, $chatId);
```

## Пошаговая загрузка

### Шаг 1: Получить URL для загрузки

```php
$result = $client->uploads()->getUploadUrl('image');
$uploadUrl = $result->getUrl();
```

### Шаг 2: Загрузить файл по URL

```php
$uploadResult = $client->uploads()->uploadFileToUrl($uploadUrl, '/path/to/photo.jpg');
$token = $uploadResult->getToken();
```

### Шаг 3: Использовать token при отправке

```php
$client->messages()->sendMessage([
    'attachments' => [
        [
            'type'    => 'image',
            'payload' => ['token' => $token],
        ],
    ],
], null, $chatId);
```

## Загрузка разных типов файлов

```php
// Видео
$token = $client->uploads()->uploadFile('video', '/path/to/video.mp4');

// Аудио
$token = $client->uploads()->uploadFile('audio', '/path/to/audio.mp3');

// Файл
$token = $client->uploads()->uploadFile('file', '/path/to/document.pdf');
```

## Информация о видео

```php
$info = $client->uploads()->getVideoInfo($videoToken);
```

## Обработка ошибок загрузки

```php
use MaxBotSdk\Exception\MaxFileException;

try {
    $token = $client->uploads()->uploadFile('image', '/path/to/photo.jpg');
} catch (MaxFileException $e) {
    // Файл не найден, ошибка загрузки, некорректный ответ
    echo 'Ошибка загрузки: ' . $e->getMessage();
}
```

## Следующие шаги

- [Обработка ошибок](05-error-handling.md)
