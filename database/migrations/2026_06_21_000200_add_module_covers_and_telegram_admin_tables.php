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
        Schema::table('modules', function (Blueprint $table): void {
            $table->string('cover_path', 500)->nullable()->after('description');
        });

        Schema::create('telegram_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('telegram_id', 80)->index();
            $table->string('telegram_username', 80)->nullable();
            $table->string('chat_id', 80)->index();
            $table->string('direction', 20)->default('inbound');
            $table->string('status', 30)->default('new');
            $table->text('body');
            $table->text('admin_reply')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['direction', 'status', 'created_at']);
        });

        Schema::create('telegram_broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->text('body');
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_broadcasts');
        Schema::dropIfExists('telegram_messages');

        Schema::table('modules', function (Blueprint $table): void {
            $table->dropColumn('cover_path');
        });
    }
};
