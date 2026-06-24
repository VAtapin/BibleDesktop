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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_blocked')->default(false)->after('is_trusted_recipe_author');
            $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            $table->text('block_reason')->nullable()->after('blocked_at');
            $table->boolean('is_trusted_feed_author')->default(false)->after('block_reason');
        });

        Schema::table('social_posts', function (Blueprint $table): void {
            $table->string('status', 20)->default('published')->after('visibility');
            $table->text('moderation_comment')->nullable()->after('body');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['status', 'moderation_comment']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_blocked', 'blocked_at', 'block_reason', 'is_trusted_feed_author']);
        });
    }
};
