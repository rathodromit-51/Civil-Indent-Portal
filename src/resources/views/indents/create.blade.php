@extends('layouts.app')

@section('title', 'Raise New Civil Indent — Civil Indent Portal')

@push('styles')
    <style>
        .form-wrapper {
            max-width: 860px;
            margin: 0 auto 40px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 12px;
            transition: color 0.15s ease;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .form-header {
            margin-bottom: 24px;
        }

        .form-title {
            font-size: 1.625rem;
            font-weight: 700;
            color: var(--text-color);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .form-subtitle {
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .form-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
            padding: 32px;
        }

        .section-header {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--primary);
            margin: 28px 0 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header:first-of-type {
            margin-top: 0;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 640px) {
            .form-card {
                padding: 20px;
            }
            .form-grid-2 {
                grid-template-columns: 1fr;
                gap: 16px;
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
            color: var(--danger);
            margin-left: 2px;
            font-weight: 700;
        }

        .form-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 5px;
            line-height: 1.4;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.875rem;
            font-family: inherit;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background-color: #ffffff;
            color: var(--text-color);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .form-control.is-invalid {
            border-color: var(--danger);
            background-color: #fffbfb;
        }

        textarea.form-control {
            line-height: 1.6;
            resize: vertical;
        }

        select.form-control {
            cursor: pointer;
        }

        .invalid-feedback {
            color: var(--danger);
            font-size: 0.775rem;
            margin-top: 5px;
            font-weight: 500;
            display: block;
        }

        /* Upload Zone */
        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            padding: 24px 20px;
            text-align: center;
            transition: all 0.2s ease;
            position: relative;
            cursor: pointer;
        }

        .upload-zone:hover {
            border-color: var(--primary);
            background: #f0f7ff;
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .upload-icon {
            width: 36px;
            height: 36px;
            margin: 0 auto 8px;
            color: var(--primary);
        }

        .upload-text {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
        }

        .upload-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .file-selected-text {
            margin-top: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Remark Box */
        .remark-box {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px 18px;
        }

        /* Actions Bar */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            margin-top: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .actions-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-draft {
            background: #ffffff;
            border: 1px solid #d97706;
            color: #d97706;
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .btn-draft:hover {
            background: #fffbeb;
            color: #b45309;
            border-color: #b45309;
        }

        .btn-submit-forward {
            background: var(--primary);
            border: 1px solid var(--primary);
            color: #ffffff;
            padding: 10px 24px;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25);
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .btn-submit-forward:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.35);
        }
    </style>
@endpush

@section('content')
    <div class="form-wrapper">
        <a href="{{ route('indents.index') }}" class="back-link">
            &larr; Back to Indents
        </a>

        <div class="form-header">
            <h1 class="form-title">Raise New Civil Indent</h1>
            <p class="form-subtitle">
                Initiate a requisition for civil engineering works. You can save as a draft to edit later, or submit directly to forward to the Installation Manager.
            </p>
        </div>

        <div class="form-card">
            <form action="{{ route('indents.store') }}" method="POST" id="indent-form" enctype="multipart/form-data">
                @csrf

                {{-- Section 1: Classification & Location --}}
                <div class="section-header">1. Classification & Location</div>

                {{-- Title --}}
                <div class="form-group">
                    <label for="title" class="form-label">
                        Work Title <span class="required">*</span>
                    </label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}"
                        class="form-control @error('title') is-invalid @enderror"
                        placeholder="e.g. Repair of Foundation Slab & Drainage Channel at GGS-1" required autofocus>
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
                        <select id="work_type" name="work_type"
                            class="form-control @error('work_type') is-invalid @enderror" required>
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
                        <select id="installation_id" name="installation_id"
                            class="form-control @error('installation_id') is-invalid @enderror" required>
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

                {{-- Section 2: Priority & Target Timeline --}}
                <div class="section-header">2. Priority & Scheduling</div>

                <div class="form-grid-2">
                    {{-- Priority --}}
                    <div class="form-group">
                        <label for="priority" class="form-label">
                            Priority Level <span class="required">*</span>
                        </label>
                        <select id="priority" name="priority" class="form-control @error('priority') is-invalid @enderror" required>
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
                        <input type="date" id="required_by" name="required_by"
                            value="{{ old('required_by', date('Y-m-d', strtotime('+14 days'))) }}" min="{{ date('Y-m-d') }}"
                            class="form-control @error('required_by') is-invalid @enderror" required>
                        <div class="form-hint">Target date by which work needs to be completed.</div>
                        @error('required_by')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Section 3: Technical Scope & Operational Justification --}}
                <div class="section-header">3. Scope & Justification</div>

                {{-- Description --}}
                <div class="form-group">
                    <label for="description" class="form-label">
                        Technical Scope & Specifications
                    </label>
                    <textarea id="description" name="description" rows="4"
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
                    <textarea id="justification" name="justification" rows="3"
                        class="form-control @error('justification') is-invalid @enderror"
                        placeholder="Explain why this work is critical, potential safety hazards, impact on plant operations, or regulatory compliance needs...">{{ old('justification') }}</textarea>
                    <div class="form-hint">Why is this work required at this time?</div>
                    @error('justification')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Section 4: Attachments & Remarks --}}
                <div class="section-header">4. Supporting Documents & Remarks</div>

                {{-- Multiple Attachments --}}
                <div class="form-group">
                    <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Attachments (Optional)</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Max 100 MB per file</span>
                    </label>

                    <div class="upload-zone" id="upload-zone">
                        <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <div class="upload-text">Click or drag files here to attach</div>
                        <div class="upload-meta">
                            Supports Images (JPG, PNG), Documents (PDF, Word, Excel), CAD (DWG, DXF) & ZIP
                        </div>
                        <input type="file" id="attachments" name="attachments[]" multiple
                            class="@error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                            accept=".jpg,.jpeg,.png,.gif,.svg,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.dwg,.dxf,.kml"
                            onchange="updateFileCount(this)">
                        <div id="file-count-label" class="file-selected-text" style="display: none;"></div>
                    </div>

                    @error('attachments')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('attachments.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Submission Remarks --}}
                <div class="form-group">
                    <div class="remark-box">
                        <label for="remark" class="form-label">
                            Submission Remarks / Notes (Optional)
                        </label>
                        <input type="text" id="remark" name="remark" value="{{ old('remark') }}"
                            class="form-control @error('remark') is-invalid @enderror"
                            placeholder="e.g. Urgent review requested due to upcoming monsoon season">
                        <div class="form-hint">Recorded in the audit movement history when forwarded.</div>
                        @error('remark')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="actions-bar">
                    <a href="{{ route('indents.index') }}" class="btn btn-outline" style="padding: 10px 18px; border-radius: 8px;">
                        Cancel
                    </a>

                    <div class="actions-right">
                        {{-- Save as Draft Button --}}
                        <button type="submit" name="action" value="draft" class="btn btn-draft">
                            Save as Draft
                        </button>

                        {{-- Submit Button (First forward) --}}
                        <button type="submit" name="action" value="submit" class="btn btn-submit-forward"
                            onclick="return confirm('Submit this indent now? Once submitted, it will be forwarded for review and can no longer be edited (FRIND-04).');">
                            Submit Indent (Forward)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateFileCount(input) {
            const label = document.getElementById('file-count-label');
            if (input.files && input.files.length > 0) {
                label.style.display = 'block';
                label.textContent = input.files.length === 1 
                    ? `✓ 1 file selected: ${input.files[0].name}`
                    : `✓ ${input.files.length} files selected`;
            } else {
                label.style.display = 'none';
            }
        }
    </script>
@endsection