<?php

namespace App\Http\Controllers;

use App\Enums\IndentPriority;
use App\Enums\IndentStatus;
use App\Enums\MovementAction;
use App\Http\Requests\StoreIndentRequest;
use App\Http\Requests\UpdateIndentRequest;
use App\Models\Attachment;
use App\Models\Indent;
use App\Models\IndentParticipant;
use App\Models\Installation;
use App\Models\Movement;
use App\Models\RoleAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class IndentController extends Controller
{
    /**
     * Common standard civil work types.
     */
    protected array $workTypes = [
        'Civil Maintenance',
        'Structural Repair',
        'Plumbing & Drainage',
        'Road & Pavement',
        'Painting & Finishing',
        'Masonry & Concreting',
        'Roofing & Waterproofing',
        'Flooring & Tiling',
        'Sanitary & Water Supply',
        'Earthwork & Excavation',
        'Other Civil Works',
    ];

    /**
     * Display a listing of indents ("Created by me" list with pagination).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Indent::class);

        $user = $request->user();
        $tab = $request->input('tab', 'my');
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $priority = $request->input('priority');

        $query = Indent::with(['installation.area', 'creator', 'currentHolder'])
            ->latest('updated_at')
            ->latest('id');

        // Filter by tab
        if ($tab === 'my') {
            // "Created by me" (all indents created by logged-in user, both draft and submitted)
            $query->where('creator_cpf', $user->cpf);
        } elseif ($tab === 'drafts') {
            // "My Drafts"
            $query->where('creator_cpf', $user->cpf)->where('is_draft', true);
        } elseif ($tab === 'holder') {
            // "Held by me" (active workflow indents assigned to logged-in user)
            $query->where('current_holder_cpf', $user->cpf)->where('is_draft', false);
        } elseif ($tab === 'all') {
            // Published indents or user's own drafts
            $query->where(function ($q) use ($user) {
                $q->where('is_draft', false)
                  ->orWhere('creator_cpf', $user->cpf);
            });
        }

        // Search by number, title, work type, or installation
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('work_type', 'like', "%{$search}%")
                  ->orWhereHas('installation', fn ($iq) => $iq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter by status
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by priority
        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        $indents = $query->paginate(10)->withQueryString();

        // Counts for tab badges
        $myCount = Indent::where('creator_cpf', $user->cpf)->count();
        $myDraftsCount = Indent::where('creator_cpf', $user->cpf)->where('is_draft', true)->count();
        $mySubmittedCount = Indent::where('creator_cpf', $user->cpf)->where('is_draft', false)->count();
        $holderCount = Indent::where('current_holder_cpf', $user->cpf)->where('is_draft', false)->count();

        return view('indents.index', [
            'indents' => $indents,
            'tab' => $tab,
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'myCount' => $myCount,
            'myDraftsCount' => $myDraftsCount,
            'mySubmittedCount' => $mySubmittedCount,
            'holderCount' => $holderCount,
            'statuses' => IndentStatus::cases(),
            'priorities' => IndentPriority::cases(),
        ]);
    }

    /**
     * Show the form for creating a new indent.
     */
    public function create(): View
    {
        Gate::authorize('create', Indent::class);

        $installations = Installation::with('area')->orderBy('name')->get();
        $priorities = IndentPriority::cases();
        $workTypes = $this->workTypes;

        return view('indents.create', [
            'installations' => $installations,
            'priorities' => $priorities,
            'workTypes' => $workTypes,
        ]);
    }

    /**
     * Store a newly created indent (Save as Draft OR Submit).
     */
    public function store(StoreIndentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Indent::class);

        $validated = $request->validated();
        $user = $request->user();
        $action = $validated['action']; // 'draft' or 'submit'

        if ($action === 'draft') {
            // Save as Draft (editable only by creator)
            $indent = Indent::create([
                'title' => $validated['title'],
                'work_type' => $validated['work_type'],
                'installation_id' => $validated['installation_id'],
                'priority' => $validated['priority'],
                'required_by' => $validated['required_by'],
                'description' => $validated['description'] ?? null,
                'justification' => $validated['justification'] ?? null,
                'status' => IndentStatus::OPEN,
                'is_draft' => true,
                'creator_cpf' => $user->cpf,
                'current_holder_cpf' => $user->cpf,
            ]);

            IndentParticipant::firstOrCreate([
                'indent_id' => $indent->id,
                'cpf' => $user->cpf,
            ]);

            // Save uploaded attachments to private disk
            $this->handleAttachments($request, $indent);

            return redirect()
                ->route('indents.index', ['tab' => 'drafts'])
                ->with('status', "Draft indent #{$indent->number} has been saved successfully.");
        }

        // Action: Submit (First forward)
        // Find designated Installation Manager (IM) for the chosen installation
        $imAssignment = RoleAssignment::where('installation_id', $validated['installation_id'])
            ->whereHas('role', fn ($q) => $q->where('name', 'IM'))
            ->first();

        $nextHolderCpf = $imAssignment?->cpf ?? $user->cpf;

        $indent = DB::transaction(function () use ($validated, $user, $nextHolderCpf, $request) {
            $indent = Indent::create([
                'title' => $validated['title'],
                'work_type' => $validated['work_type'],
                'installation_id' => $validated['installation_id'],
                'priority' => $validated['priority'],
                'required_by' => $validated['required_by'],
                'description' => $validated['description'] ?? null,
                'justification' => $validated['justification'] ?? null,
                'status' => IndentStatus::OPEN,
                'is_draft' => false,
                'creator_cpf' => $user->cpf,
                'current_holder_cpf' => $nextHolderCpf,
            ]);

            // Create initial movement (First forward)
            $movement = Movement::create([
                'indent_id' => $indent->id,
                'from' => $user->cpf,
                'to' => $nextHolderCpf,
                'action' => MovementAction::SUBMIT->value,
                'is_rejected' => false,
                'remark' => $request->input('remark') ?: 'Initial indent submission',
            ]);

            IndentParticipant::firstOrCreate([
                'indent_id' => $indent->id,
                'cpf' => $user->cpf,
            ]);

            if ($nextHolderCpf && $nextHolderCpf !== $user->cpf) {
                IndentParticipant::firstOrCreate([
                    'indent_id' => $indent->id,
                    'cpf' => $nextHolderCpf,
                ]);
            }

            // Save uploaded attachments to private disk
            $this->handleAttachments($request, $indent, $movement);

            return $indent;
        });

        return redirect()
            ->route('indents.show', $indent)
            ->with('status', "Indent #{$indent->number} submitted successfully and forwarded for review.");
    }

    /**
     * Display the specified indent.
     */
    public function show(Indent $indent): View
    {
        Gate::authorize('view', $indent);

        $indent->load([
            'installation.area',
            'creator',
            'currentHolder',
            'movements' => fn ($q) => $q->with(['fromUser', 'toUser', 'attachments'])->latest('created_at')->latest('id'),
            'participants.user',
            'attachments.uploaderUser',
        ]);

        return view('indents.show', [
            'indent' => $indent,
        ]);
    }

    /**
     * Show the form for editing a draft indent.
     * Specification Rule FRIND-04:
     * - Only draft indents are editable.
     * - Editable only by creator.
     */
    public function edit(Indent $indent): View
    {
        Gate::authorize('update', $indent);

        $indent->load(['attachments.uploaderUser']);
        $installations = Installation::with('area')->orderBy('name')->get();
        $priorities = IndentPriority::cases();
        $workTypes = $this->workTypes;

        return view('indents.edit', [
            'indent' => $indent,
            'installations' => $installations,
            'priorities' => $priorities,
            'workTypes' => $workTypes,
        ]);
    }

    /**
     * Update the draft indent (Save as Draft OR Submit).
     * Specification Rule FRIND-04:
     * - Once submitted, it can NO LONGER be edited.
     */
    public function update(UpdateIndentRequest $request, Indent $indent): RedirectResponse
    {
        Gate::authorize('update', $indent);

        $validated = $request->validated();
        $user = $request->user();
        $action = $validated['action']; // 'draft' or 'submit'

        if ($action === 'draft') {
            $indent->update([
                'title' => $validated['title'],
                'work_type' => $validated['work_type'],
                'installation_id' => $validated['installation_id'],
                'priority' => $validated['priority'],
                'required_by' => $validated['required_by'],
                'description' => $validated['description'] ?? null,
                'justification' => $validated['justification'] ?? null,
            ]);

            // Save any newly added attachments
            $this->handleAttachments($request, $indent);

            return redirect()
                ->route('indents.index', ['tab' => 'drafts'])
                ->with('status', "Draft indent #{$indent->number} has been updated successfully.");
        }

        // Action: Submit (Convert draft to submitted, trigger first forward)
        $imAssignment = RoleAssignment::where('installation_id', $validated['installation_id'])
            ->whereHas('role', fn ($q) => $q->where('name', 'IM'))
            ->first();

        $nextHolderCpf = $imAssignment?->cpf ?? $user->cpf;

        DB::transaction(function () use ($indent, $validated, $user, $nextHolderCpf, $request) {
            $indent->update([
                'title' => $validated['title'],
                'work_type' => $validated['work_type'],
                'installation_id' => $validated['installation_id'],
                'priority' => $validated['priority'],
                'required_by' => $validated['required_by'],
                'description' => $validated['description'] ?? null,
                'justification' => $validated['justification'] ?? null,
                'is_draft' => false,
                'current_holder_cpf' => $nextHolderCpf,
            ]);

            // Create first forward movement
            $movement = Movement::create([
                'indent_id' => $indent->id,
                'from' => $user->cpf,
                'to' => $nextHolderCpf,
                'action' => MovementAction::SUBMIT->value,
                'is_rejected' => false,
                'remark' => $request->input('remark') ?: 'Initial indent submission from draft',
            ]);

            IndentParticipant::firstOrCreate([
                'indent_id' => $indent->id,
                'cpf' => $user->cpf,
            ]);

            if ($nextHolderCpf && $nextHolderCpf !== $user->cpf) {
                IndentParticipant::firstOrCreate([
                    'indent_id' => $indent->id,
                    'cpf' => $nextHolderCpf,
                ]);
            }

            // Save any newly added attachments linked to the submit movement
            $this->handleAttachments($request, $indent, $movement);
        });

        return redirect()
            ->route('indents.show', $indent)
            ->with('status', "Indent #{$indent->number} has been submitted and forwarded for review. It can no longer be edited (FRIND-04).");
    }

    /**
     * Remove the specified draft indent from storage.
     */
    public function destroy(Indent $indent): RedirectResponse
    {
        Gate::authorize('delete', $indent);

        $number = $indent->number;
        $indent->delete();

        return redirect()
            ->route('indents.index')
            ->with('status', "Draft indent #{$number} has been deleted.");
    }

    /**
     * Store uploaded attachments to private disk and record Attachment models.
     */
    protected function handleAttachments(Request $request, Indent $indent, ?Movement $movement = null): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        $user = $request->user();

        foreach ($request->file('attachments') as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $size = $file->getSize();
            $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';

            // Store strictly on private filesystem disk under indents/{indent_id}/
            $storedPath = $file->store("indents/{$indent->id}", 'private');

            Attachment::create([
                'indent_id' => $indent->id,
                'movement_id' => $movement?->id,
                'uploader' => $user->cpf,
                'original_name' => $originalName,
                'stored_path' => $storedPath,
                'size' => $size,
                'mime_type' => $mime,
            ]);
        }
    }
}
