<?php

namespace Tests\Feature;

use Tests\TestCase;

class LicenseStatusAccessTest extends TestCase
{
    public function test_license_status_details_are_not_public(): void
    {
        $response = $this->get('/license/status');

        $response->assertRedirect('/login');
    }
}
