<?php

use App\Http\Controllers\Api\ReferenceDataController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\StudyDataController;
use Illuminate\Support\Facades\Route;

Route::get('/languages', [ReferenceDataController::class, 'languages']);
Route::get('/translations', [ReferenceDataController::class, 'translations']);
Route::get('/canons/{canon:code}/books', [ReferenceDataController::class, 'canonBooks']);
Route::get('/translations/{translationCode}/books/{bookSlug}/chapters/{chapter}', [ChapterController::class, 'show'])
    ->whereNumber('chapter');
Route::get('/strong/{number}', [StudyDataController::class, 'strongEntry']);
Route::get('/verses/{verse}/strong-tokens', [StudyDataController::class, 'verseStrongTokens'])
    ->whereNumber('verse');
Route::get('/verses/{verse}/cross-references', [StudyDataController::class, 'verseCrossReferences'])
    ->whereNumber('verse');
