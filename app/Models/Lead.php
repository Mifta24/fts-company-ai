<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'conversation_id',
    'service_id',
    'type',
    'name',
    'organization',
    'email',
    'phone',
    'business_type',
    'country',
    'preferred_contact',
    'preferred_time',
    'budget_range',
    'needs_summary',
    'status',
])]
class Lead extends Model
{
    public const TYPES = ['consultation', 'demo', 'quotation'];

    public const STATUS_NEW = 'new';

    public const STATUSES = ['new', 'contacted', 'qualified', 'won', 'lost'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function reference(): string
    {
        return 'FTS-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
