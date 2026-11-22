<?php

use App\Http\Middleware\EnsureTeacherPortalAccess;
use App\Livewire\Teacher\TeacherDashboard;
use App\Livewire\Teacher\TeacherLearning;
use App\Livewire\Teacher\TeacherProfilePage;
use App\Livewire\Admin\QuestionBank;
use App\Http\Controllers\Teacher\QuestionImportTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'license', EnsureTeacherPortalAccess::class])
    ->prefix('guru')
    ->name('teacher.')
    ->group(function (): void {
        Route::get('/dashboard', TeacherDashboard::class)->name('dashboard');
        Route::get('/pembelajaran', TeacherLearning::class)->name('learning');
        Route::get('/kurikulum', TeacherLearning::class)->name('curriculum');
        Route::get('/profil', TeacherProfilePage::class)->name('profile');
        Route::get('/bank-soal', QuestionBank::class)->name('questions.index');
        Route::get('/bank-soal/template', QuestionImportTemplateController::class)->name('questions.template');
    });
