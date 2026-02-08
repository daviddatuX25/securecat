<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use App\Models\ExamSession;
use App\Models\ScanEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Reports: roster and attendance per session. Per 08-api-spec-phase1 §6.
 */
class ReportController extends Controller
{
    /**
     * GET /api/reports/roster/:session_id — assignments for session with applicant (name, contact), seat_number, assignment id.
     * Roles: Admin (all sessions); Proctor (only assigned session).
     */
    public function roster(int $session_id): JsonResponse|Response
    {
        $session = ExamSession::find($session_id);
        if (! $session) {
            return response()->json(['error' => 'not_found', 'message' => 'Session not found.'], 404);
        }

        $user = Auth::user();
        if ($user && $user->role === 'proctor' && (int) $session->proctor_id !== (int) $user->id) {
            return response()->json(['error' => 'forbidden', 'message' => 'Not assigned to this session.'], 403);
        }

        $assignments = ExamAssignment::where('exam_session_id', $session_id)
            ->with(['application.applicant'])
            ->orderBy('seat_number')
            ->get();

        $data = $assignments->map(function (ExamAssignment $a) {
            $applicant = $a->application?->applicant;

            return [
                'assignment_id' => $a->id,
                'application_id' => $a->application_id,
                'seat_number' => $a->seat_number,
                'applicant' => $applicant ? [
                    'id' => $applicant->id,
                    'first_name' => $applicant->first_name,
                    'last_name' => $applicant->last_name,
                    'email' => $applicant->email,
                    'contact_number' => $applicant->contact_number,
                ] : null,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/reports/attendance/:session_id — Assignments for session with scanned status. Per 08-api-spec-phase1 §6.
     * Roles: Admin; Proctor (only for assigned session).
     */
    public function attendance(int $session_id): JsonResponse|Response
    {
        $session = ExamSession::find($session_id);
        if (! $session) {
            return response()->json(['error' => 'not_found', 'message' => 'Session not found.'], 404);
        }

        $user = Auth::user();
        if ($user && $user->role === 'proctor' && (int) $session->proctor_id !== (int) $user->id) {
            return response()->json(['error' => 'forbidden', 'message' => 'Not assigned to this session.'], 403);
        }

        $latestValidScan = ScanEntry::query()
            ->select('exam_assignment_id', 'scanned_at', 'validation_result')
            ->where('validation_result', ScanEntry::RESULT_VALID)
            ->whereIn('exam_assignment_id', function ($q) use ($session_id) {
                $q->select('id')->from('exam_assignments')->where('exam_session_id', $session_id);
            })
            ->orderByDesc('scanned_at')
            ->get()
            ->unique('exam_assignment_id')
            ->keyBy('exam_assignment_id');

        $assignments = ExamAssignment::where('exam_session_id', $session_id)
            ->with(['application.applicant'])
            ->orderBy('seat_number')
            ->get();

        $data = $assignments->map(function (ExamAssignment $a) use ($latestValidScan) {
            $applicant = $a->application?->applicant;
            $scan = $latestValidScan->get($a->id);

            return [
                'assignment_id' => $a->id,
                'application_id' => $a->application_id,
                'seat_number' => $a->seat_number,
                'applicant' => $applicant ? [
                    'id' => $applicant->id,
                    'first_name' => $applicant->first_name,
                    'last_name' => $applicant->last_name,
                    'email' => $applicant->email,
                    'contact_number' => $applicant->contact_number,
                ] : null,
                'scanned_at' => $scan ? $scan->scanned_at?->format(\DateTimeInterface::ATOM) : null,
                'validation_result' => $scan ? $scan->validation_result : null,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
