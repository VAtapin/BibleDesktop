<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name', 80);
            $table->string('native_name', 80);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('canons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('canonical_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canon_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 80);
            $table->string('osis_code', 32)->nullable();
            $table->string('testament', 20);
            $table->unsignedSmallInteger('canonical_order');
            $table->unsignedSmallInteger('default_chapters_count')->default(0);
            $table->boolean('is_deuterocanonical')->default(false);
            $table->timestamps();

            $table->unique(['canon_id', 'slug']);
            $table->unique(['canon_id', 'canonical_order']);
        });

        Schema::create('canonical_book_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canonical_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('short_name', 80)->nullable();
            $table->json('aliases_json')->nullable();
            $table->timestamps();

            $table->unique(['canonical_book_id', 'language_id']);
        });

        Schema::create('canonical_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canonical_book_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->unsignedSmallInteger('verses_count')->default(0);
            $table->timestamps();

            $table->unique(['canonical_book_id', 'number']);
        });

        Schema::create('verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canonical_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('canonical_chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('chapter_number');
            $table->unsignedSmallInteger('verse_number');
            $table->string('osis_ref', 80)->nullable()->index();
            $table->timestamps();

            $table->unique(['canonical_book_id', 'chapter_number', 'verse_number']);
            $table->index(['canonical_chapter_id', 'verse_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verses');
        Schema::dropIfExists('canonical_chapters');
        Schema::dropIfExists('canonical_book_names');
        Schema::dropIfExists('canonical_books');
        Schema::dropIfExists('canons');
        Schema::dropIfExists('languages');
    }
};
