<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of all system roles with scopes and assignments.
     */
    public function index(): View
    {
        $roles = Role::with(['assignments.area', 'assignments.installation'])
            ->withCount('assignments')
            ->orderBy('id')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }
}
