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
        Schema::table('calendar_event_types', function (Blueprint $table): void {
            $table->integer('legacy_type')->nullable()->unique()->after('code');
            $table->string('typicon_symbol', 20)->nullable()->after('name');
            $table->string('color', 20)->nullable()->after('typicon_symbol');
            $table->boolean('is_fasting')->default(false)->after('color');
            $table->boolean('is_visible')->default(true)->after('is_fasting');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_event_types', function (Blueprint $table): void {
            $table->dropColumn([
                'legacy_type',
                'typicon_symbol',
                'color',
                'is_fasting',
                'is_visible',
            ]);
        });
    }
};
