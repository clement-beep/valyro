<?php

namespace App\Providers;

use App\View\Composers\LayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Fix : force Blade engine for *.blade.php (si l’association a sauté)
        View::addExtension('blade.php', 'blade');

        // Injecte automatiquement les données (nav, user, balance) dans le layout principal
        View::composer('layouts.app', LayoutComposer::class);

        /*
        |--------------------------------------------------------------------------
        | Rate limiters
        |--------------------------------------------------------------------------
        */

        // ✅ Anti-fraude: rate limiting des demandes de retrait
        RateLimiter::for('withdraw', function (Request $request) {
            $userId = optional($request->user())->id ?? 0;
            $key = 'withdraw:' . $userId . ':' . ($request->ip() ?? '0.0.0.0');

            return [
                Limit::perMinute(6)->by($key),
                Limit::perHour(30)->by($key),
            ];
        });

        // ✅ OFFERS: anti-spam sur le bouton "Commencer"
        RateLimiter::for('offers-start', function (Request $request) {
            $userId = optional($request->user())->id;
            $key = 'offers-start:' . ($userId ?: 'guest') . ':' . ($request->ip() ?? '0.0.0.0');

            return [
                Limit::perMinute(30)->by($key),
                Limit::perHour(300)->by($key),
            ];
        });

        // ✅ POSTBACK: anti-spam / anti-bot (PUBLIC)
        RateLimiter::for('postback', function (Request $request) {
            $ip = $request->ip() ?? '0.0.0.0';

            // On inclut (si présent) un bout de token dans la clé pour éviter
            // que plusieurs réseaux/instances se gênent entre eux.
            $token = (string) $request->query('token', '');
            $tokenKey = $token !== '' ? substr(sha1($token), 0, 10) : 'notoken';

            $key = 'postback:' . $ip . ':' . $tokenKey;

            return [
                // Large mais protège bien contre les floods
                Limit::perMinute(120)->by($key),
                Limit::perHour(2000)->by($key),
            ];
        });
    }
}
