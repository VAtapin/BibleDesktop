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
        Schema::table('recipes', function (Blueprint $table): void {
            $table->unsignedSmallInteger('servings')->default(4)->after('summary');
        });

        Schema::create('recipe_ingredients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name', 220);
            $table->decimal('amount', 10, 3)->nullable();
            $table->string('unit', 40)->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['recipe_id', 'sort_order']);
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->string('answer_type', 40)->default('single')->after('question');
            $table->string('image_path', 500)->nullable()->after('answer_type');
            $table->string('recommendation_type', 40)->nullable()->after('explanation');
            $table->unsignedBigInteger('recommended_prayer_id')->nullable()->after('recommendation_type');
            $table->string('recommended_passage_ref', 120)->nullable()->after('recommended_prayer_id');
            $table->text('recommendation_text')->nullable()->after('recommended_passage_ref');

            $table->foreign('recommended_prayer_id')->references('id')->on('prayers')->nullOnDelete();
        });

        Schema::table('quiz_answers', function (Blueprint $table): void {
            $table->string('recommendation_type', 40)->nullable()->after('is_correct');
            $table->unsignedBigInteger('recommended_prayer_id')->nullable()->after('recommendation_type');
            $table->string('recommended_passage_ref', 120)->nullable()->after('recommended_prayer_id');
            $table->text('recommendation_text')->nullable()->after('recommended_passage_ref');

            $table->foreign('recommended_prayer_id')->references('id')->on('prayers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table): void {
            $table->dropForeign(['recommended_prayer_id']);
            $table->dropColumn([
                'recommendation_type',
                'recommended_prayer_id',
                'recommended_passage_ref',
                'recommendation_text',
            ]);
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->dropForeign(['recommended_prayer_id']);
            $table->dropColumn([
                'answer_type',
                'image_path',
                'recommendation_type',
                'recommended_prayer_id',
                'recommended_passage_ref',
                'recommendation_text',
            ]);
        });

        Schema::dropIfExists('recipe_ingredients');

        Schema::table('recipes', function (Blueprint $table): void {
            $table->dropColumn('servings');
        });
    }
};
