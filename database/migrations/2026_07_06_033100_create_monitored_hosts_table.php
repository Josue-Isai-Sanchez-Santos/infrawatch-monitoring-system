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
    Schema::create('monitored_hosts', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('hostname')->nullable();
        $table->string('ip_address')->unique();
        $table->string('operating_system')->nullable();
        $table->string('host_type')->default('server');
        $table->string('location')->nullable();
        $table->string('status')->default('unknown');
        $table->string('agent_token')->unique()->nullable();
        $table->timestamp('last_seen_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitored_hosts');
    }
};
