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
    Schema::create('monitored_services', function (Blueprint $table) {
        $table->id();
        $table->foreignId('monitored_host_id')
            ->constrained('monitored_hosts')
            ->cascadeOnDelete();

        $table->string('name');
        $table->integer('port');
        $table->string('protocol')->default('tcp');
        $table->string('status')->default('unknown');
        $table->timestamp('last_checked_at')->nullable();
        $table->timestamps();

        $table->unique(['monitored_host_id', 'port', 'protocol']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitored_services');
    }
};
