<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
CREATE INDEX IF NOT EXISTS verse_texts_text_plain_fts_idx
ON verse_texts
USING GIN (to_tsvector('simple', coalesce(text_plain, '')))
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS verse_texts_text_plain_fts_idx');
    }
};
