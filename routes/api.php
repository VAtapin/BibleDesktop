<?php

use App\Http\Controllers\Api\ReferenceDataController;
use Illuminate\Support\Facades\Route;

Route::get('/languages', [ReferenceDataController::class, 'languages']);
Route::get('/canons/{canon:code}/books', [ReferenceDataController::class, 'canonBooks']);
