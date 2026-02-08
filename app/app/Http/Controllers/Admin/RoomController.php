<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Admin UI: rooms — list, create, edit.
 * Per 09-ui-routes-phase1: /admin/rooms, /admin/rooms/new, /admin/rooms/:id/edit.
 * Data and mutations via API (Alpine/fetch); these actions only return views.
 */
class RoomController extends Controller
{
    public function index(): View
    {
        return view('admin.rooms.index');
    }

    public function create(): View
    {
        return view('admin.rooms.create');
    }

    public function edit(string $id): View
    {
        return view('admin.rooms.edit', ['roomId' => $id]);
    }
}
