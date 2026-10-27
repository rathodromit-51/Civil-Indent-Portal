@extends('layouts.app')

@section('title', 'Raise New Civil Indent — Civil Indent Portal')

@push('styles')
<style>
    .form-wrapper {
        max-width: 860px;
        margin: 0 auto 40px;
    }

    .form-header {
        margin-bottom: 24px;
    }

    .form-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 6px;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 640px) {
        .form-grid-2 {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 6px;
        color: #1e293b;
    }

    .form-label .required {
        color: #dc2626;
        margin-left: 2px;
    }

    .form-hint {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 4px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        font-size: 0.875rem;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background-color: #fff;
        color: var(--text-color);
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .form-control.is-invalid {
        border-color: #dc2626;
        background-color: #fffbfb;
    }

    .invalid-feedback {
        color: #dc2626;
        font-size: 0.775rem;
        margin-top: 5px;
        font-weight: 500;
        display: block;
    }

    .actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 24px;
        border-top: 1px solid var(--border-color);
        margin-top: 10px;
        flex-wrap: wrap;
        gap: 12px;
    }
</style>
@endpush

@section('content')
<div class="form-wrapper">
    <div class="form-header">
        <a href="{{ route('indents.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 8px;">
            &larr; Back to Indents
        </a>
        <h1 class="form-title">
            Raise New Civil Indent
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem;">
            Initiate a requisition for civil engineering works. You can save as a draft to edit later, or submit directly to forward to the Installation Manager.
        </p>
    </div>

    <div class="card" style="background: white; border: 1px solid var(--border-color); padding: 32px; border-radius: 10px;">
        <form action="{{ route('indents.store') }}" method="POST" id="indent-form" enctype="multipart/form-data">
            @csrf

            {{-- Title --}}
            <div class="form-group">
                <label for="title" class="form-label">
                    Work Title <span class="required">*</span>
                </label>
                <input type="text"
                       id="title"
                       name="title"
                       value="{{ old('title') }}"
                       class="form-control @error('title') is-invalid @enderror"
                       placeholder="e.g. Repair of Foundation Slab & Drainage Channel at GGS-1"
                       required
                       autofocus>
                <div class="form-hint">A concise, descriptive summary of the required civil work.</div>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Work Type & Installation Grid --}}
            <div class="form-grid-2">
                {{-- Work Type --}}
                <div class="form-group">
                    <label for="work_type" class="form-label">
                        Work Type / Category <span class="required">*</span>
                    </label>
                    <select id="work_type"
                            name="work_type"
                            class="form-control @error('work_type') is-invalid @enderror"
                            required>
                        <option value="" disabled {{ old('work_type') ? '' : 'selected' }}>Select Category...</option>
                        @foreach($workTypes as $wt)
                            <option value="{{ $wt }}" {{ old('work_type') === $wt ? 'selected' : '' }}>
                                {{ $wt }}
                            </option>
                        @endforeach
                    </select>
                    @error('work_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Installation --}}
                <div class="form-group">
                    <label for="installation_id" class="form-label">
                        Target Installation <span class="required">*</span>
                    </label>
                    <select id="installation_id"
                            name="installation_id"
                            class="form-control @error('installation_id') is-invalid @enderror"
                            required>
                        <option value="" disabled {{ old('installation_id') ? '' : 'selected' }}>Select Target Installation...</option>
                        @foreach($installations as $inst)
                            <option value="{{ $inst->id }}" {{ (int) old('installation_id') === $inst->id ? 'selected' : '' }}>
                                {{ $inst->name }} ({{ $inst->code }}) — {{ $inst->area?->name ?? 'General' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-hint">Select the physical plant or site where work will be executed.</div>
                    @error('installation_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Priority & Required By Grid --}}
            <div class="form-grid-2">
                {{-- Priority --}}
                <div class="form-group">
                    <label for="priority" class="form-label">
                        Priority Level <span class="required">*</span>
                    </label>
                    <select id="priority"
                            name="priority"
                            class="form-control @error('priority') is-invalid @enderror"
                            required>
                        @foreach($priorities as $pr)
                            <option value="{{ $pr->value }}" {{ old('priority', 'Medium') === $pr->value ? 'selected' : '' }}>
                                {{ $pr->value }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-hint">Indicates operational urgency for scheduling.</div>
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Required By Date --}}
                <div class="form-group">
                    <label for="required_by" class="form-label">
                        Required Completion Date <span class="required">*</span>
                    </label>
                    <input type="date"
                           id="required_by"
                           name="required_by"
                           value="{{ old('required_by', date('Y-m-d', strtotime('+14 days'))) }}"
                           min="{{ date('Y-m-d') }}"
                           class="form-control @error('required_by') is-invalid @enderror"
                           required>
                    <div class="form-hint">Target date by which work needs to be completed.</div>
                    @error('required_by')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Description --}}
            <div class="form-group">
                <label for="description" class="form-label">
                    Technical Scope & Specifications
                </label>
                <textarea id="description"
                          name="description"
                          rows="4"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Provide detailed technical dimensions, material requirements, site conditions, or specific structural challenges...">{{ old('description') }}</textarea>
                <div class="form-hint">Optional detailed technical specifications.</div>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Justification --}}
            <div class="form-group">
                <label for="justification" class="form-label">
                    Operational Justification
                </label>
                <textarea id="justification"
                          name="justification"
                          rows="3"
                          class="form-control @error('justification') is-invalid @enderror"
                          placeholder="Explain why this work is critical, potential safety hazards, impact on plant operations, or regulatory compliance needs...">{{ old('justification') }}</textarea>
                <div class="form-hint">Why is this work required at this time?</div>
                @error('justification')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Multiple Attachments --}}
            <div class="form-group" style="background: #ffffff; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px;">
                <label for="attachments" class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                    <span>
                        Attachments (Optional, Multiple)
                    </span>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: normal;">
                        Max 100 MB per file
                    </span>
                </label>
                <input type="file"
                       id="attachments"
                       name="attachments[]"
                       multiple
                       class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                       accept=".jpg,.jpeg,.png,.gif,.svg,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.dwg,.dxf,.kml">
                <div class="form-hint" style="margin-top: 6px; line-height: 1.5;">
                    Supported formats: <strong>Images</strong> (JPG, PNG, GIF, SVG, WEBP), <strong>Documents</strong> (PDF, DOC, DOCX), <strong>Spreadsheets</strong> (XLS, XLSX, CSV), <strong>Presentations</strong> (PPT, PPTX), <strong>Archives</strong> (ZIP), <strong>CAD & GIS</strong> (DWG, DXF, KML). Stored on a secure private disk.
                </div>
                @error('attachments')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('attachments.*')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Submission Remarks (Shown when submitting) --}}
            <div class="form-group" style="background: #f8fafc; border: 1px dashed var(--border-color); padding: 16px; border-radius: 8px;">
                <label for="remark" class="form-label">
                    Submission Remarks / Notes (Optional)
                </label>
                <input type="text"
                       id="remark"
                       name="remark"
                       value="{{ old('remark') }}"
                       class="form-control @error('remark') is-invalid @enderror"
                       placeholder="e.g. Urgent review requested due to upcoming monsoon season">
                <div class="form-hint">Recorded in the audit movement history when the indent is forwarded.</div>
                @error('remark')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Action Buttons --}}
            <div class="actions-bar">
                <a href="{{ route('indents.index') }}" class="btn btn-outline" style="padding: 10px 18px;">
                    Cancel
                </a>

                <div style="display: flex; gap: 12px; align-items: center;">
                    {{-- Save as Draft Button --}}
                    <button type="submit"
                            name="action"
                            value="draft"
                            class="btn btn-outline"
                            style="padding: 10px 20px; font-weight: 600; color: #b45309; border-color: #fde68a; background: #fffbeb;">
                        Save as Draft
                    </button>

                    {{-- Submit Button (First forward) --}}
                    <button type="submit"
                            name="action"
                            value="submit"
                            class="btn btn-primary"
                            style="padding: 10px 24px; font-weight: 600;"
                            onclick="return confirm('Submit this indent now? Once submitted, it will be forwarded for review and can no longer be edited (FRIND-04).');">
                        Submit Indent (Forward)
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
