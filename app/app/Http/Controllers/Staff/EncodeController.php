<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AdmissionPeriod;
use App\Models\Course;
use Illuminate\Contracts\View\View;

/**
 * Staff UI: encode applicant form.
 * Per 09-ui-routes-phase1: /staff/encode.
 * Courses from active admission periods (staff cannot call GET /api/courses).
 */
class EncodeController extends Controller
{
    public function create(): View
    {
        $courses = Course::query()
            ->whereHas('admissionPeriod', fn ($q) => $q->where('status', AdmissionPeriod::STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'admission_period_id']);

        return view('staff.encode', ['courses' => $courses]);
    }
}
