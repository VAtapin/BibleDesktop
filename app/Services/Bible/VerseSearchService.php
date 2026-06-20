<?php

namespace App\Services\Bible;

use App\Support\BibleReferenceFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VerseSearchService
{
    /**
     * @return array{mode: string, results: \Illuminate\Support\Collection<int, array<string, mixed>>}
     */
    public function search(string $query, string $translationCode = '', int $limit = 20, array $filters = []): array
    {
        $query = trim($query);
        $translationCode = trim($translationCode);
        $limit = min(50, max(1, $limit));
        $filters['offset'] = max(0, (int) ($filters['offset'] ?? 0));
        $match = $filters['match'] ?? 'all_words';
        $filters['match'] = in_array($match, ['all_words', 'phrase', 'partial', 'fuzzy'], true) ? $match : 'all_words';

        if (mb_strlen($query) < 2) {
            return [
                'mode' => 'text',
                'results' => collect(),
            ];
        }

        $reference = $this->parseReference($query, $translationCode);

        if ($reference) {
            return [
                'mode' => 'reference',
                'results' => $this->referenceResults($reference, $translationCode, $limit, $filters),
            ];
        }

        return [
            'mode' => 'text',
            'results' => $this->textResults($query, $translationCode, $limit, $filters),
        ];
    }

    private function textResults(string $query, string $translationCode, int $limit, array $filters = []): Collection
    {
        if (($filters['match'] ?? 'all_words') === 'fuzzy') {
            return $this->fuzzyTextResults($query, $translationCode, $limit, $filters);
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            return $this->postgresTextResults($query, $translationCode, $limit, $filters);
        }

        $tokens = $this->queryTokens($query);
        $builder = $this->baseQuery($translationCode, $filters);

        if (($filters['match'] ?? 'all_words') === 'phrase') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%';
            $builder->where('verse_texts.text_plain', 'like', $like);
        } else {
            foreach ($tokens as $token) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $token).'%';
                $builder->where('verse_texts.text_plain', 'like', $like);
            }
        }

        return $builder
            ->orderBy('translations.code')
            ->orderBy('canonical_books.canonical_order')
            ->orderBy('verses.chapter_number')
            ->orderBy('verses.verse_number')
            ->offset((int) ($filters['offset'] ?? 0))
            ->limit($limit)
            ->get($this->resultColumns())
            ->map(fn ($row) => $this->mapResult($row, $this->snippet((string) $row->text_plain, $query), $query));
    }

    private function postgresTextResults(string $query, string $translationCode, int $limit, array $filters = []): Collection
    {
        if (in_array(($filters['match'] ?? 'all_words'), ['phrase', 'partial'], true)) {
            $tokens = ($filters['match'] ?? 'all_words') === 'phrase' ? [$query] : $this->queryTokens($query);
            $builder = $this->baseQuery($translationCode, $filters)
                ->select($this->resultColumns());

            foreach ($tokens as $token) {
                $builder->where('verse_texts.text_plain', 'ilike', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $token).'%');
            }

            return $builder
                ->orderBy('translations.code')
                ->orderBy('canonical_books.canonical_order')
                ->orderBy('verses.chapter_number')
                ->orderBy('verses.verse_number')
                ->offset((int) ($filters['offset'] ?? 0))
                ->limit($limit)
                ->get()
                ->map(fn ($row) => $this->mapResult($row, $this->snippet((string) $row->text_plain, $query), $query));
        }

        $headlineSql = "ts_headline('simple', verse_texts.text_plain, plainto_tsquery('simple', ?), 'MaxWords=32, MinWords=10, StartSel=<<bd>>, StopSel=<</bd>>')";
        $rankSql = "ts_rank(to_tsvector('simple', coalesce(verse_texts.text_plain, '')), plainto_tsquery('simple', ?))";

        return $this->baseQuery($translationCode, $filters)
            ->select($this->resultColumns())
            ->selectRaw("{$headlineSql} as snippet", [$query])
            ->selectRaw("{$rankSql} as rank", [$query])
            ->whereRaw("to_tsvector('simple', coalesce(verse_texts.text_plain, '')) @@ plainto_tsquery('simple', ?)", [$query])
            ->orderByDesc('rank')
            ->orderBy('translations.code')
            ->orderBy('canonical_books.canonical_order')
            ->orderBy('verses.chapter_number')
            ->orderBy('verses.verse_number')
            ->offset((int) ($filters['offset'] ?? 0))
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->mapResult($row, (string) $row->snippet, $query, true));
    }

    private function fuzzyTextResults(string $query, string $translationCode, int $limit, array $filters = []): Collection
    {
        $queryTokens = $this->queryTokens($query);

        if ($queryTokens === []) {
            return collect();
        }

        return $this->baseQuery($translationCode, $filters)
            ->orderBy('translations.code')
            ->orderBy('canonical_books.canonical_order')
            ->orderBy('verses.chapter_number')
            ->orderBy('verses.verse_number')
            ->get($this->resultColumns())
            ->map(function ($row) use ($query, $queryTokens): ?array {
                $score = $this->fuzzyScore((string) $row->text_plain, $queryTokens);

                if ($score === null) {
                    return null;
                }

                return [
                    'score' => $score,
                    'row' => $this->mapResult($row, $this->snippet((string) $row->text_plain, $query), $query),
                ];
            })
            ->filter()
            ->sortBy('score')
            ->slice((int) ($filters['offset'] ?? 0), $limit)
            ->values()
            ->map(fn (array $item) => $item['row']);
    }

    /**
     * @param array{book_slug: string, chapter: int, verse: int} $reference
     */
    private function referenceResults(array $reference, string $translationCode, int $limit, array $filters = []): Collection
    {
        return $this->baseQuery($translationCode, $filters)
            ->where('canonical_books.slug', $reference['book_slug'])
            ->where('verses.chapter_number', $reference['chapter'])
            ->where('verses.verse_number', $reference['verse'])
            ->orderBy('translations.code')
            ->offset((int) ($filters['offset'] ?? 0))
            ->limit($limit)
            ->get($this->resultColumns())
            ->map(fn ($row) => $this->mapResult($row, mb_substr((string) $row->text_plain, 0, 160), ''));
    }

    private function baseQuery(string $translationCode, array $filters = []): \Illuminate\Database\Query\Builder
    {
        return DB::table('verse_texts')
            ->join('translations', 'translations.id', '=', 'verse_texts.translation_id')
            ->join('modules', 'modules.id', '=', 'translations.module_id')
            ->join('module_books', 'module_books.id', '=', 'verse_texts.module_book_id')
            ->join('verses', 'verses.id', '=', 'verse_texts.verse_id')
            ->join('canonical_books', 'canonical_books.id', '=', 'verses.canonical_book_id')
            ->where('modules.type', 'bible')
            ->when((bool) ($filters['canonical_only'] ?? false), fn ($builder) => $builder->where('canonical_books.is_deuterocanonical', false))
            ->when((bool) ($filters['deuterocanonical_only'] ?? false), fn ($builder) => $builder->where('canonical_books.is_deuterocanonical', true))
            ->when(($filters['scope'] ?? 'all') === 'old', fn ($builder) => $builder->where('canonical_books.testament', 'old'))
            ->when(($filters['scope'] ?? 'all') === 'new', fn ($builder) => $builder->where('canonical_books.testament', 'new'))
            ->when(($filters['scope'] ?? 'all') === 'psalms', fn ($builder) => $builder->where('canonical_books.slug', 'psalms'))
            ->when($translationCode !== '', fn ($builder) => $builder->where('translations.code', $translationCode));
    }

    /**
     * @return list<string>
     */
    private function resultColumns(): array
    {
        return [
            'verse_texts.id as verse_text_id',
            'verse_texts.text',
            'verse_texts.text_plain',
            'translations.code as translation_code',
            'translations.short_name as translation_short_name',
            'verses.id as verse_id',
            'verses.osis_ref',
            'verses.chapter_number',
            'verses.verse_number',
            'canonical_books.slug as book_slug',
            'canonical_books.osis_code',
            'module_books.name as book_name',
            'module_books.short_name as book_short_name',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapResult(object $row, string $snippet, string $query, bool $snippetHasMarkers = false): array
    {
        $snippetData = $snippetHasMarkers
            ? $this->segmentsFromMarkedSnippet($snippet)
            : $this->highlightSnippet($snippet, $query);

        return [
            'verse_text_id' => $row->verse_text_id,
            'verse_id' => $row->verse_id,
            'osis_ref' => $row->osis_ref,
            'reference' => BibleReferenceFormatter::format(
                $row->book_name ?? null,
                $row->osis_code ?? null,
                (int) $row->chapter_number,
                (int) $row->verse_number,
            ),
            'translation' => [
                'code' => $row->translation_code,
                'short_name' => $row->translation_short_name,
            ],
            'book' => [
                'slug' => $row->book_slug,
                'osis_code' => $row->osis_code,
                'name' => $row->book_name,
                'short_name' => $row->book_short_name,
            ],
            'chapter_number' => $row->chapter_number,
            'verse_number' => $row->verse_number,
            'text' => $row->text,
            'snippet' => $snippetData['text'],
            'snippet_segments' => $snippetData['segments'],
        ];
    }

    /**
     * @return array{book_slug: string, chapter: int, verse: int}|null
     */
    private function parseReference(string $query, string $translationCode): ?array
    {
        if (! preg_match('/^\s*(?<book>.+?)[\s.]+(?<chapter>\d{1,3})\s*[:.,]\s*(?<verse>\d{1,3})\s*$/u', $query, $matches)) {
            return null;
        }

        $bookSlug = $this->resolveBookSlug((string) $matches['book'], $translationCode);

        if (! $bookSlug) {
            return null;
        }

        return [
            'book_slug' => $bookSlug,
            'chapter' => (int) $matches['chapter'],
            'verse' => (int) $matches['verse'],
        ];
    }

    private function resolveBookSlug(string $bookQuery, string $translationCode): ?string
    {
        $needle = $this->normalizeBookToken($bookQuery);

        if ($needle === '') {
            return null;
        }

        $candidates = [];
        $addCandidate = function (?string $value, string $slug) use (&$candidates): void {
            if ($value === null || trim($value) === '') {
                return;
            }

            $candidates[$this->normalizeBookToken($value)] ??= $slug;
        };

        DB::table('canonical_books')
            ->leftJoin('canonical_book_names', 'canonical_book_names.canonical_book_id', '=', 'canonical_books.id')
            ->get([
                'canonical_books.slug',
                'canonical_books.osis_code',
                'canonical_book_names.name',
                'canonical_book_names.short_name',
                'canonical_book_names.aliases_json',
            ])
            ->each(function ($book) use ($addCandidate): void {
                $addCandidate($book->slug, $book->slug);
                $addCandidate($book->osis_code, $book->slug);
                $addCandidate($book->name, $book->slug);
                $addCandidate($book->short_name, $book->slug);

                foreach ($this->jsonArray($book->aliases_json) as $alias) {
                    $addCandidate($alias, $book->slug);
                }
            });

        DB::table('module_books')
            ->join('translations', 'translations.id', '=', 'module_books.translation_id')
            ->when($translationCode !== '', fn ($builder) => $builder->where('translations.code', $translationCode))
            ->get([
                'module_books.slug',
                'module_books.name',
                'module_books.short_name',
                'module_books.aliases_json',
            ])
            ->each(function ($book) use ($addCandidate): void {
                $addCandidate($book->slug, $book->slug);
                $addCandidate($book->name, $book->slug);
                $addCandidate($book->short_name, $book->slug);

                foreach ($this->jsonArray($book->aliases_json) as $alias) {
                    $addCandidate($alias, $book->slug);
                }
            });

        return $candidates[$needle] ?? null;
    }

    private function normalizeBookToken(string $value): string
    {
        $value = mb_strtolower(str_replace('ё', 'е', trim($value)));

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';
    }

    /**
     * @return list<string>
     */
    private function jsonArray(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    private function snippet(string $text, string $query): string
    {
        $position = mb_stripos($text, $query);

        if ($position === false) {
            return mb_substr($text, 0, 160);
        }

        $start = max(0, $position - 60);
        $snippet = mb_substr($text, $start, 160);

        return ($start > 0 ? '...' : '').$snippet.(mb_strlen($text) > $start + 160 ? '...' : '');
    }

    /**
     * @return array{text: string, segments: list<array{text: string, match: bool}>}
     */
    private function highlightSnippet(string $snippet, string $query): array
    {
        $tokens = $this->queryTokens($query);

        if ($tokens === []) {
            return [
                'text' => $snippet,
                'segments' => [['text' => $snippet, 'match' => false]],
            ];
        }

        $pattern = '/('.implode('|', array_map(fn (string $token): string => preg_quote($token, '/'), $tokens)).')/iu';
        $parts = preg_split($pattern, $snippet, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts)) {
            return [
                'text' => $snippet,
                'segments' => [['text' => $snippet, 'match' => false]],
            ];
        }

        return [
            'text' => $snippet,
            'segments' => array_map(fn (string $part): array => [
                'text' => $part,
                'match' => $this->isTokenMatch($part, $tokens),
            ], $parts),
        ];
    }

    /**
     * @return array{text: string, segments: list<array{text: string, match: bool}>}
     */
    private function segmentsFromMarkedSnippet(string $snippet): array
    {
        $segments = [];
        $plain = '';
        $parts = preg_split('/(<<bd>>|<<\/bd>>)/u', $snippet, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $isMatch = false;

        foreach (is_array($parts) ? $parts : [$snippet] as $part) {
            if ($part === '<<bd>>') {
                $isMatch = true;
                continue;
            }

            if ($part === '<</bd>>') {
                $isMatch = false;
                continue;
            }

            $plain .= $part;
            $segments[] = [
                'text' => $part,
                'match' => $isMatch,
            ];
        }

        return [
            'text' => $plain,
            'segments' => $segments === [] ? [['text' => $plain, 'match' => false]] : $segments,
        ];
    }

    /**
     * @return list<string>
     */
    private function queryTokens(string $query): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($tokens, fn (string $token): bool => mb_strlen($token) >= 2)));
    }

    /**
     * @param list<string> $queryTokens
     */
    private function fuzzyScore(string $text, array $queryTokens): ?int
    {
        $textTokens = $this->queryTokens(str_replace('ё', 'е', $text));
        $score = 0;

        foreach ($queryTokens as $queryToken) {
            $best = null;

            foreach ($textTokens as $textToken) {
                $distance = $this->mbLevenshtein($queryToken, $textToken);
                $threshold = mb_strlen($queryToken) <= 5 ? 2 : max(2, (int) floor(mb_strlen($queryToken) * 0.3));

                if ($distance <= $threshold || str_contains($textToken, $queryToken)) {
                    $best = min($best ?? $distance, $distance);
                }
            }

            if ($best === null) {
                return null;
            }

            $score += $best;
        }

        return $score;
    }

    private function mbLevenshtein(string $a, string $b): int
    {
        $aChars = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $bChars = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $previous = range(0, count($bChars));

        foreach ($aChars as $i => $aChar) {
            $current = [$i + 1];

            foreach ($bChars as $j => $bChar) {
                $current[$j + 1] = min(
                    $current[$j] + 1,
                    $previous[$j + 1] + 1,
                    $previous[$j] + ($aChar === $bChar ? 0 : 1),
                );
            }

            $previous = $current;
        }

        return $previous[count($bChars)] ?? count($aChars);
    }

    /**
     * @param list<string> $tokens
     */
    private function isTokenMatch(string $part, array $tokens): bool
    {
        $part = mb_strtolower($part);

        foreach ($tokens as $token) {
            if ($part === $token) {
                return true;
            }
        }

        return false;
    }
}
