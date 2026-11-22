<?php

use App\Http\Middleware\EnsureTeacherPortalAccess;
use App\Livewire\Teacher\TeacherDashboard;
use App\Livewire\Teacher\TeacherLearning;
use App\Livewire\Teacher\TeacherProfilePage;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'tenant', 'license', EnsureTeacherPortalAccess::class])
    ->prefix('guru')
    ->name('teacher.')
    ->group(function (): void {
        Route::get('/dashboard', TeacherDashboard::class)->name('dashboard');
        Route::get('/pembelajaran', TeacherLearning::class)->name('learning');
        Route::get('/profil', TeacherProfilePage::class)->name('profile');
    });
