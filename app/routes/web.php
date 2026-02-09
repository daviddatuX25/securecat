<?php

use App\Http\Controllers\Admin\AdmissionPeriodController as AdminPeriodController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\ExamSessionController as AdminExamSessionController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Auth\WebLoginController;
use App\Http\Controllers\Auth\WebRegisterController;
use App\Http\Controllers\Print\AdmissionSlipController as PrintAdmissionSlipController;
use App\Http\Controllers\Proctor\ProctorController as ProctorProctorController;
use App\Http\Controllers\Staff\ApplicationController as StaffApplicationController;
use App\Http\Controllers\Staff\EncodeController as StaffEncodeController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', fn () => auth()->check()
    ? redirect()->to(match (auth()->user()->role) {
        'admin' => '/admin/dashboard',
        'staff' => '/staff/home',
        'proctor' => '/proctor/home',
        default => '/login',
    })
    : view('welcome')
)->middleware('web');
Route::get('/login', fn () => view('auth.login'))->name('login')->middleware('guest');
Route::post('/login', [WebLoginController::class, 'login'])->middleware('guest');
Route::get('/register', fn () => view('auth.register'))->name('register')->middleware('guest');
Route::post('/register', [WebRegisterController::class, 'store'])->middleware('guest');

// Protected (auth + rbac)
Route::middleware(['auth', 'rbac'])->group(function (): void {
    Route::get('/admin/dashboard', fn () => view('admin.dashboard'));
    Route::get('/admin/periods', [AdminPeriodController::class, 'index']);
    Route::get('/admin/periods/new', [AdminPeriodController::class, 'create']);
    Route::get('/admin/periods/{id}/edit', [AdminPeriodController::class, 'edit'])->name('admin.periods.edit');
    Route::get('/admin/courses', [AdminCourseController::class, 'index']);
    Route::get('/admin/courses/new', [AdminCourseController::class, 'create']);
    Route::get('/admin/courses/{id}/edit', [AdminCourseController::class, 'edit'])->name('admin.courses.edit');
    Route::get('/admin/rooms', [AdminRoomController::class, 'index']);
    Route::get('/admin/rooms/new', [AdminRoomController::class, 'create']);
    Route::get('/admin/rooms/{id}/edit', [AdminRoomController::class, 'edit'])->name('admin.rooms.edit');
    Route::get('/admin/sessions', [AdminExamSessionController::class, 'index']);
    Route::get('/admin/sessions/new', [AdminExamSessionController::class, 'create']);
    Route::get('/admin/sessions/{id}/edit', [AdminExamSessionController::class, 'edit'])->name('admin.sessions.edit');
    Route::get('/admin/applications', [AdminApplicationController::class, 'index']);
    Route::get('/admin/applications/{id}', [AdminApplicationController::class, 'show']);
    Route::get('/admin/audit-log', [AdminAuditLogController::class, 'index']);
    Route::get('/admin/reports/roster', [AdminReportController::class, 'roster']);
    Route::get('/admin/reports/attendance', [AdminReportController::class, 'attendance']);
    Route::get('/print/admission-slip/{assignment_id}', [PrintAdmissionSlipController::class, 'show']);
    Route::get('/staff/home', fn () => view('staff.home'));
    Route::get('/staff/encode', [StaffEncodeController::class, 'create']);
    Route::get('/staff/applications', [StaffApplicationController::class, 'index']);
    Route::get('/staff/applications/{id}', [StaffApplicationController::class, 'show'])->whereNumber('id');
    Route::get('/staff/applications/{id}/revise', [StaffApplicationController::class, 'revise'])->whereNumber('id')->name('staff.applications.revise');
    Route::get('/proctor/home', fn () => redirect('/proctor/sessions'));
    Route::get('/proctor/sessions', [ProctorProctorController::class, 'sessions']);
    Route::get('/proctor/scan/{session_id}', [ProctorProctorController::class, 'scan'])->whereNumber('session_id');
    Route::get('/proctor/attendance/{session_id}', [ProctorProctorController::class, 'attendance'])->whereNumber('session_id');
    Route::get('/proctor/roster', fn () => view('proctor.roster'));
    Route::post('/logout', [WebLoginController::class, 'logout'])->name('logout');
});
