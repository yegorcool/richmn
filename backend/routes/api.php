<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\EnergyController;
use App\Http\Controllers\Api\ChestController;
use App\Http\Controllers\Api\DecorController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\StreakController;
use App\Http\Controllers\Api\DailyChallengeController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\GiftController;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

Route::middleware('miniapp')->group(function () {
    Route::get('/user/me', [UserController::class, 'me']);
    Route::patch('/user/settings', [UserController::class, 'updateSettings']);

    Route::get('/game/state', [GameController::class, 'state']);
    Route::post('/game/merge', [GameController::class, 'merge']);
    Route::post('/game/generator/tap', [GameController::class, 'tapGenerator']);
    Route::post('/game/generator/tap-batch', [GameController::class, 'tapGeneratorBatch']);
    Route::post('/game/move-item', [GameController::class, 'moveItem']);
    Route::post('/game/move-generator', [GameController::class, 'moveGenerator']);
    Route::post('/game/move-batch', [GameController::class, 'moveBatch']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/{order}/submit', [OrderController::class, 'submit']);

    Route::get('/characters', [CharacterController::class, 'index']);
    Route::get('/characters/{character}/line', [CharacterController::class, 'line']);

    Route::get('/energy', [EnergyController::class, 'show']);
    Route::post('/energy/refill', [EnergyController::class, 'refill']);

    Route::get('/chests', [ChestController::class, 'index']);
    Route::post('/chests/{chest}/open', [ChestController::class, 'open']);

    Route::get('/decor/locations', [DecorController::class, 'locations']);
    Route::post('/decor/place', [DecorController::class, 'place']);
    Route::delete('/decor/remove', [DecorController::class, 'remove']);

    Route::get('/events/active', [EventController::class, 'active']);
    Route::get('/events/{event}/progress', [EventController::class, 'progress']);
    Route::get('/events/{event}/leaderboard', [EventController::class, 'leaderboard']);

    Route::get('/collection', [CollectionController::class, 'index']);

    Route::get('/referral', [ReferralController::class, 'show']);
    Route::post('/referral/claim', [ReferralController::class, 'claim']);

    Route::post('/ads/callback', [AdController::class, 'callback']);

    Route::get('/streak', [StreakController::class, 'show']);
    Route::post('/streak/claim', [StreakController::class, 'claim']);

    Route::get('/daily-challenge', [DailyChallengeController::class, 'show']);
    Route::post('/daily-challenge/{challenge}/claim', [DailyChallengeController::class, 'claim']);

    Route::post('/gifts/send', [GiftController::class, 'send']);
    Route::post('/gifts/claim', [GiftController::class, 'claimAll']);

    Route::post('/analytics/event', [AnalyticsController::class, 'track']);
});
