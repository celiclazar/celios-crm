<?php

namespace Modules\Crm\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Crm\Models\Contact;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('crm.contact_single'))
                    ->icon('heroicon-o-user')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
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
                            ]),
                    ]),

                Section::make(__('crm.crm'))
                    ->icon('heroicon-o-briefcase')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('stage')
                                    ->label(__('crm.stage'))
                                    ->options(Contact::getStages())
                                    ->default('lead')
                                    ->required(),

                                Select::make('source')
                                    ->label(__('crm.source'))
                                    ->options(__('crm.sources'))
                                    ->default('manual'),

                                TextInput::make('lead_value')
                                    ->label(__('crm.lead_value'))
                                    ->numeric()
                                    ->prefix('€'),

                                Select::make('assigned_to_user_id')
                                    ->label(__('crm.assigned_to'))
                                    ->relationship('assignedUser', 'name')
                                    ->searchable()
                                    ->preload(),

                                DateTimePicker::make('last_contacted_at')
                                    ->label(__('crm.last_contacted_at')),

                                Select::make('form_submission_id')
                                    ->label(__('sidebar.form_submissions'))
                                    ->relationship('formSubmission', 'id')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($record) => $record && $record->form_submission_id),
                            ]),
                    ]),

                Section::make(__('crm.notes'))
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('crm.notes'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
