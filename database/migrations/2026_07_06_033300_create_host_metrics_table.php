<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('host_metrics', function (Blueprint $table) {
        $table->id();
        $table->foreignId('monitored_host_id')
            ->constrained('monitored_hosts')
            ->cascadeOnDelete();

        $table->decimal('cpu_usage', 5, 2)->nullable();
        $table->decimal('ram_usage', 5, 2)->nullable();
        $table->decimal('disk_usage', 5, 2)->nullable();
        $table->unsignedBigInteger('uptime_seconds')->nullable();
        $table->timestamp('recorded_at');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('host_metrics');
    }
};
