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
        Schema::create('service_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_service_id')
                ->constrained('monitored_services')
                ->cascadeOnDelete();

            $table->string('status');
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_checks');
    }
};
