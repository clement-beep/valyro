<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class LayoutComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        // Route helper: route si existe sinon fallback
        $routeOr = function (string $name, string $fallback = '#'): string {
            return Route::has($name) ? route($name) : $fallback;
        };

        $isLogged = (bool) $user;

        // Admin simple (à terme => role)
        $isAdmin = (bool) ($user && ($user->email === 'maillet.clement.ifsi@gmail.com'));

        $adminWithdrawalsUrl = $isAdmin
            ? (Route::has('admin.withdrawals') ? route('admin.withdrawals') : '/admin/withdrawals')
            : null;

        // Convention: 1 pt = 1 centime (stock en cents)
        $balanceCents = (int) ($user?->balance?->balance_cents ?? 0);
        $pendingCents = (int) ($user?->balance?->pending_cents ?? 0);

        $fmtPts = fn (int $c) => number_format($c, 0, ',', ' ') . ' pts';
        $fmtEur = fn (int $c) => number_format($c / 100, 2, ',', ' ') . ' €';

        // Données finales prêtes à afficher (strings)
        $layoutUserName  = (string) ($user->name ?? '');
        $layoutUserEmail = (string) ($user->email ?? '');

        $view->with([
            // title fallback
            'layoutTitle' => config('app.name', 'Valyro'),

            // flags
            'layoutIsLogged' => $isLogged,
            'layoutIsAdmin'  => $isAdmin,

            // user
            'layoutUserName'  => $layoutUserName,
            'layoutUserEmail' => $layoutUserEmail,

            // balance strings
            'layoutBalancePts'  => $fmtPts($balanceCents),
            'layoutPendingPts'  => $fmtPts($pendingCents),
            'layoutBalanceEur'  => $fmtEur($balanceCents),
            'layoutPendingEur'  => $fmtEur($pendingCents),

            // URLs
            'navUrls' => [
                'home'      => $routeOr('home', '/'),
                'dashboard' => $routeOr('dashboard', '/dashboard'),
                'offers'    => $routeOr('offers', '/offers'),
                'withdraw'  => $routeOr('withdraw', '/withdraw'),
                'profile'   => $routeOr('profile.edit', '/profile'),
                'login'     => $routeOr('login', '/login'),
                'register'  => $routeOr('register', '/register'),
                'logout'    => $routeOr('logout', '/logout'),
                'admin'     => $adminWithdrawalsUrl,
            ],

            // URLs légales
            'legalUrls' => [
                'conditions'      => $routeOr('legal.conditions', '/conditions'),
                'confidentialite' => $routeOr('legal.confidentialite', '/confidentialite'),
                'support'         => $routeOr('legal.support', '/support'),
            ],
        ]);
    }
}
