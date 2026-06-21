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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verse_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('start_verse_id')->nullable()->constrained('verses')->cascadeOnDelete();
            $table->foreignId('end_verse_id')->nullable()->constrained('verses')->cascadeOnDelete();
            $table->foreignId('module_book_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('module_chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->foreignId('verse_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('start_verse_id')->nullable()->constrained('verses')->cascadeOnDelete();
            $table->foreignId('end_verse_id')->nullable()->constrained('verses')->cascadeOnDelete();
            $table->string('visibility', 20)->default('private');
            $table->text('body');
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'visibility']);
            $table->index(['verse_id', 'visibility']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 100);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('bookmarks');
    }
};
