<?php

declare(strict_types=1);

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
     * @param list<string>|null $updateTypes
     */
    public function subscribe(
        string $url,
        ?array $updateTypes = null,
        ?string $version = null,
        ?string $secret = null,
    ): Subscription {
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
     * @return list<Subscription>
     */
    public function getSubscriptions(): array
    {
        $data = $this->get('/subscriptions');
        $subscriptions = [];

        $rawSubs = isset($data['subscriptions']) && \is_array($data['subscriptions'])
            ? $data['subscriptions']
            : [];

        foreach ($rawSubs as $raw) {
            if (\is_array($raw)) {
                $subscriptions[] = Subscription::fromArray($raw);
            }
        }

        return $subscriptions;
    }

    public function unsubscribe(string $url): ActionResult
    {
        InputValidator::validateWebhookUrl($url);
        $data = $this->delete('/subscriptions', ['url' => $url]);
        return ActionResult::fromArray($data);
    }

    /**
     * @param list<string>|null $types
     */
    public function getUpdates(
        ?int $limit = null,
        ?int $timeout = null,
        ?int $marker = null,
        ?array $types = null,
    ): UpdatesResult {
        $query = [];
        if ($limit !== null) {
            $query['limit'] = $limit;
        }
        if ($timeout !== null) {
            $query['timeout'] = $timeout;
        }
        if ($marker !== null) {
            $query['marker'] = $marker;
        }
        if ($types !== null) {
            $query['types'] = implode(',', $types);
        }

        $data = $this->get('/updates', $query);
        return UpdatesResult::fromArray($data);
    }
}
