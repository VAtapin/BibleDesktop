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

namespace App\Services\Bible;

use App\Support\BibleReferenceFormatter;
use App\Support\StrongText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolves OSIS-like passage references to readable Bible text.
 */
class PassageTextService
{
    /**
     * Return plain verse lines for a passage reference such as John.1.1-17.
     *
     * @return Collection<int, array{reference: string, text: string}>
     */
    public function verses(string $passageRef, string $translationCode, int $limit = 80): Collection
    {
        $references = $this->parsePassageList($passageRef);

        if ($references === [] || $translationCode === '') {
            return collect();
        }

        $remaining = max(1, $limit);
        $verses = collect();

        foreach ($references as $reference) {
            if ($remaining < 1) {
                break;
            }

            $rows = $this->queryReference($reference, $translationCode, $remaining);
            $remaining -= $rows->count();
            $verses = $verses->concat($rows);
        }

        return $verses->values();
    }

    public function plainText(string $passageRef, string $translationCode, int $limit = 80): string
    {
        return $this->verses($passageRef, $translationCode, $limit)
            ->map(fn (array $verse): string => "{$verse['reference']} {$verse['text']}")
            ->implode("\n");
    }

    public function bodyText(string $passageRef, string $translationCode, int $limit = 80): string
    {
        return $this->verses($passageRef, $translationCode, $limit)
            ->map(fn (array $verse): string => $verse['text'])
            ->implode("\n");
    }

    /**
     * @return list<array{book: string, start_chapter: int, start_verse: int, end_chapter: int, end_verse: int}>
     */
    private function parsePassageList(string $passageRef): array
    {
        $items = preg_split('/\s*;\s*/u', trim($passageRef), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $references = [];

        foreach ($items as $item) {
            $reference = $this->parseReference($item);

            if ($reference !== null) {
                $references[] = $reference;
            }
        }

        return $references;
    }

    /**
     * @return array{book: string, start_chapter: int, start_verse: int, end_chapter: int, end_verse: int}|null
     */
    private function parseReference(string $value): ?array
    {
        $value = trim($value);

        if (! preg_match('/^([1-3]?[A-Za-z]+)\.(\d+)\.(\d+)(?:-(?:(\d+)\.)?(\d+))?$/u', $value, $matches)) {
            return null;
        }

        $startChapter = (int) $matches[2];
        $startVerse = (int) $matches[3];
        $endChapter = isset($matches[4]) && $matches[4] !== '' ? (int) $matches[4] : $startChapter;
        $endVerse = isset($matches[5]) && $matches[5] !== '' ? (int) $matches[5] : $startVerse;

        return [
            'book' => $matches[1],
            'start_chapter' => $startChapter,
            'start_verse' => $startVerse,
            'end_chapter' => $endChapter,
            'end_verse' => $endVerse,
        ];
    }

    /**
     * @param  array{book: string, start_chapter: int, start_verse: int, end_chapter: int, end_verse: int}  $reference
     * @return Collection<int, array{reference: string, text: string}>
     */
    private function queryReference(array $reference, string $translationCode, int $limit): Collection
    {
        return DB::table('verse_texts')
            ->join('translations', 'translations.id', '=', 'verse_texts.translation_id')
            ->join('module_books', 'module_books.id', '=', 'verse_texts.module_book_id')
            ->join('verses', 'verses.id', '=', 'verse_texts.verse_id')
            ->join('canonical_books', 'canonical_books.id', '=', 'verses.canonical_book_id')
            ->where('translations.code', $translationCode)
            ->where('canonical_books.osis_code', $reference['book'])
            ->where(function ($query) use ($reference): void {
                if ($reference['start_chapter'] === $reference['end_chapter']) {
                    $query
                        ->where('verses.chapter_number', $reference['start_chapter'])
                        ->whereBetween('verses.verse_number', [$reference['start_verse'], $reference['end_verse']]);

                    return;
                }

                $query
                    ->where(function ($chapterQuery) use ($reference): void {
                        $chapterQuery
                            ->where('verses.chapter_number', $reference['start_chapter'])
                            ->where('verses.verse_number', '>=', $reference['start_verse']);
                    })
                    ->orWhere(function ($chapterQuery) use ($reference): void {
                        $chapterQuery
                            ->where('verses.chapter_number', '>', $reference['start_chapter'])
                            ->where('verses.chapter_number', '<', $reference['end_chapter']);
                    })
                    ->orWhere(function ($chapterQuery) use ($reference): void {
                        $chapterQuery
                            ->where('verses.chapter_number', $reference['end_chapter'])
                            ->where('verses.verse_number', '<=', $reference['end_verse']);
                    });
            })
            ->orderBy('verses.chapter_number')
            ->orderBy('verses.verse_number')
            ->limit(max(1, $limit))
            ->get([
                'canonical_books.osis_code',
                'module_books.name as book_name',
                'verses.chapter_number',
                'verses.verse_number',
                'verse_texts.text',
            ])
            ->map(fn ($row): array => [
                'reference' => BibleReferenceFormatter::format(
                    (string) $row->book_name,
                    (string) $row->osis_code,
                    (int) $row->chapter_number,
                    (int) $row->verse_number,
                ),
                'text' => $this->cleanText((string) $row->text),
            ]);
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = StrongText::textWithoutNumbers($text);
        $text = preg_replace('/\s+([,.;:!?»])/u', '$1', $text) ?? $text;
        $text = preg_replace('/([«])\s+/u', '$1', $text) ?? $text;

        return preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);
    }
}
