<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBalance extends Model
{
    protected $table = 'user_balances';

    protected $fillable = [
        'user_id',
        'balance_cents',
        'pending_cents',
    ];

    protected $casts = [
        'balance_cents' => 'integer',
        'pending_cents' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getBalanceEuroAttribute(): float
    {
        return round($this->balance_cents / 100, 2);
    }

    public function getPendingEuroAttribute(): float
    {
        return round($this->pending_cents / 100, 2);
    }
}
