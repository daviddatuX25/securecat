<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /print/admission-slip/:assignment_id — Printable admission slip with QR. Per 09-ui-routes-phase1, T4.1.1.
 * Roles: Admin, Staff. Signature is stored on assignment; verification happens on scan (POST /api/scan).
 */
class AdmissionSlipController extends Controller
{
    public function show(Request $request, int $assignment_id): View
    {
        $assignment = ExamAssignment::with([
            'application.applicant',
            'examSession.room',
            'examSession.course',
        ])->find($assignment_id);

        if (! $assignment) {
            throw new NotFoundHttpException('Assignment not found.');
        }

        $session = $assignment->examSession;
        $applicant = $assignment->application?->applicant;
        $applicantName = $applicant
            ? trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? ''))
            : '—';

        $dateStr = $session && $session->date instanceof \DateTimeInterface
            ? $session->date->format('Y-m-d')
            : ($session->date ?? '—');
        $roomName = $session?->room?->name ?? '—';
        $courseName = $session?->course?->name ?? '—';
        $courseCode = $session?->course?->code ?? '—';
        $timeStr = $session ? ($session->start_time . ' – ' . $session->end_time) : '—';

        // QR content: payload + signature so scanner can submit both to POST /api/scan
        $qrContent = json_encode([
            'qr_payload' => $assignment->qr_payload,
            'qr_signature' => $assignment->qr_signature,
        ], JSON_THROW_ON_ERROR);

        return view('print.admission-slip', [
            'assignment' => $assignment,
            'applicantName' => $applicantName,
            'dateStr' => $dateStr,
            'roomName' => $roomName,
            'courseName' => $courseName,
            'courseCode' => $courseCode,
            'timeStr' => $timeStr,
            'qrContent' => $qrContent,
        ]);
    }
}
