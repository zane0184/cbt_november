<?php

namespace App\Console\Commands;

use App\Services\ExamSessionLifecycleService;
use Illuminate\Console\Command;

class SyncExamSessionLifecycle extends Command
{
    protected $signature = 'exams:sync-room-session-lifecycle';

    protected $description = 'Advance exam room-session and temporary proctor assignment lifecycle states.';

    public function handle(ExamSessionLifecycleService $service): int
    {
        $counts = $service->synchronize();

        $this->info(sprintf(
            'Sessions activated: %d; sessions completed: %d; proctor assignments expired: %d; completed: %d.',
            $counts['sessions_activated'],
            $counts['sessions_completed'],
            $counts['assignments_expired'],
            $counts['assignments_completed'],
        ));

        return self::SUCCESS;
    }
}
