<?php

namespace App\Services;

use App\Models\DeviceLease;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttemptDeviceLeaseService
{
    private const LEASE_SECONDS = 300;

    public function createForAttempt(ExamAttempt $attempt, Request $request): bool
    {
        $identity = app(AttemptAuthSessionBinding::class)->identity($request);
        if (!$identity || !$attempt->auth_session_hash
            || !hash_equals($attempt->auth_session_hash, $identity['hash'])) {
            return false;
        }

        $now = now();
        $lease = DeviceLease::create([
            'tenant_id' => $attempt->tenant_id,
            'exam_attempt_id' => $attempt->id,
            'registered_device_id' => null,
            'auth_session_hash' => $identity['hash'],
            'fence_generation' => (int) $attempt->fence_generation,
            'status' => 'ACTIVE',
            'leased_at' => $now,
            'expires_at' => $this->leaseExpiry($attempt, $now),
        ]);
        $attempt->forceFill([
            'device_lease_expires_at' => $lease->expires_at,
            'last_lease_heartbeat_at' => $now,
        ])->save();

        return true;
    }

    /**
     * Caller must already hold Exam -> Participant -> Attempt row locks.
     * An expired lease can only be renewed by the exact bound auth session.
     * Renewal increments the monotonic fence generation and expires the old lease.
     */
    public function assertAndRenew(ExamAttempt $attempt, Request $request): bool
    {
        $identity = app(AttemptAuthSessionBinding::class)->identity($request);
        if (!$identity || !$attempt->auth_session_hash
            || !hash_equals($attempt->auth_session_hash, $identity['hash'])
            || $attempt->auth_session_type !== $identity['type']) {
            return false;
        }

        $lease = DeviceLease::query()
            ->where('tenant_id', $attempt->tenant_id)
            ->where('exam_attempt_id', $attempt->id)
            ->where('fence_generation', $attempt->fence_generation)
            ->where('status', 'ACTIVE')
            ->lockForUpdate()
            ->first();

        $now = now();
        if (!$lease) {
            return false;
        }

        if (!$lease->expires_at || $lease->expires_at->lte($now)) {
            $lease->update(['status' => 'EXPIRED', 'released_at' => $now]);
            $attempt->forceFill(['fence_generation' => ((int) $attempt->fence_generation) + 1])->save();

            $newLease = DeviceLease::create([
                'tenant_id' => $attempt->tenant_id,
                'exam_attempt_id' => $attempt->id,
                'registered_device_id' => null,
                'auth_session_hash' => $identity['hash'],
                'fence_generation' => (int) $attempt->fence_generation,
                'status' => 'ACTIVE',
                'leased_at' => $now,
                'expires_at' => $this->leaseExpiry($attempt, $now),
            ]);
            $attempt->forceFill([
                'device_lease_expires_at' => $newLease->expires_at,
                'last_lease_heartbeat_at' => $now,
            ])->save();

            return true;
        }

        $expiresAt = $this->leaseExpiry($attempt, $now);
        $lease->update(['expires_at' => $expiresAt]);
        $attempt->forceFill([
            'device_lease_expires_at' => $expiresAt,
            'last_lease_heartbeat_at' => $now,
        ])->save();

        return true;
    }

    public function isCurrent(ExamAttempt $attempt, Request $request): bool
    {
        $identity = app(AttemptAuthSessionBinding::class)->identity($request);
        if (!$identity || !$attempt->auth_session_hash
            || !hash_equals($attempt->auth_session_hash, $identity['hash'])
            || $attempt->auth_session_type !== $identity['type']) {
            return false;
        }

        return DeviceLease::query()
            ->where('tenant_id', $attempt->tenant_id)
            ->where('exam_attempt_id', $attempt->id)
            ->where('fence_generation', $attempt->fence_generation)
            ->where('auth_session_hash', $identity['hash'])
            ->where('status', 'ACTIVE')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function release(ExamAttempt $attempt, string $reason): void
    {
        $generation = (int) $attempt->fence_generation;
        DeviceLease::query()
            ->where('tenant_id', $attempt->tenant_id)
            ->where('exam_attempt_id', $attempt->id)
            ->where('fence_generation', $generation)
            ->where('status', 'ACTIVE')
            ->lockForUpdate()
            ->get()
            ->each(fn (DeviceLease $lease) => $lease->update([
                'status' => 'RELEASED',
                'released_at' => now(),
                'release_reason' => $reason,
            ]));

        $attempt->forceFill([
            'fence_generation' => $generation + 1,
            'device_lease_expires_at' => null,
        ])->save();
    }

    private function leaseExpiry(ExamAttempt $attempt, $now)
    {
        $expiry = $now->copy()->addSeconds(self::LEASE_SECONDS);

        return $attempt->expires_at && $attempt->expires_at->lt($expiry)
            ? $attempt->expires_at
            : $expiry;
    }
}
