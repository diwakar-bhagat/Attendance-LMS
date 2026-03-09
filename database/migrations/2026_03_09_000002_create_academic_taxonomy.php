<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();

            $table->string('name', 150);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'name']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('title', 255);
            $table->unsignedTinyInteger('credits')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'code']);
        });

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();

            $table->timestamps();

            // Cannot offer the exact same course twice in the same academic session
            $table->unique(['course_id', 'term_id']);
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained('course_offerings')->cascadeOnDelete();

            $table->string('name', 100);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_offering_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
        Schema::dropIfExists('course_offerings');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('terms');
    }
};