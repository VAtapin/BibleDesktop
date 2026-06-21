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
use App\Models\CmsPage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $footerPages = Schema::hasTable('cms_pages')
        ? CmsPage::query()
            ->where('is_published', true)
            ->where('menu_location', 'footer')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['title', 'slug', 'menu_label'])
            ->map(fn (CmsPage $page): array => [
                'title' => $page->menu_label ?: $page->title,
                'url' => route('pages.show', ['slug' => $page->slug]),
            ])
        : collect();

    return view('app', ['footerPages' => $footerPages]);
});

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
