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
        Schema::table('prayers', function (Blueprint $table): void {
            $table->text('intro')->nullable()->after('short_title');
        });
    }

    public function down(): void
    {
        Schema::table('prayers', function (Blueprint $table): void {
            $table->dropColumn('intro');
        });
    }
};
