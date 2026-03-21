<?php

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\UploadResult;
use MaxBotSdk\DTO\VideoInfo;
use MaxBotSdk\Exception\MaxFileException;
use MaxBotSdk\Utils\InputValidator;

/**
 * Ресурс: загрузка файлов MAX Bot API.
 *
 * Процесс загрузки:
 * 1. Получение URL для загрузки (getUploadUrl)
 * 2. Загрузка файла на полученный URL (uploadFileToUrl)
 * 3. Использование токена в attachment сообщения
 *
 * Shortcut: uploadFile() объединяет шаги 1+2.
 *
 * @since 1.0.0
 */
final class Uploads extends ResourceAbstract
{
    /**
     * Получить URL для загрузки файла.
     *
     * @param string $type Тип файла: image, video, audio, file.
     * @return UploadResult Содержит URL для загрузки.
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function getUploadUrl($type)
    {
        InputValidator::validateUploadType($type);
        $data = $this->post('/uploads', null, ['type' => $type]);
        return UploadResult::fromArray($data);
    }

    /**
     * Загрузить файл по полученному URL.
     *
     * @param string $url      URL для загрузки (из getUploadUrl).
     * @param string $filePath Путь к локальному файлу.
     * @return UploadResult Содержит токен для attachment.
     * @throws MaxFileException
     */
    public function uploadFileToUrl($url, $filePath)
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
        } catch (\Exception $e) {
            throw new MaxFileException(
                'Ошибка загрузки файла: ' . $e->getMessage(),
                0,
                $e
            );
        }

        $body = isset($response['body']) ? $response['body'] : '';
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new MaxFileException('Некорректный ответ сервера при загрузке файла.');
        }

        return UploadResult::fromArray($decoded);
    }

    /**
     * Загрузить файл и получить token (шаги 1+2).
     *
     * @param string $type     Тип файла.
     * @param string $filePath Путь к файлу.
     * @return string Token для attachment.
     * @throws MaxFileException
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function uploadFile($type, $filePath)
    {
        $urlResult = $this->getUploadUrl($type);
        $url = $urlResult->getUrl();
        if (empty($url)) {
            throw new MaxFileException('Не удалось получить URL для загрузки.');
        }

        $uploadResult = $this->uploadFileToUrl($url, $filePath);
        $token = $uploadResult->getToken();
        if (empty($token)) {
            throw new MaxFileException('Token не получен после загрузки файла.');
        }

        return $token;
    }

    /**
     * Получить информацию о видео.
     *
     * @param string $videoToken Токен видео.
     * @return VideoInfo
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function getVideoInfo($videoToken)
    {
        InputValidator::validateNotEmpty($videoToken, 'Video Token');
        $data = $this->get('/videos/' . $videoToken);
        return VideoInfo::fromArray($data);
    }
}
