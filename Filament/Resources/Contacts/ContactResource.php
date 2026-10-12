<?php

namespace Modules\Crm\Filament\Resources\Contacts;

use BackedEnum;
use Celios\Core\Filament\Traits\HasModuleToggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Crm\Filament\Resources\Contacts\Pages\CreateContact;
use Modules\Crm\Filament\Resources\Contacts\Pages\EditContact;
use Modules\Crm\Filament\Resources\Contacts\Pages\ListContacts;
use Modules\Crm\Filament\Resources\Contacts\RelationManagers\NotesRelationManager;
use Modules\Crm\Filament\Resources\Contacts\Schemas\ContactForm;
use Modules\Crm\Filament\Resources\Contacts\Tables\ContactsTable;
use Modules\Crm\Models\Contact;

class ContactResource extends Resource
{
    use HasModuleToggle;

    public const MODULE_KEY = 'crm';

    protected static ?string $model = Contact::class;

    protected static ?string $slug = 'contacts';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('sidebar.group_marketing');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->check()) {
            return null;
        }

        $count = static::getEloquentQuery()
            ->where('stage', 'lead')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return ContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.contacts');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.contacts');
    }

    public static function getModelLabel(): string
    {
        return __('crm.contact_single');
    }

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        return $record instanceof Contact ? $record->displayTitle() : null;
    }
}
