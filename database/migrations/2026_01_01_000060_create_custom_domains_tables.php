<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hostname', 253)->unique();
            $table->string('verification_token', 64);
            // pending | verified | disabled
            $table->string('status', 20)->default('pending');
            $table->timestamp('dns_verified_at')->nullable();
            // unknown | active | failed
            $table->string('ssl_status', 20)->default('unknown');
            $table->timestamp('ssl_checked_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->unsignedInteger('check_count')->default(0);
            $table->string('disabled_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'last_checked_at']);
        });

        Schema::create('domain_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_domain_id')->constrained()->cascadeOnDelete();
            // dns_txt | dns_target | https
            $table->string('check', 20);
            $table->boolean('passed');
            $table->string('details', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['custom_domain_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_verifications');
        Schema::dropIfExists('custom_domains');
    }
};
