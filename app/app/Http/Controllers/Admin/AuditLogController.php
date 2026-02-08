<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Admin UI: audit log browse. Per 09-ui-routes-phase1. Data from GET /api/audit-log.
 */
class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('admin.audit-log.index');
    }
}
