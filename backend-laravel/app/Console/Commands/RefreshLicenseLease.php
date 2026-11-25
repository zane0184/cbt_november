<?php

namespace App\Console\Commands;

use App\Models\LocalLicenseState;
use App\Services\Licensing\PublisherLicenseClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshLicenseLease extends Command
{
    protected $signature = 'cbt:license:refresh';
    protected $description = 'Refresh signed offline license lease from the Publisher when due';

    public function handle(PublisherLicenseClient $client): int
    {
        $state = LocalLicenseState::query()->first();
        if (! $state || $state->status !== 'ACTIVE') { $this->line('No active license state; skipped.'); return self::SUCCESS; }
        $interval = max(1, (int) config('license.lease_refresh_interval_hours', 24));
        if ($state->last_publisher_check_at && $state->last_publisher_check_at->gt(now()->subHours($interval))) { $this->line('Publisher lease refresh is not due yet.'); return self::SUCCESS; }
        try {
            $client->refresh();
            $this->info('Publisher license lease refreshed and verified.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::warning('Scheduled CBT Publisher lease refresh failed.', ['exception' => get_class($exception), 'reason' => preg_match('/^[A-Z0-9_]+$/', $exception->getMessage()) ? $exception->getMessage() : 'NETWORK_OR_PROTOCOL_ERROR']);
            $this->error('Lease refresh failed; existing offline lease remains authoritative until its signed expiry.');
            return self::FAILURE;
        }
    }
}
