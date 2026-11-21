<?php

use Illuminate\Support\Facades\Artisan;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

Artisan::command('imports:expire-staging {--days=7}', function () {
    $days = max(1, (int) $this->option('days'));
    $cutoff = now()->subDays($days);
    $expired = 0;
    $candidates = ImportBatch::withoutGlobalScopes()->where('status', 'PREVIEW')
        ->where('created_at', '<', $cutoff)->get(['id', 'tenant_id', 'created_by']);
    foreach ($candidates as $candidate) {
        $didExpire = DB::transaction(function () use ($candidate, $days): bool {
            Tenant::withoutGlobalScopes()->whereKey($candidate->tenant_id)
                ->whereNull('deleted_at')->lockForUpdate()->first();
            $batch = ImportBatch::withoutGlobalScopes()->where('tenant_id', $candidate->tenant_id)
                ->whereKey($candidate->id)->where('status', 'PREVIEW')->lockForUpdate()->first();
            if (!$batch) return false;
            ImportBatchRow::withoutGlobalScopes()->where('tenant_id', $candidate->tenant_id)
                ->where('batch_id', $batch->id)->update(['encrypted_payload' => null]);
            $batch->update(['status' => 'EXPIRED', 'completed_at' => now()]);
            AuditLog::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $candidate->tenant_id,
                'user_id' => $candidate->created_by, 'action' => 'IMPORT_PREVIEW_EXPIRED',
                'entity_type' => 'ImportBatch', 'entity_id' => $batch->id,
                'old_values' => null, 'new_values' => ['retention_days' => $days],
                'ip_address' => null, 'user_agent' => 'scheduler', 'created_at' => now(),
            ]);
            return true;
        }, 3);
        if ($didExpire) $expired++;
    }
    $this->info("Expired {$expired} preview batch(es); encrypted staging payloads cleared.");
})->purpose('Expire abandoned import previews after the configured retention period.');

\Illuminate\Support\Facades\Schedule::command('imports:expire-staging --days=7')
    ->daily()->withoutOverlapping(30);
