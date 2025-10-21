<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains');
            $table->string('code')->unique();
            $table->string('title');
            $table->enum('level', ['L1', 'L2', 'L3', 'L4']);
            $table->foreignId('owner_user_id')->constrained('users');
            $table->enum('status', ['draft', 'in_review', 'published', 'archived'])->default('draft');
            $table->text('summary')->nullable();
            $table->json('guidance')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processes');
    }
};
