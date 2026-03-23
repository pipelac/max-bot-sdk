<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект чата MAX.
 *
 * @since 1.0.0
 */
final class Chat extends AbstractDto
{
    private readonly int $chatId;
    private readonly string $type;
    private readonly string $status;
    private readonly ?string $title;
    private readonly ?string $description;
    private readonly ?int $participantsCount;
    private readonly ?User $owner;
    private readonly bool $isPublic;
    private readonly ?string $link;
    /** @var array<string, mixed>|null */
    private readonly ?array $icon;
    private readonly ?int $lastEventTime;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->chatId = self::getInt($data, 'chat_id');
        $this->type = self::getString($data, 'type');
        $this->status = self::getString($data, 'status');
        $this->title = self::getStringOrNull($data, 'title');
        $this->description = self::getStringOrNull($data, 'description');
        $this->participantsCount = self::getIntOrNull($data, 'participants_count');
        $this->owner = isset($data['owner_id']) || isset($data['owner'])
            ? $this->parseOwner($data)
            : null;
        $this->isPublic = self::getBool($data, 'is_public');
        $this->link = self::getStringOrNull($data, 'link');
        $this->icon = self::getArrayOrNull($data, 'icon');
        $this->lastEventTime = self::getIntOrNull($data, 'last_event_time');
    }

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getParticipantsCount(): ?int
    {
        return $this->participantsCount;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getIcon(): ?array
    {
        return $this->icon;
    }

    public function getLastEventTime(): ?int
    {
        return $this->lastEventTime;
    }

    public function __toString(): string
    {
        return \sprintf('Chat#%d (%s)', $this->chatId, $this->title ?? $this->type);
    }

    public function toArray(): array
    {
        return [
            'chat_id'            => $this->chatId,
            'type'               => $this->type,
            'status'             => $this->status,
            'title'              => $this->title,
            'description'        => $this->description,
            'participants_count' => $this->participantsCount,
            'owner'              => $this->owner?->toArray(),
            'is_public'          => $this->isPublic,
            'link'               => $this->link,
            'icon'               => $this->icon,
            'last_event_time'    => $this->lastEventTime,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parseOwner(array $data): ?User
    {
        if (isset($data['owner']) && \is_array($data['owner'])) {
            return User::fromArray($data['owner']);
        }
        if (isset($data['owner_id'])) {
            return User::fromArray(['user_id' => $data['owner_id'], 'name' => '']);
        }
        return null;
    }
}
