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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 180)->unique();
            $table->string('menu_label', 120)->nullable();
            $table->string('menu_location', 40)->default('footer');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['menu_location', 'is_published', 'sort_order']);
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visibility', 30)->default('followers');
            $table->text('body');
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['visibility', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        $now = now();
        DB::table('cms_pages')->insert([
            [
                'title' => 'Информация',
                'slug' => 'information',
                'menu_label' => 'Информация',
                'menu_location' => 'footer',
                'excerpt' => null,
                'content' => 'Информационная страница Bible Desktop.',
                'sort_order' => 10,
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'О проекте',
                'slug' => 'about',
                'menu_label' => 'О проекте',
                'menu_location' => 'footer',
                'excerpt' => null,
                'content' => 'Описание проекта Bible Desktop.',
                'sort_order' => 20,
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Impressum',
                'slug' => 'impressum',
                'menu_label' => 'Impressum',
                'menu_location' => 'footer',
                'excerpt' => null,
                'content' => 'Impressum.',
                'sort_order' => 30,
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Контакты',
                'slug' => 'contacts',
                'menu_label' => 'Контакты',
                'menu_location' => 'footer',
                'excerpt' => null,
                'content' => 'Контактная информация.',
                'sort_order' => 40,
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('cms_pages');
    }
};
