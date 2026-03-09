<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            // Optional: Hard auditing of who initiated the class exactly. Kept nullable if team grading is allowed.
            $table->foreignId('faculty_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('meeting_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->timestamps();

            // Highly unlikely to have the exact same batch, exact same date, exact same start time twice
            $table->unique(['section_id', 'meeting_date', 'start_time']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            // A record belongs to a specific time and place (class_meeting) and a specific person (student)
            $table->foreignId('class_meeting_id')->constrained('class_meetings')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // PostgreSQL ENUM equivalent or String. 
            // In Laravel, strings are simpler for migrations, max 20 chars prevents bloat.
            // Expected: present, absent, late, excused, disabled
            $table->string('status', 20)->default('present');

            // Tracking who made the override if a record was altered later
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // CRITICAL INTEGRITY: A student cannot have two distinct attendance rows for the exact same class meeting.
            // If they are marked Present, then later marked Absent, that is an UPDATE, not an INSERT.
            $table->unique(['class_meeting_id', 'student_id']);

            // Index for Student Dashboards: WHERE student_id = ? AND status = ?
            $table->index(['student_id', 'status']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 100); // e.g., 'override_attendance', 'sent_defaulter_email'
            $table->string('model_type', 255)->nullable(); // e.g., 'App\\Domain\\Attendance\\Models\\AttendanceRecord'
            $table->unsignedBigInteger('model_id')->nullable();

            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();

            $table->timestamps();

            // For querying history of an exact record
            $table->index(['model_type', 'model_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

        // Native Laravel Schema for database notifications queue fallback
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('class_meetings');
    }
};