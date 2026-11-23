<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentApiWriteContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_assessment_create_and_update_api_routes_are_not_exposed(): void
    {
        $this->postJson('/api/v1/assessments', [
            'assessment_type' => 'DAILY',
            'title' => 'Legacy fixed taxonomy payload',
        ])->assertStatus(405);

        $assessmentId = '00000000-0000-4000-8000-000000000001';
        $this->putJson('/api/v1/assessments/' . $assessmentId, [
            'assessment_type' => 'FINAL',
            'title' => 'Legacy update payload',
        ])->assertStatus(405);

        $this->patchJson('/api/v1/assessments/' . $assessmentId, [
            'assessment_type' => 'TRYOUT',
        ])->assertStatus(405);
    }
}
