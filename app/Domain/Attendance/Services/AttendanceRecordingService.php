<?php

namespace App\Domain\Attendance\Services;

use App\Domain\Attendance\Models\ClassMeeting;
use App\Domain\Attendance\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class AttendanceRecordingService
{
    /**
     * Process a bulk array of attendance securely and quickly.
     * Transactional so a failure in row 50 rolls back the entire class submission.
     *
     * @param ClassMeeting $meeting
     * @param array $attendanceData ['student_id' => 'status']
     * @return void
     * @throws \Exception
     */
    public function recordBatch(ClassMeeting $meeting, array $attendanceData): void
    {
        DB::beginTransaction();

        try {
            // First we prepare the insert array for upserting.
            // Upsert prevents duplicate unique key constraint violations (SQLSTATE 23505)
            // if a faculty clicks 'Save' twice in a lag spike.
            $recordsToUpsert = [];

            foreach ($attendanceData as $studentId => $status) {
                // Building the actual row data
                $recordsToUpsert[] = [
                    'class_meeting_id' => $meeting->id,
                    'student_id' => $studentId,
                    'status' => $status,

                    // Essential timestamps for manual bulk updates
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($recordsToUpsert)) {
                // Laravel 8+ bulk upsert command
                // Arg 1: array of values
                // Arg 2: unique constraint columns
                // Arg 3: what to update if exists.
                AttendanceRecord::upsert(
                    $recordsToUpsert,
                ['class_meeting_id', 'student_id'],
                ['status', 'updated_at']
                );
            }

            DB::commit();

        // We log the audit outside the transaction, or via Observers
        // Future feature: Dispatch Job here if an attendance flipped a 'defaulter' status.

        }
        catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Rethrow to let the controller handle displaying the error to the UI
        }
    }
}