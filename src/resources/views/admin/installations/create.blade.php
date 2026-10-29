@extends('layouts.app')

@section('title', 'Create Installation - Civil Indent Portal')

@section('content')
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="margin-bottom: 20px;">
            <a href="{{ route('admin.areas.index') }}"
                style="color: var(--primary); text-decoration: none; font-size: 0.875rem; font-weight: 500;">
                &larr; Back to Master Data
            </a>
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-top: 8px;">Create New Installation</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">
                Add an installation facility belonging to a parent operational area.
            </p>
        </div>

        <div class="card">
            <form action="{{ route('admin.installations.store') }}" method="POST">
                @csrf

                <div style="margin-bottom: 20px;">
                    <label for="area_id" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 6px;">
                        Parent Area <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="area_id" name="area_id" required
                        style="width: 100%; padding: 10px 12px; border: 1px solid {{ $errors->has('area_id') ? 'var(--danger)' : 'var(--border-color)' }}; border-radius: 6px; font-size: 0.95rem; background: white;">
                        <option value="">-- Select Parent Area --</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ (old('area_id', $selectedAreaId) == $area->id) ? 'selected' : '' }}>
                                {{ $area->name }} ({{ $area->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('area_id')
                        <div style="color: var(--danger); font-size: 0.8rem; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="name" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 6px;">
                        Installation Name <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                        placeholder="e.g., Gas Processing Unit 1" required
                        style="width: 100%; padding: 10px 12px; border: 1px solid {{ $errors->has('name') ? 'var(--danger)' : 'var(--border-color)' }}; border-radius: 6px; font-size: 0.95rem;">
                    @error('name')
                        <div style="color: var(--danger); font-size: 0.8rem; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom: 24px;">
                    <label for="code" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 6px;">
                        Installation Code <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" id="code" name="code" value="{{ old('code') }}" placeholder="e.g., INST-GPU-1"
                        required
                        style="width: 100%; padding: 10px 12px; border: 1px solid {{ $errors->has('code') ? 'var(--danger)' : 'var(--border-color)' }}; border-radius: 6px; font-size: 0.95rem; text-transform: uppercase;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                        Must be unique across all installations.
                    </div>
                    @error('code')
                        <div style="color: var(--danger); font-size: 0.8rem; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <a href="{{ route('admin.areas.index') }}" class="btn btn-outline">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Create Installation
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection