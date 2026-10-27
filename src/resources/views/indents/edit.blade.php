@extends('layouts.app')

@section('title', 'Edit Draft Indent ' . $indent->number . ' — Civil Indent Portal')

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
            Edit Draft Indent <span style="font-family: monospace; color: var(--primary);">{{ $indent->number }}</span>
        </h1>
        <div class="alert" style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 12px 16px; border-radius: 8px; margin-top: 12px; font-size: 0.85rem;">
            <strong>Specification Rule FRIND-04:</strong> This indent is currently a draft and can be edited freely. <em>Once submitted, it can no longer be edited</em> and will be forwarded to the Installation Manager.
        </div>
    </div>

    <div class="card" style="background: white; border: 1px solid var(--border-color); padding: 32px; border-radius: 10px;">
        <form action="{{ route('indents.update', $indent) }}" method="POST" id="edit-indent-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Title --}}
            <div class="form-group">
                <label for="title" class="form-label">
                    Work Title <span class="required">*</span>
                </label>
                <input type="text"
                       id="title"
                       name="title"
                       value="{{ old('title', $indent->title) }}"
                       class="form-control @error('title') is-invalid @enderror"
                       required
                       autofocus>
                <div class="form-hint">A concise summary of the required civil work.</div>
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
                        @foreach($workTypes as $wt)
                            <option value="{{ $wt }}" {{ old('work_type', $indent->work_type) === $wt ? 'selected' : '' }}>
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
                        @foreach($installations as $inst)
                            <option value="{{ $inst->id }}" {{ (int) old('installation_id', $indent->installation_id) === $inst->id ? 'selected' : '' }}>
                                {{ $inst->name }} ({{ $inst->code }}) — {{ $inst->area?->name ?? 'General' }}
                            </option>
                        @endforeach
                    </select>
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
                            <option value="{{ $pr->value }}" {{ old('priority', $indent->priority?->value) === $pr->value ? 'selected' : '' }}>
                                {{ $pr->value }}
                            </option>
                        @endforeach
                    </select>
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
                           value="{{ old('required_by', $indent->required_by ? $indent->required_by->format('Y-m-d') : '') }}"
                           class="form-control @error('required_by') is-invalid @enderror"
                           required>
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
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $indent->description) }}</textarea>
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
                          class="form-control @error('justification') is-invalid @enderror">{{ old('justification', $indent->justification) }}</textarea>
                @error('justification')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Attachments Section --}}
            <div class="form-group" style="background: #ffffff; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px;">
                <label for="attachments" class="form-label" style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        Attachments (Max 100 MB per file)
                    </span>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: normal;">
                        Multiple allowed
                    </span>
                </label>

                {{-- Existing Attachments --}}
                @if($indent->attachments->isNotEmpty())
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">
                            Attached Files ({{ $indent->attachments->count() }})
                        </div>
                        <div style="display: grid; gap: 8px;">
                            @foreach($indent->attachments as $att)
                                <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 6px;">
                                    <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <a href="{{ route('attachments.download', $att) }}" style="font-weight: 600; color: var(--primary); text-decoration: none; font-size: 0.85rem;">
                                                {{ $att->original_name }}
                                            </a>
                                            <span style="font-size: 0.75rem; color: #94a3b8; margin-left: 6px;">
                                                ({{ $att->formatted_size }})
                                            </span>
                                        </div>
                                    </div>
                                    <a href="{{ route('attachments.download', $att) }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem; background: white;">
                                        Download
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Upload More Attachments --}}
                <div style="margin-top: 8px;">
                    <label for="attachments" style="font-size: 0.8rem; font-weight: 600; color: #334155; display: block; margin-bottom: 4px;">
                        Upload Additional Files:
                    </label>
                    <input type="file"
                           id="attachments"
                           name="attachments[]"
                           multiple
                           class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                           accept=".jpg,.jpeg,.png,.gif,.svg,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.dwg,.dxf,.kml">
                    <div class="form-hint" style="margin-top: 6px;">
                        Supported formats: Images, PDF, Word, Excel, PowerPoint, ZIP, CAD (DWG, DXF), GIS (KML). Max 100 MB per file.
                    </div>
                    @error('attachments')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('attachments.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Submission Remarks --}}
            <div class="form-group" style="background: #f8fafc; border: 1px dashed var(--border-color); padding: 16px; border-radius: 8px;">
                <label for="remark" class="form-label">
                    Submission Remarks / Notes (Applied when submitting)
                </label>
                <input type="text"
                       id="remark"
                       name="remark"
                       value="{{ old('remark') }}"
                       class="form-control @error('remark') is-invalid @enderror"
                       placeholder="e.g. Revised requirements and submitting for technical approval">
                @error('remark')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="actions-bar">
                <a href="{{ route('indents.index') }}" class="btn btn-outline" style="padding: 10px 18px;">
                    Cancel
                </a>

                <div style="display: flex; gap: 12px; align-items: center;">
                    {{-- Save as Draft --}}
                    <button type="submit"
                            name="action"
                            value="draft"
                            class="btn btn-outline"
                            style="padding: 10px 20px; font-weight: 600; color: #b45309; border-color: #fde68a; background: #fffbeb;">
                        Update Draft
                    </button>

                    {{-- Submit (First forward) --}}
                    <button type="submit"
                            name="action"
                            value="submit"
                            class="btn btn-primary"
                            style="padding: 10px 24px; font-weight: 600;"
                            onclick="return confirm('Submit this indent now? Once submitted, it can NO LONGER be edited (FRIND-04).');">
                        Submit Indent (Finalize & Forward)
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
