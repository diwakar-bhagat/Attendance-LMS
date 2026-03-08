<?php

namespace App\Domain\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Reporting\Services\AttendanceReportService;
use App\Domain\Academic\Models\Section;
use App\Domain\Identity\Models\User;
use App\Domain\Notifications\Mail\DefaulterWarningMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProcessDefaulterEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sectionId;
    public $facultyId;
    public $cutoffPercent;

    /**
     * Create a new job instance.
     */
    public function __construct(int $sectionId, int $facultyId, int $cutoffPercent = 75)
    {
        $this->sectionId = $sectionId;
        $this->facultyId = $facultyId;
        $this->cutoffPercent = $cutoffPercent;
    }

    /**
     * Execute the job.
     */
    public function handle(AttendanceReportService $reportService): void
    {
        // Fetch the domain records from the DB securely inside the worker
        $section = Section::with('course')->find($this->sectionId);
        $faculty = User::find($this->facultyId);

        if (!$section || !$faculty) {
            Log::error("Defaulter Job Aborted", ['section' => $this->sectionId, 'faculty' => $this->facultyId]);
            return; // Soft exit. Dependencies deleted mid-queue.
        }

        $sectionName = $section->course->code . " - " . $section->name;

        // Uses the centralized report service logic
        $defaulters = $reportService->getDefaulters($section->id, $this->cutoffPercent);

        if ($defaulters->isEmpty()) {
            Log::info("Defaulter Job Complete: No Defaulters Found", ['section' => $sectionName]);
            return;
        }

        foreach ($defaulters as $defaulterRecord) {
            // Find full user to pass to Mail
            $student = User::find($defaulterRecord->id);
            if ($student) {
                // Send securely to the queue transport
                Mail::to($student->email)->send(
                    new DefaulterWarningMail($student, $sectionName, $faculty->name, $defaulterRecord->percentage)
                );

                // Assuming we want a paper trail of automated emails
                \DB::table('audit_logs')->insert([
                    'user_id' => clone $faculty->id, // Who initiated the job
                    'action' => 'sent_defaulter_email',
                    'model_type' => get_class($student),
                    'model_id' => $student->id,
                    'new_values' => json_encode(['notified_at_percentage' => $defaulterRecord->percentage]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Log::info("Defaulter Job Complete: Triggered " . $defaulters->count() . " emails", ['section' => $sectionName]);
    }
}