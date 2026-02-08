<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Admin UI: courses — list, create, edit.
 * Per 09-ui-routes-phase1: /admin/courses, /admin/courses/new, /admin/courses/:id/edit.
 * Data and mutations via API (Alpine/fetch); these actions only return views.
 */
class CourseController extends Controller
{
    public function index(): View
    {
        return view('admin.courses.index');
    }

    public function create(): View
    {
        return view('admin.courses.create');
    }

    public function edit(string $id): View
    {
        return view('admin.courses.edit', ['courseId' => $id]);
    }
}
