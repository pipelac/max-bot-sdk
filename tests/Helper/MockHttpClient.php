<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Helper;

use MaxBotSdk\Contracts\HttpClientInterface;

/**
 * Мок HTTP-клиента для тестирования MAX SDK.
 */
final class MockHttpClient implements HttpClientInterface
{
    /** @var list<array{status_code: int, body: string}> */
    private array $responses = [];

    /** @var list<array{method: string, url: string, options: array<string, mixed>}> */
    private array $requests = [];

    private int $currentResponseIndex = 0;
    private int $lastStatusCode = 200;

    public function setResponse(int $statusCode, string $body = '{}'): self
    {
        $this->responses[] = [
            'status_code' => $statusCode,
            'body'        => $body,
        ];
        return $this;
    }

    /**
     * @return list<array{method: string, url: string, options: array<string, mixed>}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * @return array{method: string, url: string, options: array<string, mixed>}|null
     */
    public function getLastRequest(): ?array
    {
        $count = \count($this->requests);
        return $count > 0 ? $this->requests[$count - 1] : null;
    }

    public function reset(): void
    {
        $this->requests = [];
        $this->responses = [];
        $this->currentResponseIndex = 0;
    }

    public function request(string $method, string $url, array $options = []): array
    {
        $this->requests[] = [
            'method'  => $method,
            'url'     => $url,
            'options' => $options,
        ];

        $response = $this->responses[$this->currentResponseIndex] ?? [
            'status_code' => 200,
            'body'        => '{}',
        ];

        if (isset($this->responses[$this->currentResponseIndex])) {
            $this->currentResponseIndex++;
        }

        $this->lastStatusCode = $response['status_code'];

        return $response;
    }

    public function getLastStatusCode(): int
    {
        return $this->lastStatusCode;
    }

    public function getBaseUrl(): string
    {
        return 'https://platform-api.max.ru';
    }
}
