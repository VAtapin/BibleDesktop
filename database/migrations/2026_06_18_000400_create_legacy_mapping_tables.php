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
        Schema::create('legacy_libraries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->unique();
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('translation_id')->nullable()->constrained()->nullOnDelete();
            $table->json('raw_json')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->unique();
            $table->unsignedBigInteger('legacy_bible_id')->index();
            $table->foreignId('module_book_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('canonical_book_id')->nullable()->constrained()->nullOnDelete();
            $table->json('raw_json')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->unique();
            $table->unsignedBigInteger('legacy_book_id')->index();
            $table->unsignedBigInteger('legacy_bible_id')->index();
            $table->foreignId('module_chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('canonical_chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->json('raw_json')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_verses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->unique();
            $table->unsignedBigInteger('legacy_book_id')->index();
            $table->unsignedBigInteger('legacy_chapter_id')->index();
            $table->unsignedBigInteger('legacy_bible_id')->index();
            $table->foreignId('verse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('verse_text_id')->nullable()->constrained()->nullOnDelete();
            $table->json('raw_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_verses');
        Schema::dropIfExists('legacy_chapters');
        Schema::dropIfExists('legacy_books');
        Schema::dropIfExists('legacy_libraries');
    }
};
