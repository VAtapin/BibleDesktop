<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_supplemental_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('translation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('module_book_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('module_chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_verse_id')->unique();
            $table->unsignedBigInteger('legacy_bible_id')->index();
            $table->unsignedBigInteger('legacy_book_id')->index();
            $table->unsignedBigInteger('legacy_chapter_id')->index();
            $table->string('legacy_book_slug', 80);
            $table->unsignedSmallInteger('legacy_chapter_number');
            $table->unsignedSmallInteger('legacy_verse_number');
            $table->string('type', 40);
            $table->string('title', 160)->nullable();
            $table->text('text');
            $table->text('text_plain');
            $table->text('text_raw')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['translation_id', 'legacy_book_slug', 'legacy_chapter_number'], 'legacy_supplemental_translation_book_chapter_idx');
            $table->index(['type', 'legacy_bible_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_supplemental_texts');
    }
};
