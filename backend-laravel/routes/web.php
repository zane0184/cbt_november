<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\StudentExamController;
use App\Livewire\Teacher\ResultList;
use App\Livewire\Teacher\ResultDetail;
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\TenantProfileManagement;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\StudentRombelManagement;
use App\Livewire\Admin\AcademicManagement;
use App\Livewire\Admin\CurriculumManagement;
use App\Livewire\Admin\QuestionBank;
use App\Livewire\Admin\ExamManagement;
use App\Livewire\Admin\ProctorAssignmentManagement;
use App\Livewire\Admin\ExamRoomSessionManagement;
use App\Http\Controllers\Admin\ImportTemplateController;
use App\Livewire\Admin\ImportManagement;
use App\Http\Controllers\Auth\ImportAccountActivationController;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();

        if ($user->hasRole('ADMIN_MADRASAH')) {
            return redirect('/admin/dashboard');
        }

        if ($user->hasRole('GURU')) {
            return redirect('/guru/dashboard');
        }

        if ($user->hasRole('SISWA')) {
            return redirect('/student/exams');
        }
    }

    return view('welcome');
});

Route::get('/login', [AuthWebController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');
Route::post('/auth/logout', [AuthWebController::class, 'logout']);
Route::get('/activate-import-account/{token}', [ImportAccountActivationController::class, 'show'])->middleware(['signed', 'throttle:10,1'])->name('import-account.activate.show');
Route::post('/activate-import-account/{token}', [ImportAccountActivationController::class, 'store'])->middleware(['signed', 'throttle:10,1'])->name('import-account.activate.store');
Route::get('/license/status', \App\Http\Controllers\LicenseStatusController::class)->middleware('auth')->name('license.status');
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
    // Dedicated Dashboards
    Route::get('/admin/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/admin/profile', TenantProfileManagement::class)->name('admin.profile');

    // Temporary URL compatibility only: the Operator dashboard itself is retired.
    Route::get('/operator/dashboard', fn () => redirect('/admin/dashboard'));

    // Admin Operational Routes
    Route::get('/admin/import', ImportManagement::class)->name('admin.import');
    Route::get('/admin/import/template/{type}', [ImportTemplateController::class, 'download'])->name('admin.import.template');
    Route::get('/admin/users', UserManagement::class);
    Route::get('/admin/rombel-students', StudentRombelManagement::class);
    Route::get('/admin/academic', AcademicManagement::class);
    Route::get('/admin/curriculum', CurriculumManagement::class);
    Route::get('/admin/questions', QuestionBank::class);
    Route::get('/admin/exams', ExamManagement::class);
    Route::get('/admin/proctor-assignments', ProctorAssignmentManagement::class)->name('admin.proctor-assignments');
    Route::get('/admin/exam-rooms-sessions', ExamRoomSessionManagement::class)->name('admin.exam-rooms-sessions');

    // Teacher & Results Routes
    Route::get('/exams/{exam}/results', ResultList::class)->name('teacher.exams.results');
    Route::get('/exams/{exam}/results/{attempt}', ResultDetail::class)->name('teacher.exams.results.detail');

    // Exports for Teacher Dashboard
    Route::get('/exams/{exam}/export/excel', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportExcel'])->name('teacher.exams.export.excel');
    Route::get('/exams/{exam}/export/pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportPdf'])->name('teacher.exams.export.pdf');
    Route::get('/students/{student}/export/pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportStudentPdf']);

    // Analytics Dashboard
    Route::get('/exams/{exam}/analytics', \App\Livewire\Teacher\AnalyticsDashboard::class)->name('teacher.exams.analytics');
    Route::get('/exams/{exam}/analytics/items', \App\Livewire\Teacher\ItemAnalysis::class)->name('teacher.exams.analytics.items');
});

require __DIR__.'/teacher.php';
