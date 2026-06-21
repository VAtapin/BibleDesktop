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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('language_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('telegram_id', 80)->nullable()->unique()->after('password');
            $table->string('telegram_username', 80)->nullable()->after('telegram_id');
            $table->string('avatar_url', 500)->nullable()->after('telegram_username');
            $table->json('settings_json')->nullable()->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropColumn([
                'language_id',
                'telegram_id',
                'telegram_username',
                'avatar_url',
                'settings_json',
            ]);
        });
    }
};
