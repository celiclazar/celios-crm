<?php

namespace Modules\Crm\Contracts;

interface CrmDriverInterface
{
    /**
     * Get the unique key identifier for this driver.
     */
    public function getKey(): string;

    /**
     * Get the human-readable display name.
     */
    public function getName(): string;

    /**
     * Test connection / credentials for this driver.
     */
    public function testConnection(): bool;
}
