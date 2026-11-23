<?php

namespace App\Livewire\Teacher;

use App\Services\TeacherPortal\TeacherLearningOutcomeService;
use App\Services\TeacherPortal\TeacherPortalDataService;

class TeacherLearning extends TeacherPortalComponent
{
    public array $tpForms = [];

    public array $atpForms = [];

    public function createTp(string $assignmentId): void
    {
        $this->validate([
            "tpForms.{$assignmentId}.parent_cp_id" => ['required', 'uuid'],
            "tpForms.{$assignmentId}.code" => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            "tpForms.{$assignmentId}.title" => ['required', 'string', 'max:255'],
            "tpForms.{$assignmentId}.description" => ['required', 'string', 'min:20', 'max:12000'],
            "tpForms.{$assignmentId}.panca_cinta" => ['required', 'array', 'min:1'],
            "tpForms.{$assignmentId}.pm_principles" => ['required', 'array', 'min:1'],
            "tpForms.{$assignmentId}.pm_framework" => ['nullable', 'array'],
            "tpForms.{$assignmentId}.graduate_dimensions" => ['required', 'array', 'min:1'],
        ]);

        $form = $this->tpForms[$assignmentId];

        app(TeacherLearningOutcomeService::class)->createTp(
            $assignmentId,
            $form['parent_cp_id'],
            $form['code'],
            $form['title'],
            $form['description'],
            $form['panca_cinta'],
            $form['pm_principles'],
            $form['pm_framework'] ?? [],
            $form['graduate_dimensions']
        );

        unset($this->tpForms[$assignmentId]);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Tujuan Pembelajaran berhasil disimpan sebagai draft.']);
    }

    public function createAtp(string $assignmentId, string $parentTpId): void
    {
        $this->validate([
            "atpForms.{$parentTpId}.code" => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            "atpForms.{$parentTpId}.title" => ['required', 'string', 'max:255'],
            "atpForms.{$parentTpId}.description" => ['required', 'string', 'min:20', 'max:12000'],
            "atpForms.{$parentTpId}.sequence_number" => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $form = $this->atpForms[$parentTpId];

        app(TeacherLearningOutcomeService::class)->createAtp(
            $assignmentId,
            $parentTpId,
            $form['code'],
            $form['title'],
            $form['description'],
            (int) $form['sequence_number']
        );

        unset($this->atpForms[$parentTpId]);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Alur Tujuan Pembelajaran berhasil disimpan sebagai draft.']);
    }

    public function publishOutcome(string $outcomeId): void
    {
        app(TeacherLearningOutcomeService::class)->publish($outcomeId);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'TP/ATP berhasil dipublikasikan untuk penugasan ini.']);
    }

    public function render()
    {
        $data = app(TeacherPortalDataService::class)->learning(auth()->user());

        return view('livewire.teacher.portal-learning', $data)
            ->layout('layouts.teacher');
    }
}
