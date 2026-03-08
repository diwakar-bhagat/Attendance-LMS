<?php

namespace App\Domain\Attendance\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_meeting_id',
        'student_id',
        'status',
    ];

    /**
     * The meeting this record pertains to.
     */
    public function classMeeting(): BelongsTo
    {
        return $this->belongsTo(ClassMeeting::class);
    }

    /**
     * The student this record is for.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class , 'student_id');
    }
}