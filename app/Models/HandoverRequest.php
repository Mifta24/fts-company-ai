<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'reason',
    'summary',
    'status',
    'assigned_to',
    'resolved_at',
])]
class HandoverRequest extends Model
{
    public const REASONS = ['custom_project', 'price_negotiation', 'partnership', 'technical_support', 'complaint', 'low_confidence'];

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
