<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект подписки (webhook).
 *
 * @since 1.0.0
 */
final class Subscription extends AbstractDto
{
    private readonly string $url;
    private readonly ?int $time;
    /** @var list<string> */
    private readonly array $updateTypes;
    private readonly ?string $version;
    private readonly bool $success;
    private readonly ?string $message;
    private readonly ?string $code;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->url = self::getString($data, 'url');
        $this->time = self::getIntOrNull($data, 'time');
        $this->updateTypes = array_values(array_map(
            static fn(mixed $v): string => \is_scalar($v) ? (string) $v : '',
            self::getArray($data, 'update_types'),
        ));
        $this->version = self::getStringOrNull($data, 'version');
        $this->success = self::getBool($data, 'success', true);
        $this->message = self::getStringOrNull($data, 'message');
        $this->code = self::getStringOrNull($data, 'code');
    }

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    /**
     * @return list<string>
     */
    public function getUpdateTypes(): array
    {
        return $this->updateTypes;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Флаг успешности операции подписки (SimpleQueryResult.success для POST /subscriptions).
     *
     * @since 2.4.0
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Пояснительное сообщение при неуспешной подписке (SimpleQueryResult.message).
     *
     * @since 2.4.0
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Машиночитаемый код ошибки при неуспешной подписке (SimpleQueryResult.code, OpenAPI v0.0.34).
     *
     * @since 2.4.0
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    public function toArray(): array
    {
        $result = [
            'url'          => $this->url,
            'time'         => $this->time,
            'update_types' => $this->updateTypes,
            'version'      => $this->version,
        ];

        if (!$this->success || $this->message !== null || $this->code !== null) {
            $result['success'] = $this->success;
            $result['message'] = $this->message;
            $result['code'] = $this->code;
        }

        return $result;
    }
}
