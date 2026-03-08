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
        // 1. Academic Sessions (Terms)
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('name'); // e.g. "Spring 2024"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Courses (Abstract Definitions)
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->string('code')->index(); // e.g. "CS101"
            $table->string('title');
            $table->integer('credits')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Sections (Physical Batches of a Course in a Term)
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->restrictOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->string('name'); // e.g. "Batch A"
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Enrollments (Students taking a Section)
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('section_id')->constrained('sections')->restrictOnDelete();
            $table->timestamps();

            // Prevent double enrollment
            $table->unique(['student_id', 'section_id']);
        });

        // 5. Faculty Assignments (Who teaches the Section)
        Schema::create('faculty_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('section_id')->constrained('sections')->restrictOnDelete();
            $table->timestamps();

            // Prevent double assignment
            $table->unique(['faculty_id', 'section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_assignments');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('terms');
    }
};