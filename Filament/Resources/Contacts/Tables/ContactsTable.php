<?php

namespace Modules\Crm\Filament\Resources\Contacts\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Crm\Models\Contact;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('crm.name'))
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company')
                    ->label(__('crm.company'))
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('crm.email'))
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->label(__('crm.phone'))
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('stage')
                    ->label(__('crm.stage'))
                    ->badge()
                    ->color(fn (?string $state): string => Contact::getStageColors()[$state] ?? 'gray')
                    ->formatStateUsing(fn (?string $state): string => Contact::getStages()[$state] ?? ($state ? ucfirst($state) : '-'))
                    ->sortable(),

                TextColumn::make('lead_value')
                    ->label(__('crm.lead_value'))
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('assignedUser.name')
                    ->label(__('crm.assigned_to'))
                    ->placeholder(__('crm.unassigned'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('source')
                    ->label(__('crm.source'))
                    ->formatStateUsing(function (?string $state): string {
                        $sources = __('crm.sources');
                        if (is_array($sources) && isset($sources[$state])) {
                            return $sources[$state];
                        }

                        return $state ? ucfirst(str_replace('_', ' ', $state)) : '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('stage')
                    ->label(__('crm.stage'))
                    ->options(Contact::getStages()),

                SelectFilter::make('source')
                    ->label(__('crm.source'))
                    ->options(__('crm.sources')),

                SelectFilter::make('assigned_to_user_id')
                    ->label(__('crm.assigned_to'))
                    ->relationship('assignedUser', 'name')
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('change_stage')
                    ->label(__('crm.stage'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->form([
                        Select::make('stage')
                            ->label(__('crm.stage'))
                            ->options(Contact::getStages())
                            ->required(),
                    ])
                    ->fillForm(fn (Contact $record): array => ['stage' => $record->stage])
                    ->action(function (Contact $record, array $data): void {
                        $record->update(['stage' => $data['stage']]);

                        Notification::make()
                            ->title(__('notifications.updated_successfully') ?? 'Updated successfully')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
