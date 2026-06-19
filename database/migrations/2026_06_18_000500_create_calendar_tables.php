<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_event_type_id')->nullable()->constrained()->nullOnDelete();
            $table->text('name');
            $table->integer('legacy_type')->nullable()->index();
            $table->string('date_rule_type', 40);
            $table->smallInteger('start_month')->nullable();
            $table->smallInteger('start_day')->nullable();
            $table->smallInteger('start_offset')->nullable();
            $table->smallInteger('end_month')->nullable();
            $table->smallInteger('end_day')->nullable();
            $table->smallInteger('end_offset')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['date_rule_type', 'start_month', 'start_day']);
        });

        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedSmallInteger('year')->index();
            $table->date('pascha_date')->nullable();
            $table->string('tone', 40)->nullable();
            $table->string('week', 80)->nullable();
            $table->string('fasting_type', 80)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });

        Schema::create('calendar_day_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['calendar_day_id', 'calendar_event_id'], 'calendar_day_events_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_day_events');
        Schema::dropIfExists('calendar_days');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('calendar_event_types');
    }
};
