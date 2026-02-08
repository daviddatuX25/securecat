<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per 04-data-model ScanEntry (Phase 1). Logs each scan (valid or invalid).
     */
    public function up(): void
    {
        Schema::create('scan_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_assignment_id')->nullable()->constrained('exam_assignments')->nullOnDelete();
            $table->foreignId('proctor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('scanned_at');
            $table->text('device_info')->nullable();
            $table->string('validation_result', 10); // valid | invalid
            $table->string('failure_reason', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_entries');
    }
};
