<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default CRM Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default CRM driver used for managing contacts.
    | The default 'local' driver persists data to the internal CMS database.
    |
    */
    'default' => env('CRM_DEFAULT_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Sync Drivers (Multi-Broadcast)
    |--------------------------------------------------------------------------
    |
    | When contacts or activities are created or updated locally, you can
    | automatically broadcast/sync the changes to these external drivers.
    | e.g. ['hubspot', 'webhook']
    |
    */
    'sync_drivers' => array_filter(explode(',', env('CRM_SYNC_DRIVERS', ''))),

    /*
    |--------------------------------------------------------------------------
    | CRM Drivers Configuration
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'local' => [
            'driver' => 'local',
            'name' => 'Local Celios CRM',
        ],

        'webhook' => [
            'driver' => 'webhook',
            'name' => 'External Webhook',
            'url' => env('CRM_WEBHOOK_URL'),
            'secret' => env('CRM_WEBHOOK_SECRET'),
            'timeout' => 10,
        ],

        'hubspot' => [
            'driver' => 'hubspot',
            'name' => 'HubSpot CRM',
            'access_token' => env('HUBSPOT_ACCESS_TOKEN'),
            'pipeline_id' => env('HUBSPOT_PIPELINE_ID', 'default'),
        ],
    ],
];
