<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use Illuminate\Http\JsonResponse;

/**
 * GET exam assignment by id (full assignment + qr for admission slip). Per 08-api-spec-phase1 §4.
 */
class ExamAssignmentController extends Controller
{
    /**
     * GET /api/exam-assignments/:id — assignment with qr_payload, qr_signature, session/room/date/time.
     * Roles: Admin, Staff.
     */
    public function show(ExamAssignment $exam_assignment): JsonResponse
    {
        $exam_assignment->load([
            'application.applicant',
            'examSession.room',
            'examSession.course',
        ]);

        $session = $exam_assignment->examSession;

        return response()->json([
            'data' => [
                'id' => $exam_assignment->id,
                'application_id' => $exam_assignment->application_id,
                'exam_session_id' => $exam_assignment->exam_session_id,
                'seat_number' => $exam_assignment->seat_number,
                'qr_payload' => $exam_assignment->qr_payload,
                'qr_signature' => $exam_assignment->qr_signature,
                'assigned_at' => $exam_assignment->assigned_at?->toIso8601String(),
                'exam_session' => $session ? [
                    'id' => $session->id,
                    'date' => $session->date instanceof \DateTimeInterface
                        ? $session->date->format('Y-m-d')
                        : $session->date,
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'room' => $session->room ? [
                        'id' => $session->room->id,
                        'name' => $session->room->name,
                        'capacity' => $session->room->capacity,
                    ] : null,
                    'course' => $session->course ? [
                        'id' => $session->course->id,
                        'name' => $session->course->name,
                        'code' => $session->course->code,
                    ] : null,
                ] : null,
                'applicant' => $exam_assignment->application?->applicant ? [
                    'id' => $exam_assignment->application->applicant->id,
                    'first_name' => $exam_assignment->application->applicant->first_name,
                    'last_name' => $exam_assignment->application->applicant->last_name,
                ] : null,
            ],
        ]);
    }
}
