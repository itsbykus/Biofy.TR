<?php

// CI only: copied into a running container to prove that a later start backs up and migrates.
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ci_probe', fn (Blueprint $table) => $table->id());
    }

    public function down(): void
    {
        Schema::dropIfExists('ci_probe');
    }
};
