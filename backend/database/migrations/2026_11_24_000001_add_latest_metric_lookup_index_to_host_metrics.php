<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_metrics', function (Blueprint $table): void {
            $table->index(
                ['monitored_host_id', 'recorded_at', 'id'],
                'host_metrics_host_recorded_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('host_metrics', function (Blueprint $table): void {
            $table->dropIndex('host_metrics_host_recorded_id_idx');
        });
    }
};
