<?php

namespace Modules\Crm\Exceptions;

class CrmDriverNotFoundException extends CrmDriverException
{
    public static function forDriver(string $driver): self
    {
        return new self("CRM driver [{$driver}] is not configured or registered.");
    }
}
