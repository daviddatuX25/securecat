<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamAssignment;
use App\Models\ScanEntry;
use App\Services\AuditLogger;
use App\Services\QrSigningService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * POST /api/scan — Submit scanned QR; validate signature, session/time, duplicate; create ScanEntry. Per 08-api-spec-phase1 §5.
 * QR payload only needs applicant_id + exam_session_id; all other checks (time, room, proctor) use session from DB.
 * Old-format QR codes (with room_id/schedule) remain valid — extra fields are simply ignored.
 */
class ScanController extends Controller
{
    public function __construct(
        private readonly QrSigningService $qrSigning,
        private readonly AuditLogger $auditLogger
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_payload' => 'required|string',
            'qr_signature' => 'required|string|size:64',
            'device_info' => 'nullable|string|max:2000',
        ]);

        $proctor = Auth::user();
        if (! $proctor || $proctor->role !== 'proctor') {
            return response()->json(['error' => 'forbidden', 'message' => 'Proctor only.'], 403);
        }

        $payloadStr = $validated['qr_payload'];
        $signature = $validated['qr_signature'];
        $deviceInfo = $validated['device_info'] ?? null;

        // 1. Verify HMAC
        $expectedSignature = $this->qrSigning->sign($payloadStr);
        if (! hash_equals($expectedSignature, $signature)) {
            $entry = $this->createScanEntry(null, $proctor->id, ScanEntry::RESULT_INVALID, 'Invalid or tampered QR', $deviceInfo);
            $this->auditScan($entry, null, null);

            return response()->json([
                'result' => 'invalid',
                'failure_reason' => 'Invalid or tampered QR',
            ]);
        }

        // 2. Decode payload
        $payload = $this->decodePayload($payloadStr);
        if ($payload === null) {
            $entry = $this->createScanEntry(null, $proctor->id, ScanEntry::RESULT_INVALID, 'Invalid or tampered QR', $deviceInfo);
            $this->auditScan($entry, null, null);

            return response()->json([
                'result' => 'invalid',
                'failure_reason' => 'Invalid or tampered QR',
            ]);
        }

        $applicantId = (int) ($payload['applicant_id'] ?? 0);
        $examSessionId = (int) ($payload['exam_session_id'] ?? 0);

        // Only applicant_id and exam_session_id are required from the QR payload.
        // Room, schedule, etc. are looked up from the session record (DB) — no-regenerate policy.
        // Old-format QR codes (with room_id/schedule) still pass; those fields are simply ignored.
        if (! $applicantId || ! $examSessionId) {
            $entry = $this->createScanEntry(null, $proctor->id, ScanEntry::RESULT_INVALID, 'Invalid or tampered QR', $deviceInfo);
            $this->auditScan($entry, null, null);

            return response()->json([
                'result' => 'invalid',
                'failure_reason' => 'Invalid or tampered QR',
            ]);
        }

        // 3. Find assignment (applicant + session)
        $assignment = ExamAssignment::where('exam_session_id', $examSessionId)
            ->whereHas('application', fn ($q) => $q->where('applicant_id', $applicantId))
            ->with(['application.applicant', 'examSession.room'])
            ->first();

        if (! $assignment) {
            $entry = $this->createScanEntry(null, $proctor->id, ScanEntry::RESULT_INVALID, 'Invalid or tampered QR', $deviceInfo);
            $this->auditScan($entry, null, null);

            return response()->json([
                'result' => 'invalid',
                'failure_reason' => 'Invalid or tampered QR',
            ]);
        }

        // 4. Proctor must be assigned to this session
        if ((int) $assignment->examSession->proctor_id !== (int) $proctor->id) {
            $entry = $this->createScanEntry($assignment->id, $proctor->id, ScanEntry::RESULT_INVALID, 'Wrong session', $deviceInfo);
            $this->auditScan($entry, $assignment->id, $examSessionId);

            return response()->json([
                'result' => 'invalid',
                'failure_reason' => 'Wrong session',
            ]);
        }

        // 5. Room: we do not reject when payload room_id differs from session's current room,
        //    so printed slips remain valid after admin changes session room (no-regenerate policy).

        // 6. Current time within session date and start_time–end_time (session is source of truth)
        $session = $assignment->examSession;
        $sessionDate = $session->date instanceof \DateTimeInterface
            ? $session->date->format('Y-m-d')
            : Carbon::parse($session->date)->format('Y-m-d');
        $startTime = $session->start_time ?? '00:00';
        $endTime = $session->end_time ?? '23:59';
        $now = Carbon::now();
        $start = Carbon::parse($sessionDate . ' ' . $startTime);
        $end = Carbon::parse($sessionDate . ' ' . $endTime);

        if ($now->lt($start) || $now->gt($end)) {
            $entry = $this->createScanEntry($assignment->id, $proctor->id, ScanEntry::RESULT_INVALID, 'Outside time window', $deviceInfo);
            $this->auditScan($entry, $assignment->id, $examSessionId);

            return response()->json([
                'result' => 'invalid',
                'failure_reason' => 'Outside time window',
            ]);
        }

        // 7. No existing valid ScanEntry for this assignment
        $alreadyScanned = ScanEntry::where('exam_assignment_id', $assignment->id)
            ->where('validation_result', ScanEntry::RESULT_VALID)
            ->exists();

        if ($alreadyScanned) {
            $entry = $this->createScanEntry($assignment->id, $proctor->id, ScanEntry::RESULT_INVALID, 'Already scanned', $deviceInfo);
            $this->auditScan($entry, $assignment->id, $examSessionId);

            return response()->json([
                'result' => 'invalid',
                'failure_reason' => 'Already scanned',
            ]);
        }

        // Valid scan
        $entry = $this->createScanEntry($assignment->id, $proctor->id, ScanEntry::RESULT_VALID, null, $deviceInfo);
        $this->auditScan($entry, $assignment->id, $examSessionId);

        $applicant = $assignment->application?->applicant;
        $applicantName = $applicant
            ? trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? ''))
            : '—';

        return response()->json([
            'result' => 'valid',
            'applicant_name' => $applicantName,
            'exam_session_id' => (string) $examSessionId,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(string $payloadStr): ?array
    {
        try {
            $decoded = json_decode($payloadStr, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }

    private function createScanEntry(
        ?int $examAssignmentId,
        int $proctorId,
        string $validationResult,
        ?string $failureReason,
        ?string $deviceInfo
    ): ScanEntry {
        return ScanEntry::create([
            'exam_assignment_id' => $examAssignmentId,
            'proctor_id' => $proctorId,
            'scanned_at' => now(),
            'device_info' => $deviceInfo,
            'validation_result' => $validationResult,
            'failure_reason' => $failureReason,
        ]);
    }

    private function auditScan(ScanEntry $entry, ?int $examAssignmentId, ?int $examSessionId): void
    {
        $this->auditLogger->log(
            'scan.validate',
            'ScanEntry',
            (string) $entry->id,
            [
                'result' => $entry->validation_result,
                'failure_reason' => $entry->failure_reason,
                'exam_assignment_id' => $examAssignmentId,
                'exam_session_id' => $examSessionId,
            ]
        );
    }
}
