<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('code', 40)->unique();
            $table->string('name', 240);
            $table->string('short_name', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('version', 40)->nullable();
            $table->json('metadata_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->restrictOnDelete();
            $table->foreignId('canon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 240);
            $table->string('short_name', 80)->nullable();
            $table->text('copyright')->nullable();
            $table->string('license', 120)->nullable();
            $table->string('source', 240)->nullable();
            $table->boolean('has_old_testament')->default(false);
            $table->boolean('has_new_testament')->default(false);
            $table->boolean('has_apocrypha')->default(false);
            $table->boolean('has_strong')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('module_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('translation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('canonical_book_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_book_id')->nullable()->index();
            $table->string('slug', 80);
            $table->string('name', 160);
            $table->string('short_name', 120)->nullable();
            $table->json('aliases_json')->nullable();
            $table->string('path_name', 120)->nullable();
            $table->unsignedSmallInteger('book_order')->default(0);
            $table->unsignedSmallInteger('chapters_count')->default(0);
            $table->boolean('show_verse_numbers')->default(true);
            $table->timestamps();

            $table->unique(['module_id', 'slug']);
            $table->index(['translation_id', 'book_order']);
        });

        Schema::create('module_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canonical_chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_chapter_id')->nullable()->index();
            $table->unsignedSmallInteger('chapter_number');
            $table->string('title', 160)->nullable();
            $table->unsignedSmallInteger('verses_count')->default(0);
            $table->timestamps();

            $table->unique(['module_book_id', 'chapter_number']);
        });

        Schema::create('verse_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('translation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('legacy_verse_id')->nullable()->index();
            $table->text('text');
            $table->text('text_plain');
            $table->text('text_raw')->nullable();
            $table->boolean('has_strong_markup')->default(false);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['translation_id', 'verse_id']);
            $table->index(['translation_id', 'module_chapter_id']);
            $table->index('verse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verse_texts');
        Schema::dropIfExists('module_chapters');
        Schema::dropIfExists('module_books');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('modules');
    }
};
