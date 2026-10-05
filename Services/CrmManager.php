<?php

namespace Modules\Crm\Services;

use Closure;
use Illuminate\Support\Manager;
use Modules\Crm\Contracts\CrmDriverInterface;
use Modules\Crm\Contracts\SyncsActivitiesInterface;
use Modules\Crm\Contracts\SyncsContactsInterface;
use Modules\Crm\Drivers\HubspotDriver;
use Modules\Crm\Drivers\LocalDatabaseDriver;
use Modules\Crm\Drivers\WebhookDriver;
use Modules\Crm\DTO\CrmActivityData;
use Modules\Crm\DTO\CrmContactData;
use Modules\Crm\DTO\CrmSyncResponse;
use Modules\Crm\Exceptions\CrmDriverNotFoundException;

class CrmManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('crm.default', 'local');
    }

    /**
     * Get a driver instance by name.
     */
    public function driver($driver = null): CrmDriverInterface
    {
        $driver = $driver ?: $this->getDefaultDriver();

        return parent::driver($driver);
    }

    /**
     * Create local database driver.
     */
    public function createLocalDriver(): CrmDriverInterface
    {
        $config = $this->config->get('crm.drivers.local', ['driver' => 'local']);

        return new LocalDatabaseDriver($config);
    }

    /**
     * Create webhook driver.
     */
    public function createWebhookDriver(): CrmDriverInterface
    {
        $config = $this->config->get('crm.drivers.webhook', ['driver' => 'webhook']);

        return new WebhookDriver($config);
    }

    /**
     * Create HubSpot driver.
     */
    public function createHubspotDriver(): CrmDriverInterface
    {
        $config = $this->config->get('crm.drivers.hubspot', ['driver' => 'hubspot']);

        return new HubspotDriver($config);
    }

    /**
     * Synchronize a contact across the default driver and all configured sync drivers.
     *
     * @return array<string, CrmSyncResponse>
     */
    public function syncContact(CrmContactData $contact): array
    {
        $results = [];

        // 1. Primary / Default Driver
        $defaultDriver = $this->driver();
        if ($defaultDriver instanceof SyncsContactsInterface) {
            $results[$this->getDefaultDriver()] = $defaultDriver->upsertContact($contact);
        }

        // 2. Multi-broadcast to secondary sync drivers
        $syncDrivers = (array) $this->config->get('crm.sync_drivers', []);
        foreach ($syncDrivers as $driverName) {
            if ($driverName === $this->getDefaultDriver()) {
                continue;
            }

            try {
                $driver = $this->driver($driverName);
                if ($driver instanceof SyncsContactsInterface) {
                    $results[$driverName] = $driver->upsertContact($contact);
                }
            } catch (\Throwable $e) {
                $results[$driverName] = CrmSyncResponse::failed($e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Log an activity across default driver and all configured sync drivers.
     *
     * @return array<string, CrmSyncResponse>
     */
    public function logActivity(string $contactIdentifier, CrmActivityData $activity): array
    {
        $results = [];

        $defaultDriver = $this->driver();
        if ($defaultDriver instanceof SyncsActivitiesInterface) {
            $results[$this->getDefaultDriver()] = $defaultDriver->logActivity($contactIdentifier, $activity);
        }

        $syncDrivers = (array) $this->config->get('crm.sync_drivers', []);
        foreach ($syncDrivers as $driverName) {
            if ($driverName === $this->getDefaultDriver()) {
                continue;
            }

            try {
                $driver = $this->driver($driverName);
                if ($driver instanceof SyncsActivitiesInterface) {
                    $results[$driverName] = $driver->logActivity($contactIdentifier, $activity);
                }
            } catch (\Throwable $e) {
                $results[$driverName] = CrmSyncResponse::failed($e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Register a custom CRM driver creator closure.
     */
    public function extend($driver, Closure $callback): static
    {
        return parent::extend($driver, $callback);
    }
}
