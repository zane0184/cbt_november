<?php

namespace App\Livewire\Teacher;

use App\Models\Exam;
use App\Services\Analytics\ItemAnalysisService;

class ItemAnalysis extends TeacherResultComponent
{
    public Exam $exam;
    public $items = [];

    public function mount(Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);
        $this->exam = $exam;
        $this->items = app(ItemAnalysisService::class)->getAnalysis($exam);
    }

    public function render()
    {
        return view('livewire.teacher.item-analysis');
    }
}
