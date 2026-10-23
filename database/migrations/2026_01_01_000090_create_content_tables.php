<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->json('title');
            // Markdown, rendered with raw HTML escaped.
            $table->json('body')->nullable();
            $table->json('meta_description')->nullable();
            $table->boolean('is_published')->default(true);
            // System pages (privacy, terms...) cannot be deleted, only edited.
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60);
            $table->string('locale', 5);
            $table->string('subject', 200);
            // Plain text with {placeholders}; always escaped when rendered.
            $table->text('body');
            $table->boolean('is_customized')->default(false);
            $table->timestamps();
            $table->unique(['key', 'locale']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            // general | package
            $table->string('type', 20)->default('general');
            $table->string('name', 120);
            $table->string('email');
            $table->string('subject', 200);
            $table->text('message');
            // new | read | archived
            $table->string('status', 20)->default('new');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('backup_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('filename', 191);
            // manual | scheduled | pre_restore | pre_update
            $table->string('type', 20)->default('manual');
            // running | completed | failed
            $table->string('status', 20)->default('running');
            $table->boolean('encrypted')->default(false);
            $table->unsignedBigInteger('size')->default(0);
            $table->char('checksum', 64)->nullable();
            $table->json('manifest')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_records');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('pages');
    }
};
