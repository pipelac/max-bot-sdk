# Загрузка файлов

## Типы загрузки

- `image` — изображения
- `video` — видео
- `audio` — аудиозаписи
- `file` — произвольные файлы

## Простая загрузка (рекомендуется)

Метод `uploadFile()` выполняет оба шага автоматически:

```php
use MaxBotSdk\Enum\UploadType;

// Загрузить изображение и получить token
$token = $client->uploads()->uploadFile(UploadType::Image, '/path/to/photo.jpg');

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
$result = $client->uploads()->getUploadUrl(UploadType::Image);
$uploadUrl = $result->getUrl();
```

### Шаг 2: Загрузить файл по URL

> **Примечание:** В MAX API ответ сервера при загрузке может отдавать `token` во вложенных структурах (например, `{"photos": {"file_id": {"token": "..."}}}`). SDK версии v2 **автоматически парсит** любые уровни вложенности и безошибочно извлекает итоговый `token`.

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
$token = $client->uploads()->uploadFile(UploadType::Video, '/path/to/video.mp4');

// Аудио
$token = $client->uploads()->uploadFile(UploadType::Audio, '/path/to/audio.mp3');

// Файл
$token = $client->uploads()->uploadFile(UploadType::File, '/path/to/document.pdf');
```

## Информация о видео

```php
$info = $client->uploads()->getVideoInfo($videoToken);
```

## Обработка ошибок загрузки

```php
use MaxBotSdk\Exception\MaxFileException;

try {
    $token = $client->uploads()->uploadFile(UploadType::Image, '/path/to/photo.jpg');
} catch (MaxFileException $e) {
    // Файл не найден, ошибка загрузки, некорректный ответ
    echo 'Ошибка загрузки: ' . $e->getMessage();
}
```

## Следующие шаги

- [Обработка ошибок](05-error-handling.md)
