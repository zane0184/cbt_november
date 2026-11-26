<?php

use App\Http\Middleware\EnsureTeacherPortalAccess;
use App\Livewire\Teacher\TeacherDashboard;
use App\Livewire\Teacher\TeacherAssignmentsPage;
use App\Livewire\Teacher\TeacherLearning;
use App\Livewire\Teacher\TeacherProfilePage;
use App\Livewire\Admin\QuestionBank;
use App\Livewire\Teacher\QuestionImportWizard;
use App\Livewire\Admin\ExamManagement;
use App\Http\Controllers\Teacher\QuestionImportTemplateController;
use App\Http\Controllers\Teacher\TeacherQuestionMediaController;
use App\Http\Controllers\Api\V1\MediaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'license', EnsureTeacherPortalAccess::class])
    ->prefix('guru')
    ->name('teacher.')
    ->group(function (): void {
        Route::get('/dashboard', TeacherDashboard::class)->name('dashboard');
        Route::get('/penugasan', TeacherAssignmentsPage::class)->name('assignments');
        Route::get('/pembelajaran', TeacherLearning::class)->name('learning');
        Route::get('/kurikulum', TeacherLearning::class)->name('curriculum');
        Route::get('/profil', TeacherProfilePage::class)->name('profile');
        Route::get('/bank-soal', QuestionBank::class)->name('questions.index');
        Route::get('/bank-soal/import', QuestionImportWizard::class)->name('questions.import');
        Route::get('/asesmen', ExamManagement::class)->name('exams.index');
        Route::get('/bank-soal/template', QuestionImportTemplateController::class)->name('questions.template');
        Route::post('/bank-soal/{question}/versions/{version}/media', [TeacherQuestionMediaController::class, 'storeAndAttach'])->name('questions.media.store');
        Route::delete('/bank-soal/{question}/versions/{version}/media/{media}', [TeacherQuestionMediaController::class, 'detach'])->name('questions.media.detach');
        Route::get('/bank-soal/media/{media}', [MediaController::class, 'show'])->name('questions.media.show');
    });
