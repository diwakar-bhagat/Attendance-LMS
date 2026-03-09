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
            $table->foreignId('faculty_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('meeting_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->timestamps();

            $table->unique(['section_id', 'meeting_date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_meetings');
    }
};