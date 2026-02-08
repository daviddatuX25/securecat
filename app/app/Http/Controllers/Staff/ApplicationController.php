<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AdmissionPeriod;
use App\Models\Course;
use Illuminate\Contracts\View\View;

/**
 * Staff UI: list and read-only view of applications encoded by current user.
 * Per 09-ui-routes-phase1: /staff/applications, /staff/applications/:id.
 * Revise: edit application when status=revision_requested (resubmit to pending_review).
 * Data via API (Alpine/fetch); these actions only return views.
 */
class ApplicationController extends Controller
{
    public function index(): View
    {
        return view('staff.applications.index');
    }

    public function show(string $id): View
    {
        return view('staff.applications.show', ['applicationId' => $id]);
    }

    /**
     * Revise application (status must be revision_requested). Staff can edit and resubmit.
     */
    public function revise(string $id): View
    {
        $courses = Course::query()
            ->whereHas('admissionPeriod', fn ($q) => $q->where('status', AdmissionPeriod::STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'admission_period_id']);

        return view('staff.applications.revise', ['applicationId' => $id, 'courses' => $courses]);
    }
}
