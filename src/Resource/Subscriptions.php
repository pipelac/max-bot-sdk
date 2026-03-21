<?php

namespace App\Component\Max\Resource;

use App\Component\Max\DTO\ActionResult;
use App\Component\Max\DTO\Subscription;
use App\Component\Max\DTO\Update;
use App\Component\Max\DTO\UpdatesResult;
use App\Component\Max\Utils\InputValidator;

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
     * @throws \App\Component\Max\Exception\MaxApiException
     * @throws \App\Component\Max\Exception\MaxValidationException
     */
    public function subscribe($url, array $updateTypes = null, $version = null, $secret = null)
    {
        InputValidator::validateWebhookUrl($url);
        $payload = array('url' => $url);
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
     * @throws \App\Component\Max\Exception\MaxApiException
     */
    public function getSubscriptions()
    {
        $data = $this->get('/subscriptions');
        $subscriptions = array();

        $rawSubs = isset($data['subscriptions']) && is_array($data['subscriptions'])
            ? $data['subscriptions']
            : array();

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
     * @throws \App\Component\Max\Exception\MaxApiException
     * @throws \App\Component\Max\Exception\MaxValidationException
     */
    public function unsubscribe($url)
    {
        InputValidator::validateWebhookUrl($url);
        $data = $this->delete('/subscriptions', array('url' => $url));
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
     * @throws \App\Component\Max\Exception\MaxApiException
     */
    public function getUpdates($limit = null, $timeout = null, $marker = null, array $types = null)
    {
        $query = array();
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
