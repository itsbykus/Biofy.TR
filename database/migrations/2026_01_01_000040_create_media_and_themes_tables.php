<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            // Null owner = platform asset (logo, favicon, ...).
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('disk', 30)->default('media');
            $table->string('path', 255)->unique();
            // avatar | banner | block | gallery | seo | brand | page
            $table->string('purpose', 30)->default('block');
            $table->string('mime', 60);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('original_name', 191)->nullable();
            $table->char('sha256', 64);
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
        Schema::dropIfExists('media_files');
    }
};
