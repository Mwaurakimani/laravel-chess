<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\System\Chess\ChessControllers;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawalRequestController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\PresenceController;



Route::get('/', function () {
    return redirect('login');
})->name('home');

Route::get('/fetch-results/{challenge}', [ChessControllers::class,'getChallengeResult'])->name('test');

Route::get('dashboard',[DashboardController::class,'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('active-users',function (){
    return Inertia::render('ActiveUsers',[
        'user' => request()->user()
    ]);
})
    ->middleware(['auth', 'verified'])
    ->name('active-users');

Route::middleware('auth')->group(function () {
    Route::post('/auth/users/online', function (Request $request) {
        $user = $request->user(); // ✅ use logged-in user, not payload
        $user->is_online = true;
        $user->last_seen_at = now();
        $user->save();

        return response()->json(['status' => 'updated']);
    });

    Route::post('/auth/users/offline', function (Request $request) {
        $user = $request->user();
        $user->is_online = false;
        $user->last_seen_at = now();
        $user->save();

        return response()->json(['status' => 'updated']);
    });

    Route::get('active-user', [USerController::class, 'activeUser'])
        ->middleware(['auth', 'verified'])
        ->name('active-user');
});

// Player Global Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('help-and-support', function () {
        return Inertia::render('Player/global/HelpAndSupport');
    })->name('help-and-support');

    Route::get('privacy-policy', function () {
        return Inertia::render('Player/global/PrivacyPolicy');
    })->name('privacy-policy');

    Route::get('terms-and-conditions', function () {
        return Inertia::render('Player/global/TermsAndConditions');
    })->name('terms-and-conditions');
});


// Player Matches Routes
Route::middleware(['auth', 'verified'])->prefix('matches')->name('matches.')->group(function () {
    Route::get('/', [ChallengeController::class,'index'])->name('active');

    Route::get('my-challenges',[ChallengeController::class,'my_matches'] )->name('my-challenges');

    Route::get('challenge/{id}', [ChallengeController::class,'show'])->name('challenge-details');

    Route::get('create-challenge', [ChallengeController::class,'create_challenge'])->name('create-challenge');

    Route::get('edit-challenge/{id}', fn() => Inertia::render('Player/matches/EditChallenge'))->name('edit-challenge');

    Route::get('ready/{id}', [ChallengeController::class,'ready'])->name('ready');

    Route::get('get-results/{challenge}',[ChallengeController::class,'get_results'] )->name('get-results');

    Route::get('results/{id}', [ChallengeController::class,'show_results'])->name('results');

    Route::post('create-challenge', [ChallengeController::class,'store_challenge'])->name('store-challenge');

    Route::post('get-active-matches', [ChallengeController::class,'get_active_matches'])->name('get-active-matches');

    Route::post('game-created/{challenge}', [ChallengeController::class,'game_created'])->name('game-created');

    Route::post('opponent-joined/{challenge}', [ChallengeController::class,'opponent_joined'])->name('opponent-joined');
});


Route::middleware(['auth', 'verified'])
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function () {

        // 1. Inertia page
        Route::get('/', function () {
            return Inertia::render('Player/notifications/NotificationsList');
        })->name('list');

        // 2. JSON API: fetch all notifications for current user
        Route::get('/all', [NotificationsController::class, 'index'])
            ->name('all');

        // 3. JSON API: store a new notification
        Route::post('/', [NotificationsController::class, 'store'])
            ->name('store');
    });


// Player Profile Routes
Route::middleware(['auth', 'verified'])->prefix('profile')->name('player-profile.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Player/profile/View');
    })->name('view');

    Route::get('edit', function () {
        return Inertia::render('Player/profile/Edit');
    })->name('edit');
});

// Player Tournaments Routes
Route::middleware(['auth', 'verified'])->prefix('tournaments')->name('tournaments.')->group(function () {
    Route::get('leaderboard', function () {
        return Inertia::render('Player/tournaments/LeadersBoard');
    })->name('leaderboard');
});

// Player Wallet Routes
Route::middleware(['auth', 'verified'])->prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/',[WalletController::class,'main'])->name('main');

    Route::get('active-peers', [WalletController::class,'index'] )->name('active-peers');

    Route::get('deposit', function () {
        return Inertia::render('Player/wallet/Deposit');
    })->name('deposit');

    Route::get('deposit/{id}', function ($id) {
        return Inertia::render('Player/wallet/DepositDetails', ['id' => $id]);
    })->name('deposit-details');

    Route::get('withdrawal/{id}',[WithdrawalRequestController::class,'view'])->name('withdrawal-details');

    Route::get('withdrawal-request/{id}', function () {
        return Inertia::render('Player/wallet/WithdrawalRequest');
    })->name('withdrawal-request');

    Route::post('withdrawal/{id}/confirm', [WithdrawalRequestController::class, 'confirmReceipt'])
        ->name('withdrawal-confirm');

    Route::post('wallet/withdrawal/{id}/mark-sent', [WithdrawalRequestController::class, 'markAsSent'])
        ->name('withdrawal-mark-sent');

    Route::post('/wallet/buy-tokens', [WalletController::class, 'buyTokens'])->name('buy-tokens');

});

//Challenge actions
Route::middleware(['auth', 'verified'])->prefix('challenges')->name('challenges.')->group(function () {
    Route::post('/contend',[ChallengeController::class,'contend'])->name('contend');
});

//Wallet actions
Route::middleware(['auth', 'verified'])->prefix('wallet_request')->name('wallet_request.')->group(function () {
    Route::post('/create',[WalletController::class,'request'])->name('create');
});


Route::post('/chess-online-status', [PresenceController::class, 'chessOnline'])
    ->name('api.chess-online-status')
    ->middleware(['auth', 'verified']);





Broadcast::routes(['middleware' => ['web', 'auth']]);

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
