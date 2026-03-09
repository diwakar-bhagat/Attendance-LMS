<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_meeting_id')->constrained('class_meetings')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            $table->string('status', 20)->default('present');
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // CRITICAL INTEGRITY: A student cannot have two distinct attendance rows for the exact same class meeting.
            // Helps UPSERT race-condition safety.
            $table->unique(['class_meeting_id', 'student_id']);

            // Index for hot-path Student Dashboards (finding absences)
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};