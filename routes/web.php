<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\System\Chess\ChessControllers;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WithdrawalRequestController;
use App\Http\Controllers\PresenceController;
use Illuminate\Http\Request;


// public/home
Route::get('/', fn() => redirect()->route('login'))->name('home');

// Dashboard
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Online presence endpoints
Route::middleware(['auth'])->prefix('auth/users')->group(function () {
    Route::post('online', fn(Request $r) => tap($r->user())->update(['is_online' => true, 'last_seen_at' => now()])->only('status'));
    Route::post('offline', fn(Request $r) => tap($r->user())->update(['is_online' => false, 'last_seen_at' => now()])->only('status'));
    Route::get('active', [UserController::class, 'activeUser'])->name('active-user');
});

// Matches
Route::middleware(['auth', 'verified'])
     ->prefix('matches')
     ->name('matches.')
     ->controller(ChallengeController::class)
     ->group(function () {
         Route::get('/', 'index')->name('active');
         Route::get('my-challenges', 'my_matches')->name('my-challenges');
         Route::get('challenge/{id}', 'show')->name('challenge-details');
         Route::get('create-challenge', 'create_challenge')->name('create-challenge');
         Route::get('edit-challenge/{id}', fn() => Inertia::render('Player/matches/EditChallenge'))->name('edit-challenge');
         Route::get('ready/{id}', 'ready')->name('ready');
         Route::get('get-results/{challenge}', 'get_results')->name('get-results');
         Route::get('results/{id}', 'show_results')->name('results');

         Route::post('store-challenge', 'store_challenge')->name('store-challenge');
         Route::post('get-active-matches', 'get_active_matches')->name('get-active-matches');
         Route::post('game-created/{challenge}', 'game_created')->name('game-created');
         Route::post('opponent-joined/{challenge}', 'opponent_joined')->name('opponent-joined');
     })
;

// Fetch a single challenge result (external)
Route::get('/fetch-results/{challenge}', [ChessControllers::class, 'getChallengeResult'])->name('test');

// Notifications (list page + API)
Route::middleware(['auth', 'verified'])
     ->prefix('notifications')
     ->name('notifications.')
     ->group(function () {
         Route::view('/', 'Player/notifications/NotificationsList')->name('list');
         Route::get('all', [NotificationsController::class, 'index'])->name('all');
         Route::post('/', [NotificationsController::class, 'store'])->name('store');
     })
;

// Profile
Route::middleware(['auth', 'verified'])
     ->prefix('profile')
     ->name('player-profile.')
     ->group(function () {
         Route::view('/', 'Player/profile/View')->name('view');
         Route::view('edit', 'Player/profile/Edit')->name('edit');
     })
;

// Tournaments
Route::middleware(['auth', 'verified'])
     ->prefix('tournaments')
     ->name('tournaments.')
     ->group(function () {
         Route::view('leaderboard', 'Player/tournaments/LeadersBoard')->name('leaderboard');
     })
;

// Wallet
Route::middleware(['auth', 'verified'])
     ->prefix('wallet')
     ->name('wallet.')
     ->group(function () {
         Route::get('/', [WalletController::class, 'main'])->name('main');
         Route::get('active-peers', [WalletController::class, 'index'])->name('active-peers');
         Route::view('deposit', 'Player/wallet/Deposit')->name('deposit');
         Route::view('deposit/{id}', 'Player/wallet/DepositDetails')->name('deposit-details');
         Route::get('withdrawal/{id}', [WithdrawalRequestController::class, 'view'])->name('withdrawal-details');
         Route::view('withdrawal-request/{id}', 'Player/wallet/WithdrawalRequest')->name('withdrawal-request');
         Route::post('withdrawal/{id}/confirm', [WithdrawalRequestController::class, 'confirmReceipt'])->name('withdrawal-confirm');
         Route::post('withdrawal/{id}/mark-sent', [WithdrawalRequestController::class, 'markAsSent'])->name('withdrawal-mark-sent');
         Route::post('buy-tokens', [WalletController::class, 'buyTokens'])->name('buy-tokens');
     })
;

// Contend a challenge
Route::middleware(['auth', 'verified'])->post('challenges/contend', [ChallengeController::class, 'contend'])->name('challenges.contend');

// Create wallet request
Route::middleware(['auth', 'verified'])->post('wallet_request/create', [WalletController::class, 'request'])->name('wallet_request.create');

// Chess presence API
Route::middleware(['auth', 'verified'])
     ->post('/chess-online-status', [PresenceController::class, 'chessOnline'])
     ->name('api.chess-online-status')
;

// Broadcast channels
Broadcast::routes(['middleware' => ['web', 'auth']]);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
