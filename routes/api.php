<?php

use App\Http\Controllers\Api\ReferenceDataController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\StudyDataController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\SupplementalTextController;
use App\Http\Controllers\Api\VerseNoteController;
use Illuminate\Support\Facades\Route;

Route::get('/languages', [ReferenceDataController::class, 'languages']);
Route::get('/translations', [ReferenceDataController::class, 'translations']);
Route::get('/canons/{canon:code}/books', [ReferenceDataController::class, 'canonBooks']);
Route::get('/translations/{translationCode}/books', [ReferenceDataController::class, 'translationBooks']);
Route::get('/translations/{translationCode}/supplemental-texts', [SupplementalTextController::class, 'index']);
Route::get('/translations/{translationCode}/books/{bookSlug}/chapters/{chapter}', [ChapterController::class, 'show'])
    ->whereNumber('chapter');
Route::get('/strong/{number}', [StudyDataController::class, 'strongEntry']);
Route::get('/verses/{verse}/strong-tokens', [StudyDataController::class, 'verseStrongTokens'])
    ->whereNumber('verse');
Route::get('/verses/{verse}/cross-references', [StudyDataController::class, 'verseCrossReferences'])
    ->whereNumber('verse');
Route::get('/verses/{verse}/notes', [VerseNoteController::class, 'index'])
    ->whereNumber('verse');
Route::post('/verses/{verse}/notes', [VerseNoteController::class, 'store'])
    ->whereNumber('verse');
Route::get('/search/verses', [SearchController::class, 'verses']);
Route::get('/calendar/day', [CalendarController::class, 'day']);
Route::post('/telegram/webhook', TelegramWebhookController::class);
