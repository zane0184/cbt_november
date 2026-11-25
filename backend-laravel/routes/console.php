<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');


// Close timed-out CBT attempts once per minute. VPS scheduler/cron must be enabled during deployment.
\Illuminate\Support\Facades\Schedule::command('cbt:expire-attempts --limit=200')
    ->everyMinute()
    ->withoutOverlapping(5);

// Refresh the signed Publisher lease daily; the command skips until the configured interval is due.
\Illuminate\Support\Facades\Schedule::command('cbt:license:refresh')
    ->everySixHours()
    ->withoutOverlapping(10);
