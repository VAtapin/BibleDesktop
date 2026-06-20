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
            ->join('modules', 'modules.id', '=', 'translations.module_id')
            ->where('translations.code', $translationCode)
            ->where('modules.type', 'bible')
            ->first(['translations.id', 'translations.code', 'translations.name', 'translations.short_name']);

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
                'id' => (int) $verse->id,
                'number' => (int) $verse->verse_number,
                'osis_ref' => $verse->osis_ref,
                'text' => $verse->text,
                'has_strong_markup' => (bool) $verse->has_strong_markup,
                'strong_tokens' => [],
            ]);

        $tokensByVerse = DB::table('verse_strong_tokens')
            ->leftJoin('strong_entries', 'strong_entries.id', '=', 'verse_strong_tokens.strong_entry_id')
            ->whereIn('verse_strong_tokens.verse_id', $verses->pluck('id')->all())
            ->orderBy('verse_strong_tokens.token_order')
            ->get([
                'verse_strong_tokens.id',
                'verse_strong_tokens.verse_id',
                'verse_strong_tokens.strong_number',
                'verse_strong_tokens.token_order',
                'verse_strong_tokens.surface_text',
                'verse_strong_tokens.grammar_code',
                'strong_entries.word',
                'strong_entries.transliteration',
            ])
            ->groupBy('verse_id')
            ->map(fn ($tokens) => $tokens->map(fn ($token) => [
                'id' => (int) $token->id,
                'strong_number' => $token->strong_number,
                'token_order' => (int) $token->token_order,
                'surface_text' => $token->surface_text,
                'grammar_code' => $token->grammar_code,
                'entry' => [
                    'word' => $token->word,
                    'transliteration' => $token->transliteration,
                ],
            ])->values());

        $verses = $verses
            ->map(function (array $verse) use ($tokensByVerse): array {
                $verse['strong_tokens'] = $tokensByVerse[$verse['id']] ?? collect();

                return $verse;
            });

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
