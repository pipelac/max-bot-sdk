<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\UploadResult;
use MaxBotSdk\DTO\VideoInfo;
use MaxBotSdk\Enum\UploadType;
use MaxBotSdk\Exception\MaxFileException;
use MaxBotSdk\Utils\InputValidator;
use Throwable;

/**
 * Ресурс: загрузка файлов MAX Bot API.
 *
 * @since 1.0.0
 */
final class Uploads extends ResourceAbstract
{
    /**
     * Получить URL для загрузки файла.
     */
    public function getUploadUrl(UploadType $type): UploadResult
    {
        $data = $this->post('/uploads', null, ['type' => $type->value]);
        return UploadResult::fromArray($data);
    }

    /**
     * Загрузить файл по полученному URL.
     *
     * @param string|null $fallbackToken Предварительный токен из ответа POST /uploads (для video/audio, когда CDN возвращает не-JSON).
     * @throws MaxFileException
     */
    public function uploadFileToUrl(string $url, string $filePath, ?string $fallbackToken = null): UploadResult
    {
        InputValidator::validateNotEmpty($url, 'Upload URL');

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new MaxFileException('Файл не найден или недоступен: ' . $filePath);
        }

        $httpClient = $this->client->getHttpClient();

        try {
            $response = $httpClient->request('POST', $url, [
                'multipart' => [
                    [
                        'name'     => 'data',
                        'filename' => basename($filePath),
                        'filepath' => $filePath,
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            throw new MaxFileException(
                'Ошибка загрузки файла: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        $statusCode = $response['status_code'] ?? 200;
        $body = $response['body'] ?? '';
        $decoded = json_decode($body, true);

        if (!\is_array($decoded)) {
            if ($statusCode >= 200 && $statusCode < 300 && $fallbackToken !== null && $fallbackToken !== '') {
                return UploadResult::fromToken($fallbackToken);
            }
            throw new MaxFileException('Некорректный ответ сервера при загрузке файла: ' . $body);
        }

        $token = isset($decoded['token']) && \is_string($decoded['token']) ? $decoded['token'] : '';
        if ($token === '') {
            // MAX API often nests tokens: {"photos": {"file_id": {"token": "..."}}}
            foreach ($decoded as $group) {
                if (\is_array($group)) {
                    foreach ($group as $item) {
                        if (\is_array($item) && isset($item['token']) && \is_string($item['token'])) {
                            $token = $item['token'];
                            break 2;
                        }
                    }
                }
            }
        }

        if ($token === '' && $fallbackToken !== null && $fallbackToken !== '') {
            $token = $fallbackToken;
        }

        $result = UploadResult::fromToken($token);
        if ($result->getToken() === '') {
            throw new MaxFileException('Token не получен после загрузки файла. Сырой ответ: ' . $body);
        }

        return $result;
    }

    /**
     * Загрузить файл и получить token (шаги 1+2).
     *
     * Поддерживает возврат предварительно выданного `token` из `POST /uploads`
     * для `UploadType::Video` и `UploadType::Audio` (OpenAPI `UploadEndpoint`).
     *
     * @throws MaxFileException
     */
    public function uploadFile(UploadType $type, string $filePath): string
    {
        $urlResult = $this->getUploadUrl($type);
        $url = $urlResult->getUrl();
        if ($url === '') {
            throw new MaxFileException('Не удалось получить URL для загрузки.');
        }

        $preToken = $urlResult->getToken();
        $uploadResult = $this->uploadFileToUrl($url, $filePath, $preToken !== '' ? $preToken : null);
        $token = $uploadResult->getToken();
        if ($token === '') {
            $responseArray = $uploadResult->toArray();
            throw new MaxFileException('Token не получен после загрузки файла. Ответил так: ' . json_encode($responseArray));
        }

        return $token;
    }

    public function getVideoInfo(string $videoToken): VideoInfo
    {
        InputValidator::validateNotEmpty($videoToken, 'Video Token');
        $data = $this->get('/videos/' . $videoToken);
        return VideoInfo::fromArray($data);
    }
}
