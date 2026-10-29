<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_metrics', function (Blueprint $table) {
            $table->uuid('reading_id')->nullable();

            $table->unique(
                ['monitored_host_id', 'reading_id'],
                'host_metrics_host_reading_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('host_metrics', function (Blueprint $table) {
            $table->dropUnique(
                'host_metrics_host_reading_unique'
            );

            $table->dropColumn('reading_id');
        });
    }
};
