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
        Schema::create('strong_lexicons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 160);
            $table->string('language', 40);
            $table->string('copyright', 240)->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('strong_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strong_lexicon_id')->constrained()->cascadeOnDelete();
            $table->string('number', 16)->unique();
            $table->text('word')->nullable();
            $table->text('transliteration')->nullable();
            $table->text('pronunciation')->nullable();
            $table->text('content')->nullable();
            $table->text('raw_content')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('cross_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_verse_id')->constrained('verses')->cascadeOnDelete();
            $table->foreignId('target_verse_id')->constrained('verses')->cascadeOnDelete();
            $table->string('type', 40)->default('tsk');
            $table->string('source', 80)->default('legacy_quote');
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['source_verse_id', 'target_verse_id', 'type'], 'cross_references_unique');
            $table->index('source_verse_id');
            $table->index('target_verse_id');
        });

        Schema::create('reference_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('type', 40)->default('thematic');
            $table->timestamps();
        });

        Schema::create('reference_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cross_reference_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['reference_group_id', 'cross_reference_id'], 'reference_group_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_group_items');
        Schema::dropIfExists('reference_groups');
        Schema::dropIfExists('cross_references');
        Schema::dropIfExists('strong_entries');
        Schema::dropIfExists('strong_lexicons');
    }
};
