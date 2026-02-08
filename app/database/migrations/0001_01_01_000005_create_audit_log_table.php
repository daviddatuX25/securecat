<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per 04-data-model AuditLog Phase 1. SC-10 foundational audit.
     */
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 20);
            $table->string('action', 100);
            $table->string('entity_type', 50);
            $table->string('entity_id', 50);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('timestamp');
            $table->json('details')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
