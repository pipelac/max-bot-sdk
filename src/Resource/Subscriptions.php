<?php

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\Subscription;
use MaxBotSdk\DTO\UpdatesResult;
use MaxBotSdk\Utils\InputValidator;

/**
 * Ресурс: подписки (webhook/long-polling) MAX Bot API.
 *
 * @since 1.0.0
 */
final class Subscriptions extends ResourceAbstract
{
    /**
     * Подписаться на обновления (webhook).
     *
     * @param string      $url         URL webhook (HTTPS обязателен).
     * @param array|null  $updateTypes Типы обновлений (null = все).
     * @param string|null $version     Версия API.
     * @param string|null $secret      Секретный ключ для верификации webhook-запросов.
     * @return Subscription
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function subscribe($url, array $updateTypes = null, $version = null, $secret = null)
    {
        InputValidator::validateWebhookUrl($url);
        $payload = ['url' => $url];
        if ($updateTypes !== null) {
            $payload['update_types'] = $updateTypes;
        }
        if ($version !== null) {
            $payload['version'] = $version;
        }
        if ($secret !== null) {
            $payload['secret_key'] = $secret;
        }

        $data = $this->post('/subscriptions', $payload);
        return Subscription::fromArray($data);
    }

    /**
     * Получить текущие подписки.
     *
     * @return Subscription[]
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function getSubscriptions()
    {
        $data = $this->get('/subscriptions');
        $subscriptions = [];

        $rawSubs = isset($data['subscriptions']) && is_array($data['subscriptions'])
            ? $data['subscriptions']
            : [];

        foreach ($rawSubs as $raw) {
            if (is_array($raw)) {
                $subscriptions[] = Subscription::fromArray($raw);
            }
        }

        return $subscriptions;
    }

    /**
     * Отписаться от обновлений (удалить webhook).
     *
     * @param string $url URL webhook для удаления.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function unsubscribe($url)
    {
        InputValidator::validateWebhookUrl($url);
        $data = $this->delete('/subscriptions', ['url' => $url]);
        return ActionResult::fromArray($data);
    }

    /**
     * Получить обновления (long-polling).
     *
     * @param int|null $limit   Макс. количество обновлений.
     * @param int|null $timeout Таймаут в секундах.
     * @param int|null $marker  Маркер для получения следующей порции.
     * @param array|null $types Типы обновлений.
     * @return UpdatesResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function getUpdates($limit = null, $timeout = null, $marker = null, array $types = null)
    {
        $query = [];
        if ($limit !== null) {
            $query['limit'] = (int) $limit;
        }
        if ($timeout !== null) {
            $query['timeout'] = (int) $timeout;
        }
        if ($marker !== null) {
            $query['marker'] = (int) $marker;
        }
        if ($types !== null) {
            $query['types'] = implode(',', $types);
        }

        $data = $this->get('/updates', $query);
        return UpdatesResult::fromArray($data);
    }
}
