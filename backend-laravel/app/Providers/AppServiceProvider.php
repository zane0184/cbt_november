<?php

namespace App\Providers;

use App\Contexts\TenantContext;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\Events\Authenticated;
use App\Policies\ReportPolicy;
use App\Policies\QuestionPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });
        $this->app->alias(TenantContext::class, 'tenant_context');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Heartbeat limits are per authenticated participant, not per shared NAT/IP.
        // Keep a conservative IP fallback for unauthenticated requests.
        RateLimiter::for('exam-heartbeat', function ($request) {
            $user = $request->user();
            $key = $user && $user->tenant_id
                ? 'tenant:' . $user->tenant_id . ':user:' . $user->id
                : 'ip:' . $request->ip();

            return Limit::perMinute(30)->by($key);
        });

        Event::listen(Authenticated::class, function ($event) {
            if ($event->user && $event->user->tenant_id) {
                app(TenantContext::class)->setTenantId($event->user->tenant_id);
            }
        });

        Gate::policy(\App\Models\User::class, ReportPolicy::class);
        Gate::policy(\App\Models\Subject::class, ReportPolicy::class);
        Gate::policy(\App\Models\Question::class, QuestionPolicy::class);

        Gate::define('viewExamResults', function ($user, ...$args) {
            $exam = collect($args)->flatten()->first(fn($a) => $a instanceof \App\Models\Exam);
            return app(ReportPolicy::class)->viewExamResults($user, $exam);
        });
        Gate::define('viewStudentResults', function ($user, ...$args) {
            $student = collect($args)->flatten()->first(fn($a) => $a instanceof \App\Models\User);
            return app(ReportPolicy::class)->viewStudentResults($user, $student ?? $user);
        });
        Gate::define('viewSubjectResults', function ($user, ...$args) {
            $subject = collect($args)->flatten()->first(fn($a) => $a instanceof \App\Models\Subject);
            return app(ReportPolicy::class)->viewSubjectResults($user, $subject);
        });
    }
}
