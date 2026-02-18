<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Offer extends Model
{
    protected $fillable = [
        'title','slug','description',
        'payout_points','network','category','difficulty',
        'est_validation_hours','url',
        'active','sort_weight','is_simple',
    ];

    protected $casts = [
        'payout_points' => 'integer',
        'est_validation_hours' => 'integer',
        'active' => 'boolean',
        'sort_weight' => 'integer',
        'is_simple' => 'boolean',
    ];

    public function clicks(): HasMany
    {
        return $this->hasMany(OfferClick::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $offer) {
            if (blank($offer->slug)) {
                $offer->slug = Str::slug($offer->title) . '-' . Str::lower(Str::random(6));
            }
        });
    }
}
