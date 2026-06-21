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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_canonical_chapter_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_bible_id')->nullable();
            $table->string('legacy_book_slug', 80);
            $table->unsignedSmallInteger('legacy_chapter_number');
            $table->string('action', 40);
            $table->string('target_book_slug', 80)->nullable();
            $table->unsignedSmallInteger('target_chapter_number')->nullable();
            $table->string('reason', 120)->nullable();
            $table->text('note')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['legacy_bible_id', 'legacy_book_slug', 'legacy_chapter_number'], 'legacy_chapter_override_unique');
            $table->index(['legacy_book_slug', 'legacy_chapter_number']);
            $table->index(['action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_canonical_chapter_overrides');
    }
};
