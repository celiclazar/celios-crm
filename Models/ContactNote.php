<?php

namespace Modules\Crm\Models;

use Celios\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Crm\DTO\CrmActivityData;

class ContactNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'user_id',
        'type',
        'content',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toCrmActivityData(): CrmActivityData
    {
        return new CrmActivityData(
            type: $this->type,
            content: $this->content,
            userId: $this->user_id,
            userName: $this->user?->name,
            occurredAt: $this->created_at,
        );
    }

    public static function getTypes(): array
    {
        return [
            'note' => 'Internal Note',
            'call' => 'Phone Call',
            'meeting' => 'Meeting',
            'email' => 'Email',
        ];
    }
}
