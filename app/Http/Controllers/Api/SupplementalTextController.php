<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplementalTextController extends Controller
{
    public function index(Request $request, string $translationCode): JsonResponse
    {
        $translation = DB::table('translations')
            ->join('modules', 'modules.id', '=', 'translations.module_id')
            ->where('translations.code', $translationCode)
            ->where('modules.type', 'bible')
            ->first(['translations.id', 'translations.code', 'translations.name', 'translations.short_name']);

        if (! $translation) {
            abort(404, 'Translation not found.');
        }

        $bookSlug = trim((string) $request->query('book', ''));
        $type = trim((string) $request->query('type', ''));
        $limit = min(200, max(1, (int) $request->query('limit', 50)));

        $items = DB::table('legacy_supplemental_texts')
            ->where('translation_id', $translation->id)
            ->when($bookSlug !== '', fn ($query) => $query->where('legacy_book_slug', $bookSlug))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy('legacy_book_slug')
            ->orderBy('legacy_chapter_number')
            ->orderBy('legacy_verse_number')
            ->limit($limit)
            ->get([
                'id',
                'legacy_verse_id',
                'legacy_book_slug',
                'legacy_chapter_number',
                'legacy_verse_number',
                'type',
                'title',
                'text',
                'text_plain',
            ])
            ->map(fn ($item) => [
                'id' => $item->id,
                'legacy_verse_id' => $item->legacy_verse_id,
                'book' => [
                    'slug' => $item->legacy_book_slug,
                ],
                'chapter_number' => (int) $item->legacy_chapter_number,
                'verse_number' => (int) $item->legacy_verse_number,
                'type' => $item->type,
                'title' => $item->title,
                'text' => $item->text,
                'text_plain' => $item->text_plain,
            ]);

        return response()->json([
            'data' => [
                'translation' => [
                    'code' => $translation->code,
                    'name' => $translation->name,
                    'short_name' => $translation->short_name,
                ],
                'filters' => [
                    'book' => $bookSlug ?: null,
                    'type' => $type ?: null,
                    'limit' => $limit,
                ],
                'items' => $items,
            ],
        ]);
    }
}
