<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLicenseActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureLicenseActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_test_bypass_works_only_in_testing_environment(): void
    {
        config([
            'license.testing_bypass' => true,
            'license.pre_activation_2026' => false,
        ]);

        $request = Request::create('/api/v1/exams', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = app(EnsureLicenseActive::class)->handle(
            $request,
            fn () => response()->json(['success' => true])
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['success' => true], $response->getData(true));
    }

    public function test_license_gate_fails_closed_when_test_bypass_is_disabled(): void
    {
        config([
            'license.testing_bypass' => false,
            'license.pre_activation_2026' => false,
        ]);

        $request = Request::create('/api/v1/exams', 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = app(EnsureLicenseActive::class)->handle(
            $request,
            fn () => response()->json(['success' => true])
        );

        $this->assertSame(423, $response->getStatusCode());
        $this->assertSame('LICENSE_NOT_ACTIVATED', $response->getData(true)['code']);
    }
}
