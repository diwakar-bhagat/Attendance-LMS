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
        // 1. Institutions (Tenant Isolation)
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Roles (Lookup Table)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // admin, faculty, student
            $table->string('display_name');
            $table->timestamps();
        });

        // 3. Users (Central Auth)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->restrictOnDelete();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->string('name');
            $table->string('email'); // Not unique globally, only per tenant. Uniqueness enforced via index.
            $table->string('password'); // Hashed (Bcrypt/Argon2)
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // Preserve audit trail

            // A user's email should only be unique within their institution
            $table->unique(['institution_id', 'email']);
        });

        // 4. Faculty Profiles
        Schema::create('faculty_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('employee_id')->nullable();
            $table->timestamps();
        });

        // 5. Student Profiles
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('roll_no')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('faculty_profiles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('institutions');
    }
};