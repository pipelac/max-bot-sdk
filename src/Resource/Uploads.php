<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\UploadResult;
use MaxBotSdk\DTO\VideoInfo;
use MaxBotSdk\Enum\UploadType;
use MaxBotSdk\Exception\MaxFileException;
use MaxBotSdk\Utils\InputValidator;

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
     * @throws MaxFileException
     */
    public function uploadFileToUrl(string $url, string $filePath): UploadResult
    {
        InputValidator::validateNotEmpty($url, 'Upload URL');

        if (!\is_file($filePath) || !\is_readable($filePath)) {
            throw new MaxFileException('Файл не найден или недоступен: ' . $filePath);
        }

        $httpClient = $this->client->getHttpClient();

        try {
            $response = $httpClient->request('POST', $url, [
                'multipart' => [
                    [
                        'name'     => 'data',
                        'filename' => \basename($filePath),
                        'filepath' => $filePath,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            throw new MaxFileException(
                'Ошибка загрузки файла: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        $body = $response['body'] ?? '';
        $decoded = \json_decode($body, true);
        if (!\is_array($decoded)) {
            throw new MaxFileException('Некорректный ответ сервера при загрузке файла.');
        }

        return UploadResult::fromArray($decoded);
    }

    /**
     * Загрузить файл и получить token (шаги 1+2).
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

        $uploadResult = $this->uploadFileToUrl($url, $filePath);
        $token = $uploadResult->getToken();
        if ($token === '') {
            throw new MaxFileException('Token не получен после загрузки файла.');
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
