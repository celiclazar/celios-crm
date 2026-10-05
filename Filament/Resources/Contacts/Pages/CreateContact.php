<?php

namespace Modules\Crm\Filament\Resources\Contacts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crm\Filament\Resources\Contacts\ContactResource;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
}
