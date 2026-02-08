<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Admin UI: admission periods — list, create, edit.
 * Per 09-ui-routes-phase1: /admin/periods, /admin/periods/new, /admin/periods/:id/edit.
 * Data and mutations via API (Alpine/fetch); these actions only return views.
 */
class AdmissionPeriodController extends Controller
{
    public function index(): View
    {
        return view('admin.periods.index');
    }

    public function create(): View
    {
        return view('admin.periods.create');
    }

    public function edit(string $id): View
    {
        return view('admin.periods.edit', ['periodId' => $id]);
    }
}
