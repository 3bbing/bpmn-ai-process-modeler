<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes');
            $table->unsignedInteger('version');
            $table->longText('bpmn_xml');
            $table->longText('sop_md')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('is_published')->default(false);
            $table->enum('status', ['draft', 'in_review', 'approved', 'changes_requested', 'published'])->default('draft');
            $table->timestamps();
            $table->unique(['process_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_versions');
    }
};
