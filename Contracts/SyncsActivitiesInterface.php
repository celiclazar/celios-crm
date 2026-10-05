<?php

namespace Modules\Crm\Contracts;

use Modules\Crm\DTO\CrmActivityData;
use Modules\Crm\DTO\CrmSyncResponse;

interface SyncsActivitiesInterface
{
    /**
     * Log an interaction/activity (note, call, meeting, email) for a contact.
     */
    public function logActivity(string $contactIdentifier, CrmActivityData $activity): CrmSyncResponse;
}
