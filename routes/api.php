<?php

use App\Http\Controllers\Api\ReferenceDataController;
use App\Http\Controllers\Api\ChapterController;
use Illuminate\Support\Facades\Route;

Route::get('/languages', [ReferenceDataController::class, 'languages']);
Route::get('/canons/{canon:code}/books', [ReferenceDataController::class, 'canonBooks']);
Route::get('/translations/{translationCode}/books/{bookSlug}/chapters/{chapter}', [ChapterController::class, 'show'])
    ->whereNumber('chapter');
