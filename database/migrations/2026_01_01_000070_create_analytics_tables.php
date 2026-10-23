<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw events: no raw IP address, only a daily-rotating pseudonymous visitor hash.
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            // view | click
            $table->string('type', 10);
            $table->unsignedBigInteger('block_id')->nullable();
            $table->unsignedSmallInteger('item_index')->nullable();
            $table->char('visitor_hash', 32);
            $table->boolean('is_unique')->default(false);
            $table->string('referrer_host', 191)->nullable();
            $table->string('device', 10)->nullable();
            $table->string('browser', 20)->nullable();
            $table->char('country', 2)->nullable();
            $table->timestamp('created_at');
            $table->index(['profile_id', 'created_at']);
            $table->index(['profile_id', 'type', 'visitor_hash', 'created_at'], 'analytics_events_dedupe_index');
            $table->index('created_at');
        });

        // Pre-aggregated counters so dashboards never scan the raw event table.
        // block_id = 0 holds profile-level totals.
        Schema::create('analytics_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('block_id')->default(0);
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unique(['profile_id', 'block_id', 'date'], 'analytics_daily_unique');
            $table->index(['date', 'block_id']);
        });

        Schema::create('analytics_salts', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->string('salt', 64);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_salts');
        Schema::dropIfExists('analytics_daily_summaries');
        Schema::dropIfExists('analytics_events');
    }
};
