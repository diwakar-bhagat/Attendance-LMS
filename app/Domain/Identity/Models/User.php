<?php

namespace App\Domain\Identity\Models;

use App\Domain\Core\Models\Institution;
use App\Domain\Academic\Models\Section;
use App\Domain\Identity\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'role_id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Institution relationship ensuring multi-tenant capability.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Role relationship for authorization checks.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Faculty-specific data, if this user is a faculty member.
     */
    public function facultyProfile(): HasOne
    {
        return $this->hasOne(FacultyProfile::class);
    }

    /**
     * Student-specific data, if this user is a student.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * The sections this student is enrolled in.
     */
    public function enrolledSections(): BelongsToMany
    {
        return $this->belongsToMany(
            Section::class ,
            'enrollments',
            'student_id',
            'section_id'
        )->withTimestamps();
    }

    /**
     * The sections this faculty is assigned to teach.
     */
    public function assignedSections(): BelongsToMany
    {
        return $this->belongsToMany(
            Section::class ,
            'faculty_assignments',
            'faculty_id',
            'section_id'
        )->withTimestamps();
    }

    /**
     * Helper to check roles.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }
}