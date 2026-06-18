<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ChapterController extends Controller
{
    public function show(string $translationCode, string $bookSlug, int $chapter): JsonResponse
    {
        $translation = DB::table('translations')
            ->where('code', $translationCode)
            ->first(['id', 'code', 'name', 'short_name']);

        if (! $translation) {
            abort(404, 'Translation not found.');
        }

        $moduleBook = DB::table('module_books')
            ->where('translation_id', $translation->id)
            ->where('slug', $bookSlug)
            ->first(['id', 'canonical_book_id', 'name', 'short_name', 'chapters_count']);

        if (! $moduleBook) {
            abort(404, 'Book not found.');
        }

        $moduleChapter = DB::table('module_chapters')
            ->where('module_book_id', $moduleBook->id)
            ->where('chapter_number', $chapter)
            ->first(['id', 'canonical_chapter_id', 'chapter_number', 'verses_count']);

        if (! $moduleChapter) {
            abort(404, 'Chapter not found.');
        }

        $verses = DB::table('verse_texts')
            ->join('verses', 'verses.id', '=', 'verse_texts.verse_id')
            ->where('verse_texts.translation_id', $translation->id)
            ->where('verse_texts.module_chapter_id', $moduleChapter->id)
            ->orderBy('verses.verse_number')
            ->get([
                'verses.id',
                'verses.osis_ref',
                'verses.verse_number',
                'verse_texts.text',
                'verse_texts.has_strong_markup',
            ])
            ->map(fn ($verse) => [
                'id' => $verse->id,
                'number' => $verse->verse_number,
                'osis_ref' => $verse->osis_ref,
                'text' => $verse->text,
                'has_strong_markup' => (bool) $verse->has_strong_markup,
            ]);

        return response()->json([
            'data' => [
                'translation' => [
                    'code' => $translation->code,
                    'name' => $translation->name,
                    'short_name' => $translation->short_name,
                ],
                'book' => [
                    'slug' => $bookSlug,
                    'name' => $moduleBook->name,
                    'short_name' => $moduleBook->short_name,
                    'chapters_count' => $moduleBook->chapters_count,
                ],
                'chapter' => [
                    'number' => $moduleChapter->chapter_number,
                    'verses_count' => $moduleChapter->verses_count,
                ],
                'verses' => $verses,
            ],
        ]);
    }
}
