<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'amount_cents',
        'currency',
        'method',
        'paypal_email',
        'status',
        'admin_note',
        'requested_at',
        'processed_at',
        'meta',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAmountEuroAttribute(): float
    {
        return round(((int) $this->amount_cents) / 100, 2);
    }
}
