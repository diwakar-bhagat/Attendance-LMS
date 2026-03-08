<?php

namespace App\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Academic\Models\Section;
use Illuminate\Auth\Access\Response;

class SectionPolicy
{
    /**
     * Determine if the user can view the specific section.
     * Admins can view all. Faculty can only view sections they are assigned to.
     * Students can only view sections they are enrolled in.
     */
    public function view(User $user, Section $section): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('faculty')) {
            return $user->assignedSections()->where('section_id', $section->id)->exists();
        }

        if ($user->hasRole('student')) {
            return $user->enrolledSections()->where('section_id', $section->id)->exists();
        }

        return false;
    }

    /**
     * Determine if the user can create/manage a meeting for this section.
     */
    public function manageAttendance(User $user, Section $section): bool
    {
        if ($user->hasRole('admin')) {
            return true; // Admins can override
        }

        // Must be faculty and assigned to this specific section
        return $user->hasRole('faculty') && $user->assignedSections()->where('section_id', $section->id)->exists();
    }
}