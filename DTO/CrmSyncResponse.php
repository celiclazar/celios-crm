<?php

namespace Modules\Crm\DTO;

class CrmSyncResponse
{
    public function __construct(
        public bool $success,
        public ?string $externalId = null,
        public ?string $message = null,
        public array $rawPayload = [],
    ) {}

    public static function successful(?string $externalId = null, ?string $message = 'Synced successfully'): static
    {
        return new static(
            success: true,
            externalId: $externalId,
            message: $message,
        );
    }

    public static function failed(string $message, array $rawPayload = []): static
    {
        return new static(
            success: false,
            externalId: null,
            message: $message,
            rawPayload: $rawPayload,
        );
    }
}
