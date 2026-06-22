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
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\ContentToolController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\PrayerController;
use App\Http\Controllers\Api\ReferenceDataController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\StudyDataController;
use App\Http\Controllers\Api\SupplementalTextController;
use App\Http\Controllers\Api\TelegramWebhookController;
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
Route::get('/prayers', [PrayerController::class, 'index']);
Route::get('/prayers/{prayer}', [PrayerController::class, 'show'])->whereNumber('prayer');
Route::get('/prayers/{prayer}/sections/{section}', [PrayerController::class, 'section'])->whereNumber('prayer')->whereNumber('section');
Route::get('/recipe-categories', [ContentToolController::class, 'recipeCategories']);
Route::get('/recipes', [ContentToolController::class, 'recipes']);
Route::post('/recipes', [ContentToolController::class, 'storeRecipe'])->middleware('auth');
Route::get('/recipes/{recipe}', [ContentToolController::class, 'recipe'])->whereNumber('recipe');
Route::get('/quizzes', [ContentToolController::class, 'quizzes']);
Route::get('/quizzes/{quiz}', [ContentToolController::class, 'quiz'])->whereNumber('quiz');
Route::get('/virtual-tours', [ContentToolController::class, 'tours']);
Route::post('/telegram/webhook', TelegramWebhookController::class);
