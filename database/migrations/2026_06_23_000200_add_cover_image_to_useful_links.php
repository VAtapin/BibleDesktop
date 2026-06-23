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
        if (! Schema::hasTable('useful_links') || Schema::hasColumn('useful_links', 'cover_image_url')) {
            return;
        }

        Schema::table('useful_links', function (Blueprint $table): void {
            $table->string('cover_image_url', 500)->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('useful_links') || ! Schema::hasColumn('useful_links', 'cover_image_url')) {
            return;
        }

        Schema::table('useful_links', function (Blueprint $table): void {
            $table->dropColumn('cover_image_url');
        });
    }
};
