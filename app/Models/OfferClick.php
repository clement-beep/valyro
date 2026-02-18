<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferClick extends Model
{
    protected $table = 'offer_clicks';

    protected $fillable = [
        'user_id',
        'offer_id',
        'subid',
        'ip',
        'user_agent',
        'referrer',
        'risk_score',
        'risk_flags',
        'started_at',
    ];

    protected $casts = [
        'risk_flags' => 'array',
        'started_at' => 'datetime',
        'risk_score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
