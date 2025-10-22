<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_log', 'event')) {
                $table->string('event')->nullable()->after('log_name');
                $table->index('event');
            }

            if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
                $table->index('batch_uuid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            if (Schema::hasColumn('activity_log', 'batch_uuid')) {
                $table->dropIndex('activity_log_batch_uuid_index');
                $table->dropColumn('batch_uuid');
            }

            if (Schema::hasColumn('activity_log', 'event')) {
                $table->dropIndex('activity_log_event_index');
                $table->dropColumn('event');
            }
        });
    }
};
