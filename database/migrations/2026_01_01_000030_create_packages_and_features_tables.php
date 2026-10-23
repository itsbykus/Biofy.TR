<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            // Translatable JSON: {"tr": "...", "en": "..."}
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('marketing_features')->nullable();
            $table->decimal('price_monthly', 10, 2)->nullable();
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->char('currency', 3)->default('TRY');
            $table->boolean('is_public')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_highlighted')->default(false);
            $table->boolean('is_contact_only')->default(false);
            $table->json('contact_message')->nullable();
            $table->string('contact_url', 500)->nullable();
            // Set when the package was created for a single customer (custom deal).
            $table->foreignId('custom_for_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('feature_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('group', 40)->default('general');
            // boolean | integer   (integer: -1 means unlimited)
            $table->string('type', 20);
            $table->string('default_value', 50);
            // Platform-wide hard constraints that no package or override may exceed.
            $table->boolean('platform_enabled')->default(true);
            $table->integer('platform_cap')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('package_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 80);
            $table->string('value', 50);
            $table->timestamps();
            $table->unique(['package_id', 'feature_key']);
            $table->foreign('feature_key')->references('key')->on('feature_definitions')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('user_package_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            // registration | admin | system
            $table->string('source', 20)->default('admin');
            $table->string('note', 500)->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'starts_at']);
        });

        Schema::create('user_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 80);
            $table->string('value', 50);
            $table->string('note', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'feature_key']);
            $table->foreign('feature_key')->references('key')->on('feature_definitions')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feature_overrides');
        Schema::dropIfExists('user_package_assignments');
        Schema::dropIfExists('package_features');
        Schema::dropIfExists('feature_definitions');
        Schema::dropIfExists('packages');
    }
};
