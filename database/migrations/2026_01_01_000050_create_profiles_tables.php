<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserved_usernames', function (Blueprint $table) {
            $table->id();
            $table->string('word', 40)->unique();
            $table->timestamps();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Always stored lower-case; public URL: https://biofy.tr/{slug}
            $table->string('slug', 40)->unique();
            $table->string('display_name', 80);
            $table->string('title', 120)->nullable();
            $table->string('bio_short', 300)->nullable();
            $table->text('bio_long')->nullable();
            $table->foreignId('avatar_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->foreignId('banner_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('badge', 30)->nullable();
            // Set by platform staff only.
            $table->timestamp('verified_at')->nullable();
            $table->string('theme_key', 50)->default('minimal-light');
            $table->json('theme_settings')->nullable();
            $table->string('seo_title', 120)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->foreignId('seo_image_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->boolean('seo_noindex')->default(false);
            $table->string('language', 5)->default('tr');
            // draft | published | suspended | archived
            $table->string('status', 20)->default('draft');
            $table->string('suspension_reason', 500)->nullable();
            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->timestamp('published_at')->nullable();
            // Optimistic concurrency token for the editor (multi-tab protection).
            $table->unsignedInteger('draft_revision')->default(1);
            $table->boolean('has_unpublished_changes')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::create('profile_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->longText('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['profile_id', 'version_no']);
        });

        Schema::create('profile_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->json('data');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['profile_id', 'position']);
            $table->index(['profile_id', 'type']);
        });

        Schema::create('profile_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('url', 2048);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['profile_id', 'position']);
        });

        Schema::create('profile_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_slug', 40)->unique();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_slug_redirects');
        Schema::dropIfExists('profile_social_links');
        Schema::dropIfExists('profile_blocks');
        Schema::dropIfExists('profile_versions');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('reserved_usernames');
    }
};
