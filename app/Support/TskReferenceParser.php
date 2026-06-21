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

namespace App\Support;

class TskReferenceParser
{
    /**
     * @var array<string, string>
     */
    private const BOOK_ALIASES = [
        'ge' => 'genesis',
        'gen' => 'genesis',
        'ex' => 'exodus',
        'exo' => 'exodus',
        'exod' => 'exodus',
        'le' => 'leviticus',
        'lev' => 'leviticus',
        'nu' => 'numbers',
        'num' => 'numbers',
        'de' => 'deuteronomy',
        'deu' => 'deuteronomy',
        'deut' => 'deuteronomy',
        'jos' => 'joshua',
        'josh' => 'joshua',
        'jdg' => 'judges',
        'judg' => 'judges',
        'ru' => 'ruth',
        'rut' => 'ruth',
        'ruth' => 'ruth',
        '1sa' => '1samuel',
        '1sam' => '1samuel',
        '1 sam' => '1samuel',
        '2sa' => '2samuel',
        '2sam' => '2samuel',
        '2samuel' => '2samuel',
        '1ki' => '1kings',
        '1kgs' => '1kings',
        '2ki' => '2kings',
        '2kgs' => '2kings',
        '1ch' => '1chron',
        '1chr' => '1chron',
        '2ch' => '2chron',
        '2chr' => '2chron',
        'ezr' => 'ezra',
        'ezra' => 'ezra',
        'ne' => 'nehemiah',
        'neh' => 'nehemiah',
        'es' => 'esther',
        'est' => 'esther',
        'esth' => 'esther',
        'job' => 'job',
        'ps' => 'psalms',
        'psa' => 'psalms',
        'pr' => 'proverbs',
        'pro' => 'proverbs',
        'prov' => 'proverbs',
        'ec' => 'ecclesia',
        'ecc' => 'ecclesia',
        'eccl' => 'ecclesia',
        'so' => 'songs',
        'son' => 'songs',
        'song' => 'songs',
        'isa' => 'isaiah',
        'jer' => 'jeremiah',
        'la' => 'lamentations',
        'lam' => 'lamentations',
        'eze' => 'ezekiel',
        'ezek' => 'ezekiel',
        'da' => 'daniel',
        'dan' => 'daniel',
        'ho' => 'hosea',
        'hos' => 'hosea',
        'joe' => 'joel',
        'joel' => 'joel',
        'am' => 'amos',
        'amo' => 'amos',
        'ob' => 'obadiah',
        'oba' => 'obadiah',
        'obad' => 'obadiah',
        'jon' => 'jonah',
        'jonah' => 'jonah',
        'mic' => 'micah',
        'na' => 'nahum',
        'nah' => 'nahum',
        'hab' => 'habakkuk',
        'hag' => 'haggai',
        'zec' => 'zechariah',
        'zech' => 'zechariah',
        'zep' => 'zephaniah',
        'zeph' => 'zephaniah',
        'mal' => 'malachi',
        'mat' => 'matthew',
        'mt' => 'matthew',
        'matt' => 'matthew',
        'mar' => 'mark',
        'mr' => 'mark',
        'mark' => 'mark',
        'lu' => 'luke',
        'luk' => 'luke',
        'luke' => 'luke',
        'joh' => 'john',
        'john' => 'john',
        'ac' => 'acts',
        'act' => 'acts',
        'acts' => 'acts',
        'ro' => 'romans',
        'rom' => 'romans',
        '1co' => '1corinthians',
        '1cor' => '1corinthians',
        '1 cor' => '1corinthians',
        '2co' => '2corinthians',
        '2cor' => '2corinthians',
        'ga' => 'galatians',
        'gal' => 'galatians',
        'eph' => 'ephesians',
        'phi' => 'philippians',
        'php' => 'philippians',
        'phil' => 'philippians',
        'col' => 'colossians',
        '1th' => '1thessalonians',
        '1thess' => '1thessalonians',
        '2th' => '2thessalonians',
        '2thess' => '2thessalonians',
        '1ti' => '1timothy',
        '1tim' => '1timothy',
        '1 tim' => '1timothy',
        '2ti' => '2timothy',
        '2tim' => '2timothy',
        'tit' => 'titus',
        'phm' => 'philemon',
        'heb' => 'hebrews',
        'jam' => 'james',
        'jas' => 'james',
        '1pe' => '1peter',
        '1pet' => '1peter',
        '1 pet' => '1peter',
        '2pe' => '2peter',
        '2pet' => '2peter',
        '2 pet' => '2peter',
        '1jo' => '1john',
        '1john' => '1john',
        '2jo' => '2john',
        '2john' => '2john',
        '3jo' => '3john',
        '3john' => '3john',
        'jude' => 'jude',
        're' => 'revelation',
        'rev' => 'revelation',
    ];

    /**
     * @return array{references: list<array{book_slug: string, chapter: int, verse_start: int, verse_end: int, raw: string}>, skipped: int}
     */
    public function parseList(string $value): array
    {
        $references = [];
        $skipped = 0;
        $parts = preg_split('/;/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($parts as $part) {
            $reference = $this->parse(trim($part));

            if ($reference) {
                $references[] = $reference;

                continue;
            }

            $skipped++;
        }

        return [
            'references' => $references,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{book_slug: string, chapter: int, verse_start: int, verse_end: int, raw: string}|null
     */
    public function parse(string $value): ?array
    {
        $raw = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        if (! preg_match('/^([1-3]?\s?[A-Za-z]+)\s+(\d+):(\d+)(?:-(\d+))?$/u', $raw, $matches)) {
            return null;
        }

        $alias = mb_strtolower($matches[1]);
        $slug = self::BOOK_ALIASES[$alias] ?? null;

        if (! $slug) {
            return null;
        }

        $verseStart = (int) $matches[3];
        $verseEnd = isset($matches[4]) ? (int) $matches[4] : $verseStart;

        if ($verseEnd < $verseStart) {
            return null;
        }

        return [
            'book_slug' => $slug,
            'chapter' => (int) $matches[2],
            'verse_start' => $verseStart,
            'verse_end' => $verseEnd,
            'raw' => $raw,
        ];
    }
}
