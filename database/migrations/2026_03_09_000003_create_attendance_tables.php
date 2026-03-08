<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Class Meetings (Instances of a Section meeting on a day)
        Schema::create('class_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->restrictOnDelete();
            $table->foreignId('faculty_id')->constrained('users')->restrictOnDelete(); // Which faculty held this session
            $table->date('meeting_date')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            // Prevent multiple identical meetings from being created by mistake for the same day/section
            // Removed unique constraint to allow multiple meetings on same day, e.g. morning/afternoon, 
            // but we can add an index here instead.
            $table->index(['meeting_date', 'section_id']);
        });

        // 2. Attendance Records (High Velocity Table)
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_meeting_id')->constrained('class_meetings')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            // Using a string status rather than lookup table saves joins for simple SaaS apps.
            // Values: present, absent, excused, late
            $table->string('status', 20)->default('absent');
            $table->timestamps();

            // DATA-INTEGRITY-CRITICAL: Prevent double entries for the same student in the same meeting
            $table->unique(['class_meeting_id', 'student_id']);

            // Index for dashboard reporting querying a student's aggregates quickly
            $table->index(['student_id', 'status']);
        });

        // 3. Audit Logs (Compliance & History Tracking)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // user who caused action
            $table->string('action'); // e.g. "updated_attendance", "soft_deleted_faculty"
            $table->string('model_type'); // e.g. "App\Domain\Attendance\Models\AttendanceRecord"
            $table->unsignedBigInteger('model_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('class_meetings');
    }
};