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
    Schema::create('alerts', function (Blueprint $table) {
        $table->id();

        $table->foreignId('monitored_host_id')
            ->nullable()
            ->constrained('monitored_hosts')
            ->nullOnDelete();

        $table->foreignId('monitored_service_id')
            ->nullable()
            ->constrained('monitored_services')
            ->nullOnDelete();

        $table->string('type');
        $table->string('severity')->default('warning');
        $table->string('title');
        $table->text('message')->nullable();
        $table->string('status')->default('open');
        $table->timestamp('triggered_at');
        $table->timestamp('resolved_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
