<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class TeacherResultNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_and_analytics_routes_have_stable_teacher_names(): void
    {
        $routes = Route::getRoutes();

        $this->assertSame('exams/{exam}/results', $routes->getByName('teacher.exams.results')?->uri());
        $this->assertSame('exams/{exam}/results/{attempt}', $routes->getByName('teacher.exams.results.detail')?->uri());
        $this->assertSame('exams/{exam}/analytics', $routes->getByName('teacher.exams.analytics')?->uri());
        $this->assertSame('exams/{exam}/analytics/items', $routes->getByName('teacher.exams.analytics.items')?->uri());
    }

    public function test_named_result_and_analytics_urls_resolve_for_an_exam(): void
    {
        $examId = '018f0000-0000-7000-8000-000000000001';
        $attemptId = '018f0000-0000-7000-8000-000000000002';

        $this->assertSame('/exams/'.$examId.'/results', route('teacher.exams.results', ['exam' => $examId], false));
        $this->assertSame('/exams/'.$examId.'/results/'.$attemptId, route('teacher.exams.results.detail', [
            'exam' => $examId,
            'attempt' => $attemptId,
        ], false));
        $this->assertSame('/exams/'.$examId.'/analytics', route('teacher.exams.analytics', ['exam' => $examId], false));
        $this->assertSame('/exams/'.$examId.'/analytics/items', route('teacher.exams.analytics.items', ['exam' => $examId], false));
    }
}
