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
        return view('livewire.teacher.analytics-dashboard');
    }
}
