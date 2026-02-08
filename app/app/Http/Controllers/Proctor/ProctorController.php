<?php

declare(strict_types=1);

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Proctor UI: session list, scan page, attendance. Per 09-ui-routes-phase1.
 */
class ProctorController extends Controller
{
    /**
     * GET /proctor/sessions — List my assigned sessions.
     */
    public function sessions(): View
    {
        return view('proctor.sessions.index');
    }

    /**
     * GET /proctor/scan/:session_id — QR scan page for session.
     */
    public function scan(int $session_id): View|Response
    {
        $session = ExamSession::with(['course', 'room'])
            ->find($session_id);

        if (! $session) {
            throw new NotFoundHttpException('Session not found.');
        }

        $user = Auth::user();
        if (! $user || $user->role !== 'proctor' || (int) $session->proctor_id !== (int) $user->getAuthIdentifier()) {
            abort(403, 'You are not assigned to this session.');
        }

        return view('proctor.scan.show', [
            'sessionId' => $session_id,
            'session' => $session,
        ]);
    }

    /**
     * GET /proctor/attendance/:session_id — Live attendance for session.
     */
    public function attendance(int $session_id): View|Response
    {
        $session = ExamSession::with(['course', 'room'])
            ->find($session_id);

        if (! $session) {
            throw new NotFoundHttpException('Session not found.');
        }

        $user = Auth::user();
        if (! $user || $user->role !== 'proctor' || (int) $session->proctor_id !== (int) $user->getAuthIdentifier()) {
            abort(403, 'You are not assigned to this session.');
        }

        return view('proctor.attendance.show', [
            'sessionId' => $session_id,
            'session' => $session,
        ]);
    }
}
