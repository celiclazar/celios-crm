<?php

namespace Modules\Crm\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Crm\Contracts\HandlesWebhooksInterface;
use Modules\Crm\Contracts\SyncsActivitiesInterface;
use Modules\Crm\Contracts\SyncsContactsInterface;
use Modules\Crm\DTO\CrmActivityData;
use Modules\Crm\DTO\CrmContactData;
use Modules\Crm\DTO\CrmSyncResponse;
use Throwable;

class WebhookDriver extends AbstractCrmDriver implements SyncsContactsInterface, SyncsActivitiesInterface, HandlesWebhooksInterface
{
    public function testConnection(): bool
    {
        return ! empty($this->config['url']);
    }

    public function upsertContact(CrmContactData $contact): CrmSyncResponse
    {
        $url = $this->config['url'] ?? null;
        if (! $url) {
            return CrmSyncResponse::failed('Webhook URL not configured.');
        }

        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 10))
                ->withHeaders($this->getHeaders())
                ->post($url, [
                    'event' => 'contact.upserted',
                    'contact' => $contact->toArray(),
                    'timestamp' => now()->toIso8601String(),
                ]);

            if ($response->successful()) {
                return CrmSyncResponse::successful(
                    $response->json('id') ?? $contact->email,
                    'Contact sent via webhook successfully.'
                );
            }

            return CrmSyncResponse::failed("Webhook responded with HTTP {$response->status()}: {$response->body()}", $response->json() ?? []);
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function deleteContact(string $identifier): CrmSyncResponse
    {
        $url = $this->config['url'] ?? null;
        if (! $url) {
            return CrmSyncResponse::failed('Webhook URL not configured.');
        }

        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 10))
                ->withHeaders($this->getHeaders())
                ->post($url, [
                    'event' => 'contact.deleted',
                    'identifier' => $identifier,
                    'timestamp' => now()->toIso8601String(),
                ]);

            if ($response->successful()) {
                return CrmSyncResponse::successful($identifier, 'Contact deletion sent via webhook.');
            }

            return CrmSyncResponse::failed("Webhook responded with HTTP {$response->status()}: {$response->body()}");
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function logActivity(string $contactIdentifier, CrmActivityData $activity): CrmSyncResponse
    {
        $url = $this->config['url'] ?? null;
        if (! $url) {
            return CrmSyncResponse::failed('Webhook URL not configured.');
        }

        try {
            $response = Http::timeout((int) ($this->config['timeout'] ?? 10))
                ->withHeaders($this->getHeaders())
                ->post($url, [
                    'event' => 'activity.logged',
                    'contact_identifier' => $contactIdentifier,
                    'activity' => $activity->toArray(),
                    'timestamp' => now()->toIso8601String(),
                ]);

            if ($response->successful()) {
                return CrmSyncResponse::successful(null, 'Activity logged via webhook successfully.');
            }

            return CrmSyncResponse::failed("Webhook responded with HTTP {$response->status()}: {$response->body()}");
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function handleWebhook(Request $request): CrmSyncResponse
    {
        $secret = $this->config['secret'] ?? null;
        if ($secret && $request->header('X-Webhook-Secret') !== $secret) {
            return CrmSyncResponse::failed('Invalid webhook secret signature.');
        }

        return CrmSyncResponse::successful(null, 'Webhook processed successfully.');
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if (! empty($this->config['secret'])) {
            $headers['X-Webhook-Secret'] = $this->config['secret'];
        }

        return $headers;
    }
}
