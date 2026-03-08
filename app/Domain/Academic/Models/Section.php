<?php

namespace App\Domain\Academic\Models;

use App\Domain\Attendance\Models\ClassMeeting;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use SoftDeletes; // tenant_id is omitted conceptually here because Term and Course have it, but you CAN mix it in if needed for performance.

    protected $fillable = [
        'term_id',
        'course_id',
        'name',
    ];

    /**
     * Term to which this section belongs.
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Course abstract this section derives from.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Students enrolled in this specific batch.
     */
    public function enrolledStudents(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class ,
            'enrollments',
            'section_id',
            'student_id'
        )->withTimestamps();
    }

    /**
     * Faculty teaching this specific batch.
     */
    public function assignedFaculty(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class ,
            'faculty_assignments',
            'section_id',
            'faculty_id'
        )->withTimestamps();
    }

    /**
     * The physical class meetings held for this section.
     */
    public function classMeetings(): HasMany
    {
        return $this->hasMany(ClassMeeting::class);
    }
}