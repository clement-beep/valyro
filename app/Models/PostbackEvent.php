<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PostbackEvent extends Model
{
    protected $table = 'postback_events';

    protected $fillable = [
        'external_id',
        'subid',
        'offer_click_id',
        'user_id',
        'offer_id',
        'network',
        'status',
        'payout_points',
        'raw',
        'ip',
        'user_agent',
        'received_at',
    ];

    protected $casts = [
        'raw' => 'array',
        'received_at' => 'datetime',
        'payout_points' => 'integer',
        'status' => 'string',
        'network' => 'string',
        'subid' => 'string',
        'external_id' => 'string',
        'ip' => 'string',
        'user_agent' => 'string',
    ];

    // ✅ Si jamais tu crées un event sans received_at, on le remplit automatiquement
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->received_at)) {
                $model->received_at = now();
            }
        });
    }

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

    // ✅ petit helper pour l’admin
    public function scopeLatest(Builder $q): Builder
    {
        return $q->orderByDesc('id');
    }
}
