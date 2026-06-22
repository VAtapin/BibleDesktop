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
        Schema::create('monastery_services', function (Blueprint $table): void {
            $table->id();
            $table->string('external_uid', 255)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('location', 500)->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('source_url', 500)->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestampTz('imported_at')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'is_public']);
        });

        Schema::create('useful_links', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 220);
            $table->text('description')->nullable();
            $table->string('url', 500);
            $table->string('category', 80)->default('project');
            $table->string('icon', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        DB::table('useful_links')->insert([
            [
                'slug' => 'georg-kloster',
                'title' => 'Свято-Георгиевский монастырь',
                'description' => 'Сайт монастыря, для которого создаётся Bible Desktop.',
                'url' => 'https://georg-kloster.ru/',
                'category' => 'monastery',
                'icon' => 'church',
                'sort_order' => 10,
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'q2me',
                'title' => 'Q-2.me',
                'description' => 'Короткие ссылки и QR-коды Bible Media.',
                'url' => 'https://q-2.me/ru',
                'category' => 'service',
                'icon' => 'link',
                'sort_order' => 20,
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'bible-desktop-bot',
                'title' => 'Telegram-бот Bible Desktop',
                'description' => 'Библия, календарь, молитвы и поиск в Telegram.',
                'url' => 'https://t.me/bibleDesktop_bot',
                'category' => 'app',
                'icon' => 'telegram',
                'sort_order' => 30,
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('useful_links');
        Schema::dropIfExists('monastery_services');
    }
};
