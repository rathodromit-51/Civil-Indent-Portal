<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    /**
     * Display a listing of areas, installations, and roles.
     */
    public function index(): View
    {
        $areas = Area::with(['installations' => function ($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();

        $roles = Role::withCount('assignments')->orderBy('name')->get();

        return view('admin.areas.index', [
            'areas' => $areas,
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new area.
     */
    public function create(): View
    {
        return view('admin.areas.create');
    }

    /**
     * Store a newly created area in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:areas,code'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        Area::create($validated);

        return redirect()->route('admin.areas.index')
            ->with('status', "Area '{$validated['name']}' ({$validated['code']}) created successfully.");
    }

    /**
     * Show the form for editing the specified area.
     */
    public function edit(Area $area): View
    {
        return view('admin.areas.edit', [
            'area' => $area,
        ]);
    }

    /**
     * Update the specified area in storage.
     */
    public function update(Request $request, Area $area): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:areas,code,' . $area->id],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $area->update($validated);

        return redirect()->route('admin.areas.index')
            ->with('status', "Area '{$area->name}' updated successfully.");
    }

    /**
     * Remove the specified area from storage.
     */
    public function destroy(Area $area): RedirectResponse
    {
        $name = $area->name;
        $area->delete();

        return redirect()->route('admin.areas.index')
            ->with('status', "Area '{$name}' deleted successfully.");
    }
}
