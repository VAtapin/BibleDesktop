<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BibleCanonSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_base_languages_and_orthodox_canon(): void
    {
        $this->seed(DatabaseSeeder::class);

        $canonId = DB::table('canons')->where('code', 'orthodox')->value('id');

        $this->assertNotNull($canonId);
        $this->assertSame(26, DB::table('languages')->count());
        $this->assertSame(77, DB::table('canonical_books')->where('canon_id', $canonId)->count());
        $this->assertSame(1360, DB::table('canonical_chapters')->count());
        $this->assertDatabaseHas('canonical_books', [
            'canon_id' => $canonId,
            'slug' => 'genesis',
            'canonical_order' => 1,
            'default_chapters_count' => 50,
        ]);
        $this->assertDatabaseHas('canonical_books', [
            'canon_id' => $canonId,
            'slug' => 'tobit',
            'canonical_order' => 77,
            'is_deuterocanonical' => true,
        ]);
    }
}
