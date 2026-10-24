<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Installation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallationController extends Controller
{
    /**
     * Show the form for creating a new installation.
     */
    public function create(Request $request): View
    {
        $areas = Area::orderBy('name')->get();
        $selectedAreaId = $request->query('area_id');

        return view('admin.installations.create', [
            'areas' => $areas,
            'selectedAreaId' => $selectedAreaId,
        ]);
    }

    /**
     * Store a newly created installation in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:installations,code'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $installation = Installation::create($validated);

        return redirect()->route('admin.areas.index')
            ->with('status', "Installation '{$installation->name}' ({$installation->code}) created successfully.");
    }

    /**
     * Show the form for editing the specified installation.
     */
    public function edit(Installation $installation): View
    {
        $areas = Area::orderBy('name')->get();

        return view('admin.installations.edit', [
            'installation' => $installation,
            'areas' => $areas,
        ]);
    }

    /**
     * Update the specified installation in storage.
     */
    public function update(Request $request, Installation $installation): RedirectResponse
    {
        $validated = $request->validate([
            'area_id' => ['required', 'exists:areas,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:installations,code,' . $installation->id],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $installation->update($validated);

        return redirect()->route('admin.areas.index')
            ->with('status', "Installation '{$installation->name}' updated successfully.");
    }

    /**
     * Remove the specified installation from storage.
     */
    public function destroy(Installation $installation): RedirectResponse
    {
        $name = $installation->name;
        $installation->delete();

        return redirect()->route('admin.areas.index')
            ->with('status', "Installation '{$name}' deleted successfully.");
    }
}
