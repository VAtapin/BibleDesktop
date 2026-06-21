<?php

/**
 * BibleDesktop - Bible study desktop and web application.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 *
 * @link https://bible-desktop.com/
 *
 * @copyright 2026 Atapin Vladimir / Bible Media
 *
 * @version 1.0.0
 */
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReaderDataController;
use App\Support\FooterPages;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app', ['footerPages' => FooterPages::links()]);
});

Route::get('/embed', function () {
    return view('app', [
        'embed' => true,
        'embedSource' => request()->query('source'),
        'footerPages' => FooterPages::links(),
    ]);
})->name('embed');

Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reader/verses/{verse}/notes', [ReaderDataController::class, 'notes'])->whereNumber('verse');
    Route::post('/reader/verses/{verse}/notes', [ReaderDataController::class, 'storeNote'])->whereNumber('verse');
    Route::get('/reader/bookmarks', [ReaderDataController::class, 'bookmarks']);
    Route::post('/reader/bookmarks', [ReaderDataController::class, 'storeBookmark']);
    Route::get('/reader/feed', [ReaderDataController::class, 'feed']);
    Route::post('/reader/feed', [ReaderDataController::class, 'storeFeedPost']);
});
