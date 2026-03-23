<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\Contracts\ClientInterface;
use MaxBotSdk\Contracts\ResourceInterface;
use MaxBotSdk\Utils\InputValidator;

/**
 * Абстрактный базовый класс для ресурсов MAX Bot API.
 *
 * @since 1.0.0
 */
abstract class ResourceAbstract implements ResourceInterface
{
    public function __construct(
        protected readonly ClientInterface $client,
    ) {
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    protected function get(string $endpoint, array $query = []): array
    {
        return $this->client->get($endpoint, $query);
    }

    /**
     * @param array<string, mixed>|null $json
     * @param array<string, mixed>      $query
     * @return array<string, mixed>
     */
    protected function post(string $endpoint, ?array $json = null, array $query = []): array
    {
        return $this->client->post($endpoint, $json, $query);
    }

    /**
     * @param array<string, mixed>|null $json
     * @param array<string, mixed>      $query
     * @return array<string, mixed>
     */
    protected function put(string $endpoint, ?array $json = null, array $query = []): array
    {
        return $this->client->put($endpoint, $json, $query);
    }

    /**
     * @param array<string, mixed>|null $json
     * @param array<string, mixed>      $query
     * @return array<string, mixed>
     */
    protected function patch(string $endpoint, ?array $json = null, array $query = []): array
    {
        return $this->client->patch($endpoint, $json, $query);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    protected function delete(string $endpoint, array $query = []): array
    {
        return $this->client->delete($endpoint, $query);
    }

    protected function validateId(mixed $id, string $name = 'ID'): int
    {
        return InputValidator::validateId($id, $name);
    }

    protected function validateText(string $text): string
    {
        return InputValidator::validateText($text);
    }
}
