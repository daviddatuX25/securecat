<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Admin UI: reports — roster, attendance. Per 09-ui-routes-phase1. Data from API.
 */
class ReportController extends Controller
{
    public function roster(): View
    {
        return view('admin.reports.roster');
    }

    /**
     * GET /admin/reports/attendance — Select session → attendance (scanned yes/no + time). Per 09-ui-routes-phase1.
     */
    public function attendance(): View
    {
        return view('admin.reports.attendance');
    }
}
