<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferConversion extends Model
{
    protected $table = 'offer_conversions';

    protected $fillable = [
        'offer_click_id',
        'subid',
        'user_id',
        'offer_id',
        'network',
        'status',

        'payout_points',
        'pending_points',
        'confirmed_points',

        'ip',
        'last_ip',
        'user_agent',

        'external_id',

        'raw',
        'last_payload',

        'first_seen_at',
        'last_seen_at',
        'received_at',
    ];

    protected $casts = [
        'payout_points' => 'integer',
        'pending_points' => 'integer',
        'confirmed_points' => 'integer',

        'raw' => 'array',
        'last_payload' => 'array',

        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function click(): BelongsTo
    {
        return $this->belongsTo(OfferClick::class, 'offer_click_id');
    }
}
