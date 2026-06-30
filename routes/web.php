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
use App\Http\Middleware\AllowReaderEmbedding;
use App\Support\FooterPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app', [
        'appSurface' => 'standard',
        'footerPages' => FooterPages::links(),
    ]);
})->middleware(AllowReaderEmbedding::class);

Route::get('/embed', function () {
    return view('app', [
        'embed' => true,
        'appSurface' => 'webview',
        'embedSource' => 'webview',
        'footerPages' => FooterPages::links(),
    ]);
})->middleware(AllowReaderEmbedding::class)->name('embed');

Route::get('/telegramm-mini-app', function () {
    return view('app', [
        'embed' => true,
        'appSurface' => 'telegram',
        'embedSource' => 'telegram',
        'footerPages' => FooterPages::links(),
    ]);
})->middleware(AllowReaderEmbedding::class)->name('telegram-mini-app');

Route::get('/webview', function () {
    return view('app', [
        'embed' => true,
        'appSurface' => 'webview',
        'embedSource' => 'webview',
        'footerPages' => FooterPages::links(),
    ]);
})->middleware(AllowReaderEmbedding::class)->name('webview');

Route::get('/mini-app', function (Request $request) {
    $query = $request->getQueryString();

    return redirect('/'.($query ? '?'.$query : ''), 301);
})->name('legacy-mini-app');

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
    Route::put('/dashboard/notes/{note}', [DashboardController::class, 'updateNote'])->whereNumber('note')->name('dashboard.notes.update');
    Route::delete('/dashboard/notes/{note}', [DashboardController::class, 'deleteNote'])->whereNumber('note')->name('dashboard.notes.delete');
    Route::put('/dashboard/bookmarks/{bookmark}', [DashboardController::class, 'updateBookmark'])->whereNumber('bookmark')->name('dashboard.bookmarks.update');
    Route::delete('/dashboard/bookmarks/{bookmark}', [DashboardController::class, 'deleteBookmark'])->whereNumber('bookmark')->name('dashboard.bookmarks.delete');
    Route::put('/dashboard/posts/{post}', [DashboardController::class, 'updatePost'])->whereNumber('post')->name('dashboard.posts.update');
    Route::delete('/dashboard/posts/{post}', [DashboardController::class, 'deletePost'])->whereNumber('post')->name('dashboard.posts.delete');
    Route::get('/reader/verses/{verse}/notes', [ReaderDataController::class, 'notes'])->whereNumber('verse');
    Route::post('/reader/verses/{verse}/notes', [ReaderDataController::class, 'storeNote'])->whereNumber('verse');
    Route::get('/reader/bookmarks', [ReaderDataController::class, 'bookmarks']);
    Route::post('/reader/bookmarks', [ReaderDataController::class, 'storeBookmark']);
    Route::get('/reader/feed', [ReaderDataController::class, 'feed']);
    Route::post('/reader/feed', [ReaderDataController::class, 'storeFeedPost']);
});
