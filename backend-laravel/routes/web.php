<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\StudentExamController;
use App\Livewire\Teacher\ResultList;
use App\Livewire\Teacher\ResultDetail;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\StudentRombelManagement;
use App\Livewire\Admin\AcademicManagement;
use App\Livewire\Admin\CurriculumManagement;
use App\Livewire\Admin\QuestionBank;
use App\Livewire\Admin\ExamManagement;
use App\Livewire\Admin\ProctorAssignmentManagement;
use App\Livewire\Operator\OperatorDashboard;
use App\Livewire\Kamad\KamadDashboard;
use App\Livewire\Proktor\ProktorDashboard;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasRole('KAMAD')) {
            return redirect('/admin/overview');
        } elseif ($user->hasRole('PROKTOR')) {
            return redirect('/proktor/assignments');
        } elseif ($user->hasRole('OPERATOR') || $user->hasRole('ADMIN_MADRASAH')) {
            return redirect('/admin/dashboard');
        } elseif ($user->hasRole('GURU')) {
            return redirect('/admin/exams');
        } elseif ($user->hasRole('SISWA')) {
            return redirect('/student/exams');
        }
    }
    return view('welcome');
});

Route::get('/login', [AuthWebController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');
Route::post('/auth/logout', [AuthWebController::class, 'logout']);
Route::get('/license/status', \App\Http\Controllers\LicenseStatusController::class)->name('license.status');
Route::post('/license/refresh', [\App\Http\Controllers\LicenseStatusController::class, 'refresh'])->middleware(['auth', 'throttle:5,1'])->name('license.refresh');

// Web Installer Wizard Routes
Route::middleware(['prevent_reinstall', 'installer_bootstrap'])->prefix('install')->name('installer.')->group(function () {
    Route::get('/authorize', [\App\Http\Controllers\InstallerController::class, 'authorizeForm'])->name('authorize.form');
    Route::post('/authorize', [\App\Http\Controllers\InstallerController::class, 'authorizeBootstrap'])->middleware('throttle:5,1')->name('authorize');
    Route::get('/', [\App\Http\Controllers\InstallerController::class, 'index'])->name('index');
    Route::get('/requirements', [\App\Http\Controllers\InstallerController::class, 'requirements'])->name('requirements');
    Route::get('/database', [\App\Http\Controllers\InstallerController::class, 'database'])->name('database');
    Route::post('/database/test', [\App\Http\Controllers\InstallerController::class, 'testDatabase'])->name('database.test');
    Route::post('/database/save', [\App\Http\Controllers\InstallerController::class, 'saveDatabase'])->name('database.save');
    Route::get('/migrations', [\App\Http\Controllers\InstallerController::class, 'migrations'])->name('migrations');
    Route::post('/migrations/run', [\App\Http\Controllers\InstallerController::class, 'runMigrations'])->name('migrations.run');
    Route::get('/admin', [\App\Http\Controllers\InstallerController::class, 'admin'])->name('admin');
    Route::post('/admin/save', [\App\Http\Controllers\InstallerController::class, 'saveAdmin'])->name('admin.save');
    Route::get('/license', [\App\Http\Controllers\InstallerController::class, 'license'])->name('license');
    Route::post('/license/activate', [\App\Http\Controllers\InstallerController::class, 'activateLicense'])->middleware('throttle:5,1')->name('license.activate');
    Route::post('/license/refresh', [\App\Http\Controllers\InstallerController::class, 'refreshLicense'])->middleware('throttle:10,1')->name('license.refresh');
    Route::get('/complete', [\App\Http\Controllers\InstallerController::class, 'complete'])->name('complete');
    Route::post('/finalize', [\App\Http\Controllers\InstallerController::class, 'finalize'])->name('finalize');
});

Route::get('/exams/{exam}/monitoring', function (\App\Models\Exam $exam) {
    if (auth()->user()->cannot('monitor', $exam)) {
        abort(403, 'Anda tidak memiliki izin untuk mengakses monitoring ujian ini.');
    }
    return view('monitoring.dashboard', ['examId' => $exam->id]);
})->middleware(['auth', 'tenant', 'license']);

Route::middleware(['auth', 'tenant', 'license'])->prefix('student')->name('student.')->group(function () {
    Route::get('/exams', [StudentExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
    Route::get('/results/{attempt}', [StudentExamController::class, 'result'])->name('results.show');
});

Route::middleware(['auth', 'tenant', 'license'])->group(function () {
    // Shared Admin Madrasah portal. Kamad receives a read-only executive view
    // inside the same portal; Proktor receives only a temporary assignment workbench.
    Route::get('/admin/dashboard', OperatorDashboard::class)->name('admin.dashboard');
    Route::get('/admin/overview', KamadDashboard::class)->name('admin.overview');
    Route::get('/admin/proctor-assignments', ProctorAssignmentManagement::class)->name('admin.proctor-assignments');
    Route::get('/operator/dashboard', fn () => new \Illuminate\Http\RedirectResponse('/admin/dashboard'))->name('operator.dashboard.legacy');
    Route::get('/proktor/assignments', ProktorDashboard::class)->name('proktor.assignments');

    // Admin Operational Routes
    Route::get('/admin/users', UserManagement::class);
    Route::get('/admin/rombel-students', StudentRombelManagement::class);
    Route::get('/admin/academic', AcademicManagement::class);
    Route::get('/admin/curriculum', CurriculumManagement::class);
    Route::get('/admin/questions', QuestionBank::class);
    Route::get('/admin/exams', ExamManagement::class);

    // Teacher & Results Routes
    Route::get('/exams/{exam}/results', ResultList::class);
    Route::get('/exams/{exam}/results/{attempt}', ResultDetail::class);

    // Exports for Teacher Dashboard
    Route::get('/exams/{exam}/export/excel', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportExcel']);
    Route::get('/exams/{exam}/export/pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportPdf']);
    Route::get('/students/{student}/export/pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportStudentPdf']);

    // Analytics Dashboard
    Route::get('/exams/{exam}/analytics', \App\Livewire\Teacher\AnalyticsDashboard::class);
    Route::get('/exams/{exam}/analytics/items', \App\Livewire\Teacher\ItemAnalysis::class);
});
