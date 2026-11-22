<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\AttemptAuthSessionBinding;
use Illuminate\Http\Request;
use Tests\TestCase;

class AttemptAuthSessionBindingTest extends TestCase
{
    public function test_web_attempt_binding_uses_server_session_id_not_device_header(): void
    {
        $user = new User();
        $binding = app(AttemptAuthSessionBinding::class);

        $firstSession = app('session')->driver('array');
        $firstSession->setId('web-session-first-0000000000000001');
        $firstSession->start();
        $firstRequest = Request::create('/api/v1/exams/attempt');
        $firstRequest->setLaravelSession($firstSession);
        $firstRequest->setUserResolver(fn () => $user);
        $firstRequest->headers->set('X-Device-Binding-ID', 'same-browser-fingerprint');

        $secondSession = app('session')->driver('array');
        $secondSession->setId('web-session-second-000000000002');
        $secondSession->start();
        $secondRequest = Request::create('/api/v1/exams/attempt');
        $secondRequest->setLaravelSession($secondSession);
        $secondRequest->setUserResolver(fn () => $user);
        $secondRequest->headers->set('X-Device-Binding-ID', 'same-browser-fingerprint');

        $firstIdentity = $binding->identity($firstRequest);
        $secondIdentity = $binding->identity($secondRequest);

        $this->assertNotNull($firstIdentity);
        $this->assertNotNull($secondIdentity);
        $this->assertSame('WEB_SESSION', $firstIdentity['type']);
        $this->assertSame('WEB_SESSION', $secondIdentity['type']);
        $this->assertNotSame($firstIdentity['hash'], $secondIdentity['hash']);

        $attempt = new ExamAttempt();
        $attempt->forceFill([
            'auth_session_hash' => $firstIdentity['hash'],
            'auth_session_type' => $firstIdentity['type'],
        ]);

        $this->assertTrue($binding->matches($attempt, $firstRequest));
        $this->assertFalse($binding->matches($attempt, $secondRequest));
    }
}
