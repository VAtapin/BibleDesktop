<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedLegacyCanonicalOverrides extends Command
{
    protected $signature = 'bible:legacy:seed-canonical-overrides';

    protected $description = 'Seed known legacy canonical chapter override rules.';

    public function handle(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('legacy_canonical_chapter_overrides')) {
            $this->error('Table legacy_canonical_chapter_overrides is missing. Run migrations first.');

            return self::FAILURE;
        }

        $now = now();
        $rows = array_map(fn (array $row): array => [
            ...$row,
            'metadata_json' => isset($row['metadata_json'])
                ? json_encode($row['metadata_json'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $this->rules());

        DB::table('legacy_canonical_chapter_overrides')->upsert(
            $rows,
            ['legacy_bible_id', 'legacy_book_slug', 'legacy_chapter_number'],
            ['action', 'target_book_slug', 'target_chapter_number', 'reason', 'note', 'metadata_json', 'updated_at'],
        );

        $this->components->info(sprintf('Seeded legacy canonical chapter overrides: %d rules.', count($rows)));

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rules(): array
    {
        return [
            ...$this->directMappingRules(),
            ...$this->requiresVerseMappingRules(),
            ...$this->requiresBookMappingRules(),
            ...$this->appendixRules(),
            ...$this->headingRules(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function directMappingRules(): array
    {
        return [
            [
                'legacy_bible_id' => 10,
                'legacy_book_slug' => 'baruch',
                'legacy_chapter_number' => 6,
                'action' => 'map_chapter',
                'target_book_slug' => 'epistle',
                'target_chapter_number' => 1,
                'reason' => 'epistle_of_jeremiah',
                'note' => 'DRB Baruch 6 is the Epistle of Jeremiah in the Orthodox canon seed.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function requiresVerseMappingRules(): array
    {
        return array_map(fn (int $legacyBibleId): array => [
            'legacy_bible_id' => $legacyBibleId,
            'legacy_book_slug' => 'joel',
            'legacy_chapter_number' => 4,
            'action' => 'requires_verse_mapping',
            'target_book_slug' => 'joel',
            'target_chapter_number' => 3,
            'reason' => 'alternate_joel_versification',
            'note' => 'Four-chapter Joel versification needs verse-level mapping before import.',
        ], [3, 5, 325, 359]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function requiresBookMappingRules(): array
    {
        return [
            [
                'legacy_bible_id' => 3,
                'legacy_book_slug' => '2thessalonians',
                'legacy_chapter_number' => 4,
                'action' => 'requires_book_mapping',
                'target_book_slug' => '1timothy',
                'target_chapter_number' => 1,
                'reason' => 'legacy_ukr_misaligned_chapter',
                'note' => 'Legacy UKR stores 1 Timothy 1 text as an extra 2 Thessalonians chapter; do not map automatically because the real 1 Timothy chapter also exists.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function appendixRules(): array
    {
        return [
            [
                'legacy_bible_id' => 1,
                'legacy_book_slug' => 'sirach',
                'legacy_chapter_number' => 52,
                'action' => 'appendix',
                'target_book_slug' => null,
                'target_chapter_number' => null,
                'reason' => 'sirach_extra_material',
                'note' => 'Legacy RST Sirach 52 is additional material outside the 51-chapter canonical seed.',
            ],
            [
                'legacy_bible_id' => 4,
                'legacy_book_slug' => 'psalms',
                'legacy_chapter_number' => 151,
                'action' => 'appendix',
                'target_book_slug' => null,
                'target_chapter_number' => null,
                'reason' => 'psalm_151_extra_material',
                'note' => 'Psalm 151 is not represented in the current 150-chapter Psalms seed.',
            ],
            [
                'legacy_bible_id' => 4,
                'legacy_book_slug' => 'psalms',
                'legacy_chapter_number' => 152,
                'action' => 'appendix',
                'target_book_slug' => null,
                'target_chapter_number' => null,
                'reason' => 'psalm_152_extra_material',
                'note' => 'Psalm 152 is not represented in the current 150-chapter Psalms seed.',
            ],
            [
                'legacy_bible_id' => 336,
                'legacy_book_slug' => 'psalms',
                'legacy_chapter_number' => 151,
                'action' => 'appendix',
                'target_book_slug' => null,
                'target_chapter_number' => null,
                'reason' => 'psalm_151_extra_material',
                'note' => 'Psalm 151 is not represented in the current 150-chapter Psalms seed.',
            ],
            [
                'legacy_bible_id' => 325,
                'legacy_book_slug' => 'esther',
                'legacy_chapter_number' => 11,
                'action' => 'appendix',
                'target_book_slug' => null,
                'target_chapter_number' => null,
                'reason' => 'esther_greek_addition',
                'note' => 'Legacy Esther 11 is Greek/additional material and needs a dedicated non-standard text model.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function headingRules(): array
    {
        $newTestamentSlugs = [
            'matthew',
            'mark',
            'luke',
            'john',
            'acts',
            'james',
            '1peter',
            '2peter',
            '1john',
            '2john',
            '3john',
            'jude',
            'romans',
            '1corinthians',
            '2corinthians',
            'galatians',
            'ephesians',
            'philippians',
            'colossians',
            '1thessalonians',
            '2thessalonians',
            '1timothy',
            '2timothy',
            'titus',
            'philemon',
            'hebrews',
            'revelation',
        ];

        return array_map(fn (string $slug): array => [
            'legacy_bible_id' => 492,
            'legacy_book_slug' => $slug,
            'legacy_chapter_number' => 0,
            'action' => 'heading',
            'target_book_slug' => null,
            'target_chapter_number' => null,
            'reason' => 'ibsb_nt_book_intro',
            'note' => 'IBSNT chapter 0 contains book or section introduction text, not canonical verses.',
        ], $newTestamentSlugs);
    }
}
