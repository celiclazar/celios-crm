<?php

namespace Modules\Crm\Filament\Support;

use Celios\Core\Models\FormSubmission;
use Celios\Core\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Modules\Crm\Filament\Resources\Contacts\ContactResource;
use Modules\Crm\Models\Contact;

class FormSubmissionCrmBridge
{
    /**
     * @return array<int, IconColumn>
     */
    public static function tableColumns(): array
    {
        return [
            IconColumn::make('is_lead')
                ->label(__('crm.crm'))
                ->boolean()
                ->trueIcon('heroicon-o-user-plus')
                ->falseIcon('')
                ->trueColor('primary')
                ->state(fn (FormSubmission $record): bool => Contact::where('form_submission_id', $record->id)->exists())
                ->visible(fn (): bool => module_enabled('crm'))
                ->alignCenter(),
        ];
    }

    /**
     * @return array<int, Action>
     */
    public static function tableActions(): array
    {
        return [
            Action::make('convert_to_lead')
                ->label(__('crm.convert_to_lead'))
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->visible(fn (FormSubmission $record): bool => module_enabled('crm') && ! Contact::where('form_submission_id', $record->id)->exists())
                ->modalHeading(__('crm.convert_to_lead_heading'))
                ->modalDescription(__('crm.convert_to_lead_description'))
                ->fillForm(fn (FormSubmission $record): array => self::extractLeadDefaults($record))
                ->form(self::leadFormSchema())
                ->action(fn (FormSubmission $record, array $data) => self::convertToLead($record, $data)),

            Action::make('view_lead')
                ->label(__('crm.contact_single'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (FormSubmission $record): bool => module_enabled('crm') && Contact::where('form_submission_id', $record->id)->exists())
                ->url(function (FormSubmission $record): ?string {
                    $contact = Contact::where('form_submission_id', $record->id)->first();

                    return $contact ? ContactResource::getUrl('edit', ['record' => $contact]) : null;
                }),
        ];
    }

    /**
     * @param  Closure(): FormSubmission  $recordResolver
     * @return array<int, Action>
     */
    public static function headerActions(Closure $recordResolver): array
    {
        return [
            Action::make('convert_to_lead')
                ->label(__('crm.convert_to_lead'))
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->visible(fn (): bool => module_enabled('crm') && ! Contact::where('form_submission_id', $recordResolver()->id)->exists())
                ->modalHeading(__('crm.convert_to_lead_heading'))
                ->modalDescription(__('crm.convert_to_lead_description'))
                ->fillForm(fn (): array => self::extractLeadDefaults($recordResolver()))
                ->form(self::leadFormSchema())
                ->action(fn (array $data) => self::convertToLead($recordResolver(), $data)),

            Action::make('view_lead')
                ->label(__('crm.contact_single'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => module_enabled('crm') && Contact::where('form_submission_id', $recordResolver()->id)->exists())
                ->url(function () use ($recordResolver): ?string {
                    $contact = Contact::where('form_submission_id', $recordResolver()->id)->first();

                    return $contact ? ContactResource::getUrl('edit', ['record' => $contact]) : null;
                }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function extractLeadDefaults(FormSubmission $record): array
    {
        $data = $record->data ?? [];
        $name = null;
        $email = null;
        $phone = null;
        $company = null;
        $notes = [];

        foreach ($data as $key => $value) {
            $k = strtolower(str_replace(['-', '_'], '', (string) $key));
            $valStr = is_array($value) ? implode(', ', $value) : (string) $value;

            if (! $name && (str_contains($k, 'name') || str_contains($k, 'ime'))) {
                $name = $valStr;
            } elseif (! $email && (str_contains($k, 'email') || str_contains($k, 'mail'))) {
                $email = $valStr;
            } elseif (! $phone && (str_contains($k, 'phone') || str_contains($k, 'tel') || str_contains($k, 'telefon'))) {
                $phone = $valStr;
            } elseif (! $company && (str_contains($k, 'company') || str_contains($k, 'kompanija') || str_contains($k, 'firma'))) {
                $company = $valStr;
            } else {
                $notes[] = str((string) $key)->headline().': '.$valStr;
            }
        }

        return [
            'name' => $name ?: 'Lead #'.$record->id,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'stage' => 'lead',
            'source' => 'form_submission',
            'lead_value' => null,
            'notes' => ! empty($notes) ? implode("\n", $notes) : null,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected static function leadFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('crm.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('company')
                ->label(__('crm.company'))
                ->maxLength(255),
            TextInput::make('email')
                ->label(__('crm.email'))
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label(__('crm.phone'))
                ->tel()
                ->maxLength(50),
            Select::make('stage')
                ->label(__('crm.stage'))
                ->options(fn () => Contact::getStages())
                ->default('lead')
                ->required(),
            TextInput::make('lead_value')
                ->label(__('crm.lead_value'))
                ->numeric()
                ->prefix('€'),
            Select::make('assigned_to_user_id')
                ->label(__('crm.assigned_to'))
                ->options(fn () => User::query()->pluck('name', 'id')->toArray())
                ->searchable()
                ->preload()
                ->nullable(),
            Textarea::make('notes')
                ->label(__('crm.notes'))
                ->rows(3),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function convertToLead(FormSubmission $record, array $data): void
    {
        $contact = Contact::create([
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'stage' => $data['stage'] ?? 'lead',
            'source' => 'form_submission',
            'lead_value' => $data['lead_value'] ?? null,
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'form_submission_id' => $record->id,
            'notes' => $data['notes'] ?? null,
        ]);

        if (! $record->is_read) {
            $record->update(['is_read' => true]);
        }

        if (! empty($data['notes'])) {
            $contact->interactions()->create([
                'user_id' => auth()->id(),
                'type' => 'note',
                'content' => "Inquiry details from Form #{$record->form_id} Submission #{$record->id}:\n".$data['notes'],
            ]);
        }

        Notification::make()
            ->title(__('crm.converted_to_lead_success'))
            ->success()
            ->send();
    }
}
