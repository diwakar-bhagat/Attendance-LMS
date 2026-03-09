<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();

            $table->string('name', 150); // e.g., "Fall 2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Cannot have two identically named terms in the same college
            $table->unique(['institution_id', 'name']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('name', 200);

            $table->timestamps();
            $table->unique(['institution_id', 'name']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('name', 200); // e.g., "B.Tech Computer Science"

            $table->timestamps();
            $table->unique(['department_id', 'name']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->string('code', 50);
            $table->string('title', 255);
            $table->unsignedTinyInteger('credits')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'code']);
        });

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            // Connects the abstract 'course' to a specific 'academic session'
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // A course can only be offered once per academic session globally before branching into Sections
            $table->unique(['course_id', 'academic_session_id']);
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            // A section/batch is a specific physical grouping under a Course Offering
            $table->foreignId('course_offering_id')->constrained('course_offerings')->cascadeOnDelete();
            $table->string('name', 100); // e.g., "Section A", "Lab 2"

            $table->timestamps();
            $table->softDeletes();

            // Cannot have two "Section A"s for the exact same offering.
            $table->unique(['course_offering_id', 'name']);
        });

        // Many-to-Many Pivot tables
        Schema::create('faculty_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();

            $table->timestamps();

            // Prevent accidentally assigning the same professor 3 times to 1 section
            $table->unique(['faculty_id', 'section_id']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();

            $table->timestamps();

            // Prevent double enrollment
            $table->unique(['student_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('faculty_assignments');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('course_offerings');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('academic_sessions');
    }
};