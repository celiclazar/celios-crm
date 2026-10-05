<?php

namespace Modules\Crm\DTO;

class CrmContactData
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $company = null,
        public string $stage = 'lead',
        public ?string $source = 'manual',
        public ?float $leadValue = null,
        public ?int $assignedToUserId = null,
        public ?int $formSubmissionId = null,
        public ?string $notes = null,
        public ?string $externalId = null,
        public array $customFields = [],
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            name: (string) ($data['name'] ?? ''),
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            company: $data['company'] ?? null,
            stage: (string) ($data['stage'] ?? 'lead'),
            source: $data['source'] ?? 'manual',
            leadValue: isset($data['lead_value']) ? (float) $data['lead_value'] : null,
            assignedToUserId: isset($data['assigned_to_user_id']) ? (int) $data['assigned_to_user_id'] : null,
            formSubmissionId: isset($data['form_submission_id']) ? (int) $data['form_submission_id'] : null,
            notes: $data['notes'] ?? null,
            externalId: $data['external_id'] ?? null,
            customFields: $data['custom_fields'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'stage' => $this->stage,
            'source' => $this->source,
            'lead_value' => $this->leadValue,
            'assigned_to_user_id' => $this->assignedToUserId,
            'form_submission_id' => $this->formSubmissionId,
            'notes' => $this->notes,
            'external_id' => $this->externalId,
            'custom_fields' => $this->customFields,
        ];
    }
}
