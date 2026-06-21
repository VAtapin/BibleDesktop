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

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeedLegacyCanonicalOverridesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_known_legacy_canonical_override_rules_idempotently(): void
    {
        $this->artisan('bible:legacy:seed-canonical-overrides')
            ->expectsOutputToContain('Seeded legacy canonical chapter overrides: 38 rules.')
            ->expectsOutputToContain('Seeded legacy canonical verse overrides: 104 rules.')
            ->assertSuccessful();

        $this->assertSame(38, DB::table('legacy_canonical_chapter_overrides')->count());
        $this->assertSame(104, DB::table('legacy_canonical_verse_overrides')->count());
        $this->assertDatabaseHas('legacy_canonical_chapter_overrides', [
            'legacy_bible_id' => 10,
            'legacy_book_slug' => 'baruch',
            'legacy_chapter_number' => 6,
            'action' => 'map_chapter',
            'target_book_slug' => 'epistle',
            'target_chapter_number' => 1,
        ]);
        $this->assertDatabaseHas('legacy_canonical_chapter_overrides', [
            'legacy_bible_id' => 3,
            'legacy_book_slug' => 'joel',
            'legacy_chapter_number' => 4,
            'action' => 'requires_verse_mapping',
        ]);
        $this->assertDatabaseHas('legacy_canonical_verse_overrides', [
            'legacy_bible_id' => 3,
            'legacy_book_slug' => 'joel',
            'legacy_chapter_number' => 3,
            'legacy_verse_number' => 1,
            'action' => 'map_verse',
            'target_book_slug' => 'joel',
            'target_chapter_number' => 2,
            'target_verse_number' => 28,
        ]);
        $this->assertDatabaseHas('legacy_canonical_verse_overrides', [
            'legacy_bible_id' => 3,
            'legacy_book_slug' => 'joel',
            'legacy_chapter_number' => 4,
            'legacy_verse_number' => 21,
            'action' => 'map_verse',
            'target_book_slug' => 'joel',
            'target_chapter_number' => 3,
            'target_verse_number' => 21,
        ]);
        $this->assertDatabaseHas('legacy_canonical_chapter_overrides', [
            'legacy_bible_id' => 492,
            'legacy_book_slug' => 'matthew',
            'legacy_chapter_number' => 0,
            'action' => 'heading',
        ]);
        $this->assertDatabaseHas('legacy_canonical_chapter_overrides', [
            'legacy_bible_id' => 3,
            'legacy_book_slug' => '2thessalonians',
            'legacy_chapter_number' => 4,
            'action' => 'requires_book_mapping',
            'target_book_slug' => '1timothy',
            'target_chapter_number' => 1,
        ]);
        $this->assertDatabaseHas('legacy_canonical_chapter_overrides', [
            'legacy_bible_id' => 4,
            'legacy_book_slug' => 'psalms',
            'legacy_chapter_number' => 151,
            'action' => 'appendix',
        ]);

        $this->artisan('bible:legacy:seed-canonical-overrides')->assertSuccessful();

        $this->assertSame(38, DB::table('legacy_canonical_chapter_overrides')->count());
        $this->assertSame(104, DB::table('legacy_canonical_verse_overrides')->count());
    }
}
