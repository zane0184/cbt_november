<?php

namespace App\Livewire\Teacher;

use App\Models\Exam;
use App\Services\Analytics\ExamAnalyticsService;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public Exam $exam;
    public $summary = [];
    public $distribution = [];

    public function mount(Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);
        $this->exam = $exam;
        $service = app(ExamAnalyticsService::class);
        $this->summary = $service->getSummary($exam);
        $this->distribution = $service->getDistribution($exam);
    }

    public function render()
    {
        // Re-check persisted Guru role, permission and assignment on every Livewire request.
        $this->authorize('viewExamResults', $this->exam);

        return view('livewire.teacher.analytics-dashboard');
    }
}
