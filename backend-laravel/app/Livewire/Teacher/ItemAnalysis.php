<?php

namespace App\Livewire\Teacher;

use App\Models\Exam;
use App\Services\Analytics\ItemAnalysisService;
use Livewire\Component;

class ItemAnalysis extends Component
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
        // Re-check persisted Guru role, permission and assignment on every Livewire request.
        $this->authorize('viewExamResults', $this->exam);

        return view('livewire.teacher.item-analysis');
    }
}
