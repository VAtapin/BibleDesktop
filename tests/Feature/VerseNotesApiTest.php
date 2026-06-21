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

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VerseNotesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_lists_private_demo_user_verse_notes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $verseId = $this->createVerseFixture();

        $this->postJson("/api/verses/{$verseId}/notes", [
            'body' => 'Важная мысль к стиху.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.note.body', 'Важная мысль к стиху.')
            ->assertJsonPath('data.note.visibility', 'private');

        $this->getJson("/api/verses/{$verseId}/notes")
            ->assertOk()
            ->assertJsonCount(1, 'data.notes')
            ->assertJsonPath('data.notes.0.body', 'Важная мысль к стиху.');

        $this->assertDatabaseHas('users', [
            'email' => 'demo@bibledesktop.local',
        ]);
        $this->assertSame(1, DB::table('notes')->where('verse_id', $verseId)->count());
    }

    private function createVerseFixture(): int
    {
        $now = now();
        $bookId = DB::table('canonical_books')->where('slug', 'genesis')->value('id');
        $chapterId = DB::table('canonical_chapters')
            ->where('canonical_book_id', $bookId)
            ->where('number', 1)
            ->value('id');

        DB::table('verses')->insert([
            'canonical_book_id' => $bookId,
            'canonical_chapter_id' => $chapterId,
            'chapter_number' => 1,
            'verse_number' => 1,
            'osis_ref' => 'Gen.1.1',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) DB::table('verses')->where('osis_ref', 'Gen.1.1')->value('id');
    }
}
