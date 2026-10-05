<?php

namespace Modules\Crm\Filament\Resources\Contacts\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Crm\Models\Contact;
use Modules\Crm\Models\ContactNote;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    public static function getTitle(mixed $ownerRecord, string $pageClass): string
    {
        return __('crm.timeline_and_interactions') ?? 'Interactions & Timeline';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (ContactNote $record): string => $record->created_at ? $record->created_at->diffForHumans() : '')
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('crm.interaction_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'call' => 'info',
                        'meeting' => 'warning',
                        'email' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ContactNote::getTypes()[$state] ?? ucfirst($state)),

                TextColumn::make('user.name')
                    ->label(__('blog.author'))
                    ->default(__('fields.system'))
                    ->icon('heroicon-o-user'),

                TextColumn::make('content')
                    ->label(__('crm.interaction_content'))
                    ->wrap()
                    ->limit(120),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('crm.add_interaction'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Select::make('type')
                            ->label(__('crm.interaction_type'))
                            ->options(ContactNote::getTypes())
                            ->default('note')
                            ->required(),

                        Textarea::make('content')
                            ->label(__('crm.interaction_content'))
                            ->required()
                            ->rows(4),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    })
                    ->after(function () {
                        /** @var Contact $contact */
                        $contact = $this->getOwnerRecord();
                        $contact->update(['last_contacted_at' => now()]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        Select::make('type')
                            ->label(__('crm.interaction_type'))
                            ->options(ContactNote::getTypes())
                            ->required(),

                        Textarea::make('content')
                            ->label(__('crm.interaction_content'))
                            ->required()
                            ->rows(4),
                    ]),
                DeleteAction::make(),
            ]);
    }
}
