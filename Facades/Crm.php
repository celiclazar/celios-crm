<?php

namespace Modules\Crm\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Crm\Services\CrmService;

/**
 * @method static \Modules\Crm\Contracts\CrmDriverInterface driver(?string $driver = null)
 * @method static \Modules\Crm\Services\CrmManager manager()
 * @method static array upsertContact(\Modules\Crm\DTO\CrmContactData|array $data)
 * @method static array logActivity(string|int $contactIdentifier, \Modules\Crm\DTO\CrmActivityData|array $activity)
 * @method static \Modules\Crm\Models\Contact createFromFormSubmission(int $submissionId, array $data)
 *
 * @see \Modules\Crm\Services\CrmService
 */
class Crm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'crm';
    }
}
