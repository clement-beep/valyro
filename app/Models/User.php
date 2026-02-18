<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\UserBalance;
use App\Models\Transaction;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ============================================================
     | RELATIONS
     ============================================================ */

    public function balance(): HasOne
    {
        return $this->hasOne(UserBalance::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ⚠️ Garde si tu as réellement un modèle Withdrawal
    public function withdrawals(): HasMany
    {
        return $this->hasMany(\App\Models\Withdrawal::class);
    }

    /* ============================================================
     | BOOT : créer automatiquement la balance utilisateur
     ============================================================ */

    protected static function booted(): void
    {
        static::created(function (User $user) {
            UserBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance_cents' => 0, 'pending_cents' => 0]
            );
        });
    }
}
