<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ExamSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * GET /api/dashboard — Admin only. Pending count + upcoming sessions. Per 08-api-spec-phase1, T5.3.1.
 */
class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $pendingCount = Application::where('status', Application::STATUS_PENDING_REVIEW)->count();

        $today = Carbon::today()->toDateString();
        $sessions = ExamSession::with(['course', 'room'])
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $upcomingSessions = $sessions->map(function (ExamSession $session): array {
            $courseName = $session->course?->name ?? '—';
            $roomName = $session->room?->name ?? '—';
            $date = Carbon::parse($session->date)->format('Y-m-d');

            return [
                'id' => $session->id,
                'date' => $date,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
                'course_name' => $courseName,
                'room_name' => $roomName,
                'label' => "{$courseName} — {$roomName} — {$date}",
            ];
        })->values()->all();

        return response()->json([
            'pending_applications_count' => $pendingCount,
            'upcoming_sessions' => $upcomingSessions,
        ]);
    }
}
