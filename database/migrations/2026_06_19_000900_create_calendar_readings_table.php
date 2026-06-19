<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_readings', function (Blueprint $table) {
            $table->id();
            $table->string('date_rule_type', 40);
            $table->smallInteger('month')->nullable();
            $table->smallInteger('day')->nullable();
            $table->smallInteger('offset')->nullable();
            $table->string('reading_type', 40);
            $table->string('title', 240)->nullable();
            $table->string('passage_ref', 160);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['date_rule_type', 'month', 'day']);
            $table->index(['date_rule_type', 'offset']);
            $table->index(['reading_type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_readings');
    }
};
