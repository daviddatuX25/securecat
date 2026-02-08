<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;

/**
 * Admin UI: exam sessions — list, create, edit (assign proctor).
 * Per 09-ui-routes-phase1: /admin/sessions, /admin/sessions/new, /admin/sessions/:id/edit.
 * Data and mutations via API (Alpine/fetch); these actions return views.
 * Proctors are passed server-side for dropdown (no Phase 1 API for listing users).
 */
class ExamSessionController extends Controller
{
    public function index(): View
    {
        return view('admin.sessions.index');
    }

    public function create(): View
    {
        $proctors = User::where('role', User::ROLE_PROCTOR)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.sessions.create', ['proctors' => $proctors]);
    }

    public function edit(string $id): View
    {
        $proctors = User::where('role', User::ROLE_PROCTOR)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.sessions.edit', ['sessionId' => $id, 'proctors' => $proctors]);
    }
}
