<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Config;

/**
 * Build and sign QR payload for exam assignments. Per 08-api-spec-phase1, SC-09: HMAC-SHA256.
 * Minimal payload: applicant_id, exam_session_id, generated_at.
 * Room, date/time are NOT included — scan validation uses the session record (DB) as source of truth
 * so printed slips stay valid after admin edits session details (no-regenerate policy).
 * See docs/plans/QR-NO-REGENERATE-REFACTOR.md.
 */
class QrSigningService
{
    public function buildPayload(
        int $applicantId,
        int $examSessionId,
    ): string {
        $payload = [
            'applicant_id' => $applicantId,
            'exam_session_id' => $examSessionId,
            'generated_at' => now()->toIso8601String(),
        ];

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    public function sign(string $payload): string
    {
        $secret = Config::get('app.qr_signing_key') ?? Config::get('app.key');
        if (empty($secret)) {
            throw new \RuntimeException('QR signing key not configured.');
        }

        return hash_hmac('sha256', $payload, $secret);
    }
}
