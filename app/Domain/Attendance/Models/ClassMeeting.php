<?php

namespace App\Domain\Attendance\Models;

use App\Domain\Academic\Models\Section;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'faculty_id',
        'meeting_date',
        'start_time',
        'end_time',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    /**
     * The section this meeting belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * The faculty member who created/held this specific meeting.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class , 'faculty_id');
    }

    /**
     * The attendance records for this specific meeting.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}