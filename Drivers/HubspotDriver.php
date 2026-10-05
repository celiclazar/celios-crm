<?php

namespace Modules\Crm\Drivers;

use Illuminate\Support\Facades\Http;
use Modules\Crm\Contracts\SyncsActivitiesInterface;
use Modules\Crm\Contracts\SyncsContactsInterface;
use Modules\Crm\DTO\CrmActivityData;
use Modules\Crm\DTO\CrmContactData;
use Modules\Crm\DTO\CrmSyncResponse;
use Throwable;

class HubspotDriver extends AbstractCrmDriver implements SyncsContactsInterface, SyncsActivitiesInterface
{
    protected string $baseUrl = 'https://api.hubapi.com';

    public function testConnection(): bool
    {
        return ! empty($this->config['access_token']);
    }

    public function upsertContact(CrmContactData $contact): CrmSyncResponse
    {
        $token = $this->config['access_token'] ?? null;
        if (! $token) {
            return CrmSyncResponse::failed('HubSpot access token not configured.');
        }

        try {
            $nameParts = explode(' ', trim($contact->name), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            $properties = array_filter([
                'email' => $contact->email,
                'firstname' => $firstName,
                'lastname' => $lastName,
                'phone' => $contact->phone,
                'company' => $contact->company,
                'lifecyclestage' => $this->mapStageToHubspot($contact->stage),
            ]);

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/crm/v3/objects/contacts", [
                    'properties' => $properties,
                ]);

            if ($response->successful()) {
                return CrmSyncResponse::successful(
                    (string) $response->json('id'),
                    'Contact synced with HubSpot.'
                );
            }

            // If contact already exists (HTTP 409), attempt update
            if ($response->status() === 409 && $contact->email) {
                $updateRes = Http::withToken($token)
                    ->post("{$this->baseUrl}/crm/v3/objects/contacts/{$contact->email}?idProperty=email", [
                        'properties' => $properties,
                    ]);

                if ($updateRes->successful()) {
                    return CrmSyncResponse::successful(
                        (string) $updateRes->json('id'),
                        'Existing contact updated in HubSpot.'
                    );
                }
            }

            return CrmSyncResponse::failed("HubSpot API error ({$response->status()}): {$response->body()}", $response->json() ?? []);
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function deleteContact(string $identifier): CrmSyncResponse
    {
        $token = $this->config['access_token'] ?? null;
        if (! $token) {
            return CrmSyncResponse::failed('HubSpot access token not configured.');
        }

        try {
            $response = Http::withToken($token)
                ->delete("{$this->baseUrl}/crm/v3/objects/contacts/{$identifier}");

            if ($response->successful()) {
                return CrmSyncResponse::successful($identifier, 'Contact deleted from HubSpot.');
            }

            return CrmSyncResponse::failed("HubSpot API error: {$response->body()}");
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function logActivity(string $contactIdentifier, CrmActivityData $activity): CrmSyncResponse
    {
        $token = $this->config['access_token'] ?? null;
        if (! $token) {
            return CrmSyncResponse::failed('HubSpot access token not configured.');
        }

        try {
            $hubspotType = match ($activity->type) {
                'call' => 'calls',
                'meeting' => 'meetings',
                'email' => 'emails',
                default => 'notes',
            };

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/crm/v3/objects/{$hubspotType}", [
                    'properties' => [
                        'hs_note_body' => $activity->content,
                        'hs_timestamp' => ($activity->occurredAt ?? now())->toIso8601String(),
                    ],
                ]);

            if ($response->successful()) {
                return CrmSyncResponse::successful(
                    (string) $response->json('id'),
                    'Activity logged in HubSpot.'
                );
            }

            return CrmSyncResponse::failed("HubSpot API error: {$response->body()}");
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    protected function mapStageToHubspot(string $stage): string
    {
        return match ($stage) {
            'won' => 'customer',
            'qualified', 'proposal' => 'opportunity',
            'contacted' => 'marketingqualifiedlead',
            default => 'lead',
        };
    }
}
