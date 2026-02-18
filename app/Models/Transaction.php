<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    // Types normalisés (gains)
    public const TYPE_PENDING_CREDIT    = 'pending_credit';
    public const TYPE_CONFIRMED_CREDIT  = 'confirmed_credit';
    public const TYPE_PENDING_APPROVED  = 'pending_approved';

    // Types normalisés (retraits)
    public const TYPE_WITHDRAWAL_REQUEST = 'withdrawal_request';
    public const TYPE_CONFIRMED_DEBIT    = 'confirmed_debit';

    // Postback
    public const TYPE_POSTBACK_REJECTED  = 'postback_rejected';
    public const TYPE_POSTBACK_CHARGEBACK = 'postback_chargeback';

    protected $fillable = [
        'user_id',
        'type',
        'amount_cents',
        'currency',
        'status',
        'source',
        'reference',
        'note',
        'meta',
        'balance_before_cents',
        'balance_after_cents',
        'occurred_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'balance_before_cents' => 'integer',
        'balance_after_cents' => 'integer',
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getAmountEuroAttribute(): float
    {
        return round(((int) $this->amount_cents) / 100, 2);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ((string) $this->type) {
            self::TYPE_PENDING_CREDIT      => 'Crédit en attente',
            self::TYPE_CONFIRMED_CREDIT    => 'Crédit confirmé',
            self::TYPE_PENDING_APPROVED    => 'Pending validé',
            self::TYPE_WITHDRAWAL_REQUEST  => 'Demande de retrait',
            self::TYPE_CONFIRMED_DEBIT     => 'Débit (retrait)',
            self::TYPE_POSTBACK_REJECTED   => 'Postback rejeté',
            self::TYPE_POSTBACK_CHARGEBACK => 'Chargeback',
            default => (string) ($this->type ?? '—'),
        };
    }
}
