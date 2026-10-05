<?php

namespace Modules\Crm\Contracts;

use Illuminate\Http\Request;
use Modules\Crm\DTO\CrmSyncResponse;

interface HandlesWebhooksInterface
{
    /**
     * Handle an incoming webhook request from the external CRM.
     */
    public function handleWebhook(Request $request): CrmSyncResponse;
}
