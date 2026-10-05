<?php

namespace Modules\Crm\Drivers;

use Modules\Crm\Contracts\SyncsActivitiesInterface;
use Modules\Crm\Contracts\SyncsContactsInterface;
use Modules\Crm\DTO\CrmActivityData;
use Modules\Crm\DTO\CrmContactData;
use Modules\Crm\DTO\CrmSyncResponse;
use Modules\Crm\Models\Contact;
use Modules\Crm\Models\ContactNote;
use Throwable;

class LocalDatabaseDriver extends AbstractCrmDriver implements SyncsContactsInterface, SyncsActivitiesInterface
{
    public function upsertContact(CrmContactData $contactData): CrmSyncResponse
    {
        try {
            $contact = null;

            if ($contactData->email) {
                $contact = Contact::where('email', $contactData->email)->first();
            }

            if (! $contact && $contactData->formSubmissionId) {
                $contact = Contact::where('form_submission_id', $contactData->formSubmissionId)->first();
            }

            $payload = array_filter([
                'name' => $contactData->name,
                'email' => $contactData->email,
                'phone' => $contactData->phone,
                'company' => $contactData->company,
                'stage' => $contactData->stage,
                'source' => $contactData->source,
                'lead_value' => $contactData->leadValue,
                'assigned_to_user_id' => $contactData->assignedToUserId,
                'form_submission_id' => $contactData->formSubmissionId,
                'notes' => $contactData->notes,
            ], fn ($val) => $val !== null);

            if ($contact) {
                $contact->update($payload);
            } else {
                $contact = Contact::create($payload);
            }

            return CrmSyncResponse::successful(
                (string) $contact->id,
                'Contact saved locally in database.'
            );
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function deleteContact(string $identifier): CrmSyncResponse
    {
        try {
            $contact = is_numeric($identifier)
                ? Contact::find((int) $identifier)
                : Contact::where('email', $identifier)->first();

            if ($contact) {
                $contact->delete();
                return CrmSyncResponse::successful($identifier, 'Contact deleted from local database.');
            }

            return CrmSyncResponse::failed("Contact not found for identifier: {$identifier}");
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }

    public function logActivity(string $contactIdentifier, CrmActivityData $activityData): CrmSyncResponse
    {
        try {
            $contact = is_numeric($contactIdentifier)
                ? Contact::find((int) $contactIdentifier)
                : Contact::where('email', $contactIdentifier)->first();

            if (! $contact) {
                return CrmSyncResponse::failed("Contact not found for identifier: {$contactIdentifier}");
            }

            $note = $contact->interactions()->create([
                'type' => $activityData->type,
                'content' => $activityData->content,
                'user_id' => $activityData->userId,
            ]);

            $contact->update(['last_contacted_at' => now()]);

            return CrmSyncResponse::successful(
                (string) $note->id,
                'Activity logged successfully in local database.'
            );
        } catch (Throwable $e) {
            return CrmSyncResponse::failed($e->getMessage());
        }
    }
}
