<?php

namespace Modules\Crm\DTO;

use DateTimeInterface;

class CrmActivityData
{
    public function __construct(
        public string $type, // note, call, meeting, email
        public string $content,
        public ?int $userId = null,
        public ?string $userName = null,
        public ?DateTimeInterface $occurredAt = null,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            type: (string) ($data['type'] ?? 'note'),
            content: (string) ($data['content'] ?? ''),
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            userName: $data['user_name'] ?? null,
            occurredAt: $data['occurred_at'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'content' => $this->content,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'occurred_at' => $this->occurredAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
