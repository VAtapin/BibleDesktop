<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\BibleReferenceFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudyDataController extends Controller
{
    public function strongEntry(string $number): JsonResponse
    {
        $entry = DB::table('strong_entries')
            ->join('strong_lexicons', 'strong_lexicons.id', '=', 'strong_entries.strong_lexicon_id')
            ->where('strong_entries.number', $number)
            ->first([
                'strong_entries.id',
                'strong_entries.number',
                'strong_entries.word',
                'strong_entries.transliteration',
                'strong_entries.pronunciation',
                'strong_entries.content',
                'strong_entries.raw_content',
                'strong_lexicons.code as lexicon_code',
                'strong_lexicons.name as lexicon_name',
                'strong_lexicons.language as lexicon_language',
            ]);

        if (! $entry) {
            abort(404, 'Strong entry not found.');
        }

        return response()->json([
            'data' => [
                'id' => $entry->id,
                'number' => $entry->number,
                'word' => $entry->word,
                'transliteration' => $entry->transliteration,
                'pronunciation' => $entry->pronunciation,
                'content' => $entry->content,
                'raw_content' => $entry->raw_content,
                'lexicon' => [
                    'code' => $entry->lexicon_code,
                    'name' => $entry->lexicon_name,
                    'language' => $entry->lexicon_language,
                ],
            ],
        ]);
    }

    public function verseStrongTokens(int $verse): JsonResponse
    {
        $sourceVerse = DB::table('verses')->where('id', $verse)->first(['id', 'osis_ref']);

        if (! $sourceVerse) {
            abort(404, 'Verse not found.');
        }

        $tokens = DB::table('verse_strong_tokens')
            ->leftJoin('strong_entries', 'strong_entries.id', '=', 'verse_strong_tokens.strong_entry_id')
            ->where('verse_strong_tokens.verse_id', $verse)
            ->orderBy('verse_strong_tokens.token_order')
            ->get([
                'verse_strong_tokens.id',
                'verse_strong_tokens.strong_number',
                'verse_strong_tokens.token_order',
                'verse_strong_tokens.surface_text',
                'verse_strong_tokens.grammar_code',
                'strong_entries.word',
                'strong_entries.transliteration',
            ])
            ->map(fn ($token) => [
                'id' => $token->id,
                'strong_number' => $token->strong_number,
                'token_order' => $token->token_order,
                'surface_text' => $token->surface_text,
                'grammar_code' => $token->grammar_code,
                'entry' => [
                    'word' => $token->word,
                    'transliteration' => $token->transliteration,
                ],
            ]);

        return response()->json([
            'data' => [
                'verse' => [
                    'id' => $sourceVerse->id,
                    'osis_ref' => $sourceVerse->osis_ref,
                ],
                'tokens' => $tokens,
            ],
        ]);
    }

    public function verseCrossReferences(Request $request, int $verse): JsonResponse
    {
        $translationCode = (string) $request->query('translation', 'L1_RST');
        $sourceVerse = DB::table('verses')->where('id', $verse)->first(['id', 'osis_ref']);

        if (! $sourceVerse) {
            abort(404, 'Verse not found.');
        }

        $translationId = DB::table('translations')->where('code', $translationCode)->value('id');

        $references = DB::table('cross_references')
            ->join('verses as target_verses', 'target_verses.id', '=', 'cross_references.target_verse_id')
            ->join('canonical_books', 'canonical_books.id', '=', 'target_verses.canonical_book_id')
            ->leftJoin('verse_texts', function ($join) use ($translationId): void {
                $join->on('verse_texts.verse_id', '=', 'target_verses.id');

                if ($translationId) {
                    $join->where('verse_texts.translation_id', '=', $translationId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('module_books', function ($join) use ($translationId): void {
                $join->on('module_books.canonical_book_id', '=', 'canonical_books.id');

                if ($translationId) {
                    $join->where('module_books.translation_id', '=', $translationId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->where('cross_references.source_verse_id', $verse)
            ->orderBy('target_verses.osis_ref')
            ->get([
                'cross_references.id',
                'cross_references.type',
                'cross_references.source',
                'cross_references.metadata_json',
                'target_verses.id as target_verse_id',
                'target_verses.osis_ref as target_osis_ref',
                'target_verses.chapter_number',
                'target_verses.verse_number',
                'canonical_books.osis_code',
                'canonical_books.slug as book_slug',
                'module_books.name as book_name',
                'module_books.short_name as book_short_name',
                'verse_texts.text',
            ])
            ->map(fn ($reference) => [
                'id' => $reference->id,
                'type' => $reference->type,
                'source' => $reference->source,
                'metadata' => $reference->metadata_json ? json_decode((string) $reference->metadata_json, true) : null,
                'target' => [
                    'verse_id' => $reference->target_verse_id,
                    'osis_ref' => $reference->target_osis_ref,
                    'reference' => BibleReferenceFormatter::format(
                        $reference->book_name,
                        $reference->osis_code,
                        (int) $reference->chapter_number,
                        (int) $reference->verse_number,
                    ),
                    'book_slug' => $reference->book_slug,
                    'book_name' => $reference->book_name,
                    'book_short_name' => $reference->book_short_name,
                    'chapter_number' => $reference->chapter_number,
                    'verse_number' => $reference->verse_number,
                    'text' => $reference->text,
                ],
            ]);

        return response()->json([
            'data' => [
                'verse' => [
                    'id' => $sourceVerse->id,
                    'osis_ref' => $sourceVerse->osis_ref,
                ],
                'translation_code' => $translationCode,
                'references' => $references,
            ],
        ]);
    }
}
