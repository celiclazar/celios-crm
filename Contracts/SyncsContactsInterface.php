<?php

namespace Modules\Crm\Contracts;

use Modules\Crm\DTO\CrmContactData;
use Modules\Crm\DTO\CrmSyncResponse;

interface SyncsContactsInterface
{
    /**
     * Upsert a contact into the target CRM.
     */
    public function upsertContact(CrmContactData $contact): CrmSyncResponse;

    /**
     * Delete a contact by email or external ID.
     */
    public function deleteContact(string $identifier): CrmSyncResponse;
}
