<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Admin UI: approval queue — list and detail. Per 09-ui-routes-phase1: /admin/applications, /admin/applications/:id.
 * Data and mutations via API (Alpine/fetch); these actions only return views.
 */
class ApplicationController extends Controller
{
    public function index(): View
    {
        return view('admin.applications.index');
    }

    public function show(string $id): View
    {
        return view('admin.applications.show', ['applicationId' => $id]);
    }
}
