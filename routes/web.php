<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\OffersController;
use App\Http\Controllers\PostbackController;

use App\Http\Controllers\Admin\TestCreditController;
use App\Http\Controllers\Admin\ApprovePendingController;
use App\Http\Controllers\Admin\WithdrawalsAdminController;
use App\Http\Controllers\Admin\ConversionsAdminController;
use App\Http\Controllers\Admin\PostbackSimulatorController;
use App\Http\Controllers\Admin\UsersAdminController;
use App\Http\Controllers\Admin\AdminDashboardController;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/conditions', 'legal.conditions')->name('legal.conditions');
Route::view('/confidentialite', 'legal.confidentialite')->name('legal.confidentialite');
Route::view('/support', 'legal.support')->name('legal.support');

/*
|--------------------------------------------------------------------------
| Postback (PUBLIC) - protégé par token (+ allowlist IP + signature optionnelles)
|--------------------------------------------------------------------------
*/
Route::get('/postback', [PostbackController::class, 'handle'])
    ->name('postback')
    ->middleware(['throttle:postback', 'postback.sec']); // ✅ important

/*
|--------------------------------------------------------------------------
| Sandbox (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::get('/sandbox/offer', function (\Illuminate\Http\Request $request) {
    abort_if(app()->environment('production'), 404);

    return response()->json([
        'ok' => true,
        'received' => $request->query(),
        'hint' => 'Tu dois voir s1=... ou subid=... ici.',
    ]);
})->name('sandbox.offer');

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Verified routes (email vérifié obligatoire)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['verified'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/offers', [OffersController::class, 'index'])->name('offers');

        Route::post('/offerwall/start', [OffersController::class, 'startOfferwall'])
            ->name('offerwall.start')
            ->middleware(['throttle:offers-start']);

        Route::get('/withdraw', [WithdrawController::class, 'index'])->name('withdraw');
        Route::post('/withdraw', [WithdrawController::class, 'store'])
            ->name('withdraw.store')
            ->middleware(['throttle:withdraw']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin routes (dédiées)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->middleware(['admin'])
        ->name('admin.')
        ->group(function () {

            // ✅ Optionnel: dashboard admin résumé
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::post('/test-credit', TestCreditController::class)->name('test-credit');
            Route::post('/approve-pending', ApprovePendingController::class)->name('approve-pending');

            Route::get('/withdrawals', [WithdrawalsAdminController::class, 'index'])->name('withdrawals');
            Route::post('/withdrawals/{transaction}/release', [WithdrawalsAdminController::class, 'release'])->name('withdrawals.release');
            Route::post('/withdrawals/{transaction}/paid', [WithdrawalsAdminController::class, 'markPaid'])->name('withdrawals.markPaid');
            Route::post('/withdrawals/{transaction}/reject', [WithdrawalsAdminController::class, 'reject'])->name('withdrawals.reject');

            Route::get('/conversions', [ConversionsAdminController::class, 'index'])->name('conversions');

            Route::post('/postback/simulate', [PostbackSimulatorController::class, 'run'])->name('postback.simulate');

            Route::get('/users', [UsersAdminController::class, 'index'])->name('users');
            Route::post('/users/{user}/credit', [UsersAdminController::class, 'credit'])->name('users.credit');
        });
});

require __DIR__ . '/auth.php';
