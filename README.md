# Celios CRM Integration Guide

This guide documents the architecture, configuration, and implementation steps for connecting Celios CMS to external Customer Relationship Management (CRM) platforms, including HubSpot and Salesforce.

---

## 1. Architecture Overview

Celios CRM uses a Driver and Gateway design pattern managed by `CrmManager` and exposed through the `Crm` Facade.

```
Incoming Request / Form Submission / Filament Admin
                |
                v
        Modules\Crm\Facades\Crm
                |
                v
      Modules\Crm\Services\CrmManager
       /              |              \
      v               v               v
Local Database     HubSpot        Salesforce / Custom
(Primary Storage) (Sync Driver)   (Sync Driver)
```

### Core Concepts

1. **Primary Driver (`CRM_DEFAULT_DRIVER`)**: The default storage driver where contacts are written synchronously. In most deployments, this is `local` (the internal database).
2. **Multi-Broadcast Sync (`CRM_SYNC_DRIVERS`)**: Comma-separated list of secondary drivers that receive contact and activity data whenever updates occur.
3. **Data Transfer Objects (DTOs)**: Normalized structures (`CrmContactData`, `CrmActivityData`, `CrmSyncResponse`) that isolate external API payloads from CMS models.

---

## 2. HubSpot Integration

The HubSpot driver is implemented in `Modules\Crm\Drivers\HubspotDriver`.

### Step 1: Obtain Credentials from HubSpot
1. Log in to the HubSpot Portal and navigate to **Settings** > **Integrations** > **Private Apps**.
2. Click **Create a private app** (for example, name it `Celios CMS Integration`).
3. Under the **Scopes** tab, select the following permissions:
   - `crm.objects.contacts.read`
   - `crm.objects.contacts.write`
   - `crm.objects.custom.read` (optional, for custom attributes)
   - `crm.objects.custom.write` (optional, for custom attributes)
4. Click **Create app** and copy the generated **Personal Access Token** (`pat-na1-...`).

### Step 2: Environment Configuration
Add the following variables to `.env`:

```env
# Maintain local database as source of truth and mirror to HubSpot
CRM_DEFAULT_DRIVER=local
CRM_SYNC_DRIVERS=hubspot

# HubSpot Access Credentials
HUBSPOT_ACCESS_TOKEN="pat-na1-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
HUBSPOT_PIPELINE_ID="default"
```

### Step 3: Lifecycle and Stage Mapping
The driver maps Celios stages to standard HubSpot lifecycle stages:

| Celios Stage | HubSpot `lifecyclestage` |
| :--- | :--- |
| `lead` | `lead` |
| `contacted` | `marketingqualifiedlead` |
| `qualified` | `opportunity` |
| `proposal` | `opportunity` |
| `won` | `customer` |
| `lost` | `lead` (or custom stage) |

### Step 4: Duplicate Handling
If a contact with the same email already exists in HubSpot, the driver intercepts the HTTP 409 conflict and issues an update request using `idProperty=email`.

---

## 3. Salesforce Integration

Follow these steps to implement a custom Salesforce driver.

### Step 1: Obtain Credentials from Salesforce
1. Go to Salesforce **Setup** > **Apps** > **App Manager** > **New Connected App**.
2. Fill in the App Name and Contact Email.
3. Check **Enable OAuth Settings**.
4. Set Callback URL (for example: `https://yourdomain.com/crm/oauth/salesforce/callback`).
5. Select OAuth Scopes:
   - `Manage user data via APIs (api)`
   - `Perform requests at any time (refresh_token, offline_access)`
6. Save and note the following values:
   - **Consumer Key** (`SALESFORCE_CLIENT_ID`)
   - **Consumer Secret** (`SALESFORCE_CLIENT_SECRET`)
   - **Instance URL** (for example: `https://yourcompany.my.salesforce.com`)
   - **Username, Password, and Security Token**

### Step 2: Create Driver Class
Create `packages/crm/Drivers/SalesforceDriver.php`:

```php
<?php

namespace Modules\Crm\Drivers;

use Illuminate\Support\Facades\Http;
use Modules\Crm\Contracts\SyncsActivitiesInterface;
use Modules\Crm\Contracts\SyncsContactsInterface;
use Modules\Crm\DTO\CrmActivityData;
use Modules\Crm\DTO\CrmContactData;
use Modules\Crm\DTO\CrmSyncResponse;
use Throwable;

class SalesforceDriver extends AbstractCrmDriver implements SyncsContactsInterface, SyncsActivitiesInterface
{
    protected function getAccessToken(): ?string
    {
        $response = Http::asForm()->post($this->config['login_url'].'/services/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'username' => $this->config['username'],
            'password' => $this->config['password'].$this->config['security_token'],
        ]);

        return $response->json('access_token');
    }

    public function upsertContact(CrmContactData $contact): CrmSyncResponse
    {
        try {
            $token = $this->getAccessToken();
            $instanceUrl = rtrim($this->config['instance_url'], '/');

            $response = Http::withToken($token)
                ->patch("{$instanceUrl}/services/data/v58.0/sobjects/Contact/Email/{$contact->email}", [
                    'LastName' => $contact->name,
                    'Phone' => $contact->phone,
                    'Description' => $contact->notes,
                    'LeadSource' => $contact->source,
                ]);

            if ($response->successful()) {
                return CrmSyncResponse::successful($response->json('id'), 'Synced to Salesforce');
            }

            return CrmSyncResponse::failed("Salesforce Error: {$response->body()}");
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function deleteContact(string $identifier): CrmSyncResponse
    {
        try {
            $token = $this->getAccessToken();
            $instanceUrl = rtrim($this->config['instance_url'], '/');

            $response = Http::withToken($token)
                ->delete("{$instanceUrl}/services/data/v58.0/sobjects/Contact/{$identifier}");

            if ($response->successful()) {
                return CrmSyncResponse::successful($identifier, 'Deleted from Salesforce');
            }

            return CrmSyncResponse::failed($response->body());
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function logActivity(string $contactIdentifier, CrmActivityData $activity): CrmSyncResponse
    {
        try {
            $token = $this->getAccessToken();
            $instanceUrl = rtrim($this->config['instance_url'], '/');

            $response = Http::withToken($token)
                ->post("{$instanceUrl}/services/data/v58.0/sobjects/Task", [
                    'Subject' => ucfirst($activity->type).' - Celios CMS Activity',
                    'Description' => $activity->content,
                    'Status' => 'Completed',
                    'ActivityDate' => ($activity->occurredAt ?? now())->format('Y-m-d'),
                ]);

            if ($response->successful()) {
                return CrmSyncResponse::successful($response->json('id'), 'Activity logged in Salesforce');
            }

            return CrmSyncResponse::failed($response->body());
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }
}
```

### Step 3: Register Configuration
In `packages/crm/Config/crm.php`, add:

```php
'salesforce' => [
    'driver' => 'salesforce',
    'name' => 'Salesforce CRM',
    'login_url' => env('SALESFORCE_LOGIN_URL', 'https://login.salesforce.com'),
    'instance_url' => env('SALESFORCE_INSTANCE_URL'),
    'client_id' => env('SALESFORCE_CLIENT_ID'),
    'client_secret' => env('SALESFORCE_CLIENT_SECRET'),
    'username' => env('SALESFORCE_USERNAME'),
    'password' => env('SALESFORCE_PASSWORD'),
    'security_token' => env('SALESFORCE_SECURITY_TOKEN'),
],
```

### Step 4: Register in Service Provider or Manager
In `packages/crm/Services/CrmManager.php`:

```php
public function createSalesforceDriver(): CrmDriverInterface
{
    $config = $this->config->get('crm.drivers.salesforce', ['driver' => 'salesforce']);

    return new SalesforceDriver($config);
}
```

Or dynamically inside any Service Provider:

```php
use Modules\Crm\Facades\Crm;
use Modules\Crm\Drivers\SalesforceDriver;

Crm::manager()->extend('salesforce', function ($app) {
    return new SalesforceDriver(config('crm.drivers.salesforce'));
});
```

### Step 5: Configure `.env`
```env
CRM_SYNC_DRIVERS=salesforce,hubspot
SALESFORCE_LOGIN_URL=https://login.salesforce.com
SALESFORCE_INSTANCE_URL=https://yourcompany.my.salesforce.com
SALESFORCE_CLIENT_ID=your_consumer_key
SALESFORCE_CLIENT_SECRET=your_consumer_secret
SALESFORCE_USERNAME=api_user@yourcompany.com
SALESFORCE_PASSWORD=your_password
SALESFORCE_SECURITY_TOKEN=your_security_token
```

---

## 4. Generic Webhook Integration (Zapier / Make / n8n)

The `WebhookDriver` is pre-installed. It POSTs standard JSON payloads on contact and activity events.

### Configuration
```env
CRM_SYNC_DRIVERS=webhook
CRM_WEBHOOK_URL="https://hooks.zapier.com/hooks/catch/123456/abcdef"
CRM_WEBHOOK_SECRET="your-signature-secret"
```

### Payload Format
```json
{
  "event": "contact.upserted",
  "contact": {
    "name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+123456789",
    "company": "Acme Inc",
    "stage": "qualified",
    "source": "form_submission",
    "lead_value": 2500.00,
    "assigned_to_user_id": 1,
    "form_submission_id": 42,
    "notes": "Inquiry submitted from Contact Us form"
  },
  "timestamp": "2026-10-05T20:15:00Z"
}
```

---

## 5. Usage in Code

### Creating or Updating Contacts
```php
use Modules\Crm\Facades\Crm;

$results = Crm::upsertContact([
    'name' => 'Alexander Bell',
    'email' => 'alexander@bell.com',
    'phone' => '+1555123456',
    'company' => 'Bell Telephone',
    'stage' => 'qualified',
    'lead_value' => 7500.00,
]);
```

### Logging Activities / Notes
```php
use Modules\Crm\Facades\Crm;

$results = Crm::logActivity($contactId, [
    'type' => 'call',
    'content' => 'Discussed project scope and technical timeline.',
]);
```

### Converting a Form Submission
```php
use Modules\Crm\Facades\Crm;

$contact = Crm::createFromFormSubmission($submissionId, [
    'name' => $data['name'],
    'email' => $data['email'],
    'phone' => $data['phone'] ?? null,
    'company' => $data['company'] ?? null,
    'notes' => $data['message'] ?? null,
]);
```
