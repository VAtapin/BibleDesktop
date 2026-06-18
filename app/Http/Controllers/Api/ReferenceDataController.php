<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Canon;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class ReferenceDataController extends Controller
{
    public function languages(): JsonResponse
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['code', 'name', 'native_name']);

        return response()->json(['data' => $languages]);
    }

    public function canonBooks(Canon $canon): JsonResponse
    {
        $books = $canon->books()
            ->with(['names.language'])
            ->get()
            ->map(fn ($book) => [
                'slug' => $book->slug,
                'osis_code' => $book->osis_code,
                'testament' => $book->testament,
                'order' => $book->canonical_order,
                'chapters_count' => $book->default_chapters_count,
                'is_deuterocanonical' => $book->is_deuterocanonical,
                'names' => $book->names->mapWithKeys(fn ($name) => [
                    $name->language->code => [
                        'name' => $name->name,
                        'short_name' => $name->short_name,
                        'aliases' => $name->aliases_json ?? [],
                    ],
                ]),
            ]);

        return response()->json([
            'data' => [
                'code' => $canon->code,
                'name' => $canon->name,
                'books' => $books,
            ],
        ]);
    }
}
