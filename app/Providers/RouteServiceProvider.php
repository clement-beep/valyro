<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        // ✅ Throttle postback (et autres si besoin)
        RateLimiter::for('postback', function (Request $request) {
            // clés séparées par IP + token (évite que 1 IP flingue tout)
            $token = (string) $request->query('token', 'no-token');
            $key = 'postback:' . $request->ip() . ':' . substr($token, 0, 12);

            return Limit::perMinute(120)->by($key);
        });

        RateLimiter::for('offers-start', function (Request $request) {
            return Limit::perMinute(30)->by('offers-start:' . ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('withdraw', function (Request $request) {
            return Limit::perMinute(10)->by('withdraw:' . ($request->user()?->id ?? $request->ip()));
        });

        $this->routes(function () {
            Route::middleware('web')->group(base_path('routes/web.php'));
            Route::prefix('api')->middleware('api')->group(base_path('routes/api.php'));
        });
    }
}
