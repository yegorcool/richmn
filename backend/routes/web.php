<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GameConfigController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/telegram', function (Request $request) {
    $queryString = http_build_query($request->query());
    $target = config('app.frontend_url').'/login/telegram'.($queryString !== '' ? '?'.$queryString : '');

    return redirect()->away($target);
});

Route::prefix('admin')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');

        Route::get('users', [UserManagementController::class, 'index'])->name('admin.users');
        Route::get('users/{user}', [UserManagementController::class, 'show'])->name('admin.users.show');
        Route::patch('users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');

        Route::get('themes', [GameConfigController::class, 'themes'])->name('admin.themes');
        Route::get('themes/create', [GameConfigController::class, 'createTheme'])->name('admin.themes.create');
        Route::post('themes', [GameConfigController::class, 'storeTheme'])->name('admin.themes.store');
        Route::get('themes/{theme}/edit', [GameConfigController::class, 'editTheme'])->name('admin.themes.edit');
        Route::put('themes/{theme}', [GameConfigController::class, 'updateTheme'])->name('admin.themes.update');
        Route::delete('themes/{theme}', [GameConfigController::class, 'deleteTheme'])->name('admin.themes.delete');

        Route::get('themes/{theme}/items', [GameConfigController::class, 'itemDefinitions'])->name('admin.item-definitions');
        Route::get('themes/{theme}/items/create', [GameConfigController::class, 'createItemDefinition'])->name('admin.item-definitions.create');
        Route::post('themes/{theme}/items', [GameConfigController::class, 'storeItemDefinition'])->name('admin.item-definitions.store');
        Route::get('themes/{theme}/items/{itemDefinition}/edit', [GameConfigController::class, 'editItemDefinition'])->name('admin.item-definitions.edit');
        Route::put('themes/{theme}/items/{itemDefinition}', [GameConfigController::class, 'updateItemDefinition'])->name('admin.item-definitions.update');
        Route::delete('themes/{theme}/items/{itemDefinition}', [GameConfigController::class, 'deleteItemDefinition'])->name('admin.item-definitions.delete');
        Route::post('themes/{theme}/items/{itemDefinition}/generate-icon', [GameConfigController::class, 'generateIcon'])->name('admin.item-definitions.generate-icon');

        Route::get('characters', [GameConfigController::class, 'characters'])->name('admin.characters');
        Route::get('characters/{character}/lines', [GameConfigController::class, 'characterLines'])->name('admin.character-lines');
        Route::post('characters/{character}/lines', [GameConfigController::class, 'storeLine'])->name('admin.character-lines.store');
        Route::delete('lines/{line}', [GameConfigController::class, 'deleteLine'])->name('admin.lines.delete');
    });
});

Route::fallback(function () {
    if (request()->is('admin') || request()->is('admin/*') || request()->is('api/*')) {
        abort(404);
    }
    $frontendPath = public_path('../frontend/dist/index.html');
    if (file_exists($frontendPath)) {
        return response()->file($frontendPath);
    }
    return view('welcome');
});
