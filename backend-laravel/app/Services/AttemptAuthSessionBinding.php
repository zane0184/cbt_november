<?php

namespace App\Services;

use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;

class AttemptAuthSessionBinding
{
    /**
     * Return only a keyed digest. Raw session IDs and bearer-token values are never persisted.
     *
     * Stateful Web/Sanctum-cookie requests bind to the server session ID.
     * Personal access tokens bind to the server-side Sanctum token row ID.
     */
    public function identity(Request $request): ?array
    {
        $user = $request->user();
        $applicationKey = (string) config('app.key');
        if (!$user || trim($applicationKey) === '') {
            return null;
        }

        $accessToken = method_exists($user, 'currentAccessToken')
            ? $user->currentAccessToken()
            : null;

        if ($accessToken && !$accessToken instanceof TransientToken) {
            $tokenId = $accessToken->getKey();
            if (!$tokenId) {
                return null;
            }

            return [
                'hash' => hash_hmac('sha256', 'sanctum-token:'.$tokenId, $applicationKey),
                'type' => 'SANCTUM_TOKEN',
            ];
        }

        if ($request->hasSession()) {
            $sessionId = $request->session()->getId();
            if (is_string($sessionId) && $sessionId !== '') {
                return [
                    'hash' => hash_hmac('sha256', 'web-session:'.$sessionId, $applicationKey),
                    'type' => 'WEB_SESSION',
                ];
            }
        }

        return null;
    }

    public function bind(ExamAttempt $attempt, Request $request): bool
    {
        $identity = $this->identity($request);
        if (!$identity) {
            return false;
        }

        $attempt->forceFill([
            'auth_session_hash' => $identity['hash'],
            'auth_session_type' => $identity['type'],
            'auth_session_bound_at' => now(),
        ]);

        return true;
    }

    public function matches(ExamAttempt $attempt, Request $request): bool
    {
        $identity = $this->identity($request);

        return $identity !== null
            && is_string($attempt->auth_session_hash)
            && $attempt->auth_session_hash !== ''
            && hash_equals($attempt->auth_session_hash, $identity['hash'])
            && $attempt->auth_session_type === $identity['type'];
    }
}
