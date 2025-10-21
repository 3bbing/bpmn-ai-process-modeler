<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_version_id')->constrained('process_versions');
            $table->foreignId('reviewer_user_id')->constrained('users');
            $table->text('comment')->nullable();
            $table->enum('decision', ['approve', 'request_changes']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
