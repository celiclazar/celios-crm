<?php

namespace Modules\Crm\Services;

use Modules\Crm\Contracts\CrmDriverInterface;
use Modules\Crm\DTO\CrmActivityData;
use Modules\Crm\DTO\CrmContactData;
use Modules\Crm\DTO\CrmSyncResponse;
use Modules\Crm\Models\Contact;

class CrmService
{
    public function __construct(
        protected CrmManager $manager
    ) {}

    public function driver(?string $driver = null): CrmDriverInterface
    {
        return $this->manager->driver($driver);
    }

    public function manager(): CrmManager
    {
        return $this->manager;
    }

    /**
     * Create or update a contact and broadcast to sync drivers.
     *
     * @return array<string, CrmSyncResponse>
     */
    public function upsertContact(CrmContactData|array $data): array
    {
        $contactData = is_array($data) ? CrmContactData::fromArray($data) : $data;

        return $this->manager->syncContact($contactData);
    }

    /**
     * Log an interaction or timeline activity for a contact.
     *
     * @return array<string, CrmSyncResponse>
     */
    public function logActivity(string|int $contactIdentifier, CrmActivityData|array $activity): array
    {
        $activityData = is_array($activity) ? CrmActivityData::fromArray($activity) : $activity;

        return $this->manager->logActivity((string) $contactIdentifier, $activityData);
    }

    /**
     * Convert an external form submission or raw payload into a CRM contact.
     */
    public function createFromFormSubmission(int $submissionId, array $data): Contact
    {
        $contactData = CrmContactData::fromArray(array_merge($data, [
            'form_submission_id' => $submissionId,
            'source' => 'form_submission',
        ]));

        $this->upsertContact($contactData);

        return Contact::where('form_submission_id', $submissionId)->firstOrFail();
    }
}
