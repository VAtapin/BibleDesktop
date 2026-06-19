<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Canon;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    public function translations(): JsonResponse
    {
        $translations = DB::table('translations')
            ->join('languages', 'languages.id', '=', 'translations.language_id')
            ->leftJoin('canons', 'canons.id', '=', 'translations.canon_id')
            ->join('modules', 'modules.id', '=', 'translations.module_id')
            ->where('modules.is_active', true)
            ->orderByDesc('translations.is_default')
            ->orderBy('languages.sort_order')
            ->orderBy('translations.name')
            ->get([
                'translations.code',
                'translations.name',
                'translations.short_name',
                'translations.has_old_testament',
                'translations.has_new_testament',
                'translations.has_apocrypha',
                'translations.has_strong',
                'translations.is_default',
                'languages.code as language_code',
                'languages.native_name as language_name',
                'canons.code as canon_code',
                'modules.code as module_code',
            ])
            ->map(fn ($translation) => [
                'code' => $translation->code,
                'name' => $translation->name,
                'short_name' => $translation->short_name,
                'language' => [
                    'code' => $translation->language_code,
                    'name' => $translation->language_name,
                ],
                'canon_code' => $translation->canon_code,
                'module_code' => $translation->module_code,
                'has_old_testament' => (bool) $translation->has_old_testament,
                'has_new_testament' => (bool) $translation->has_new_testament,
                'has_apocrypha' => (bool) $translation->has_apocrypha,
                'has_strong' => (bool) $translation->has_strong,
                'is_default' => (bool) $translation->is_default,
            ]);

        return response()->json(['data' => $translations]);
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
