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

    public static function successful(?string $externalId = null, ?string $message = 'Synced successfully'): self
    {
        return new self(
            success: true,
            externalId: $externalId,
            message: $message,
        );
    }

    public static function failed(string $message, array $rawPayload = []): self
    {
        return new self(
            success: false,
            externalId: null,
            message: $message,
            rawPayload: $rawPayload,
        );
    }
}
