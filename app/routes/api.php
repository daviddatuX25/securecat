<?php

use App\Http\Controllers\Api\AdmissionPeriodController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApplicantController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExamAssignmentController;
use App\Http\Controllers\Api\ExamSessionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ScanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Phase 1)
|--------------------------------------------------------------------------
|
| Per docs/architecture/08-api-spec-phase1.md
|
*/

// Login: rate-limited, uses session (web middleware) for Auth::login + cookie
Route::middleware(['web', 'throttle:5,1'])->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Protected API routes: auth + RBAC + CSRF
Route::middleware(['web', 'auth', 'rbac'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Admission periods (T2.1.2) — Admin only
    Route::apiResource('admission-periods', AdmissionPeriodController::class);

    // Courses (T2.2.2) — Admin only
    Route::apiResource('courses', CourseController::class);

    // Rooms (T2.3.2) — Admin only
    Route::apiResource('rooms', RoomController::class);

    // Exam sessions (T2.4.2) — Admin full CRUD; Proctor list/show assigned only
    Route::apiResource('exam-sessions', ExamSessionController::class);

    // Applicants & Applications (T3.1.2) — Staff: POST applicants
    Route::post('/applicants', [ApplicantController::class, 'store']);

    // Applications (T3.2) — Admin/Staff: list, show; Admin: approve, reject, request-revision
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::post('/applications/{application}/approve', [ApplicationController::class, 'approve']);
    Route::post('/applications/{application}/reject', [ApplicationController::class, 'reject']);
    Route::post('/applications/{application}/request-revision', [ApplicationController::class, 'requestRevision']);
    Route::post('/applications/{application}/assign', [ApplicationController::class, 'assign']);
    Route::patch('/applications/{application}', [ApplicationController::class, 'update']);

    // Exam assignments (T3.3.3) — Admin, Staff: get assignment + QR for admission slip
    Route::get('/exam-assignments/{exam_assignment}', [ExamAssignmentController::class, 'show']);

    // Reports (T5.1.1, T5.2.1) — Admin, Proctor: roster and attendance per session
    Route::get('/reports/roster/{session_id}', [ReportController::class, 'roster']);
    Route::get('/reports/attendance/{session_id}', [ReportController::class, 'attendance']);

    // Scan (T4.2.2) — Proctor: submit QR, validate, create ScanEntry
    Route::post('/scan', [ScanController::class, 'store']);

    // Dashboard (T5.3.1) — Admin only; pending count + upcoming sessions
    Route::get('/dashboard', DashboardController::class);

    // Audit log (T6.2.1) — Admin only; paginated, filters
    Route::get('/audit-log', [AuditLogController::class, 'index']);
});
