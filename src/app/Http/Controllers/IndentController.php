<?php

namespace App\Http\Controllers;

use App\Enums\IndentPriority;
use App\Enums\IndentStatus;
use App\Enums\MovementAction;
use App\Http\Requests\ForwardIndentRequest;
use App\Http\Requests\StoreIndentRequest;
use App\Http\Requests\UpdateIndentRequest;
use App\Models\Attachment;
use App\Models\Indent;
use App\Models\IndentParticipant;
use App\Models\Installation;
use App\Models\Movement;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
     * Display the "Work Queue" inbox page for currently held indents.
     */
    public function inbox(Request $request): View
    {
        $request->merge(['tab' => 'with-me']);

        return $this->index($request);
    }

    /**
     * Display a listing of indents ("Work Queue", "Created by me", drafts, or all).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Indent::class);

        $user = $request->user();
        $tab = $request->input('tab', 'my');
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $priority = $request->input('priority');

        $query = Indent::with(['installation.area', 'creator', 'currentHolder', 'latestMovement'])
            ->latest('updated_at')
            ->latest('id');

        // Filter by tab
        if ($tab === 'with-me' || $tab === 'holder') {
            // "Work Queue" Inbox (active workflow indents assigned to logged-in user)
            $query->withMe($user->cpf);
        } elseif ($tab === 'my') {
            // "Created by me" (all indents created by logged-in user, both draft and submitted)
            $query->where('creator_cpf', $user->cpf);
        } elseif ($tab === 'drafts') {
            // "My Drafts"
            $query->where('creator_cpf', $user->cpf)->drafts();
        } elseif ($tab === 'flagged') {
            // Flagged with rejection
            $query->where(function ($q) use ($user) {
                $q->where('creator_cpf', $user->cpf)
                  ->orWhere('current_holder_cpf', $user->cpf);
            })->flagged();
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
        $myDraftsCount = Indent::where('creator_cpf', $user->cpf)->drafts()->count();
        $mySubmittedCount = Indent::where('creator_cpf', $user->cpf)->published()->count();
        $withMeCount = Indent::withMe($user->cpf)->count();
        $flaggedCount = Indent::where(function ($q) use ($user) {
            $q->where('creator_cpf', $user->cpf)
              ->orWhere('current_holder_cpf', $user->cpf);
        })->flagged()->count();

        return view('indents.index', [
            'indents' => $indents,
            'tab' => $tab,
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'myCount' => $myCount,
            'myDraftsCount' => $myDraftsCount,
            'mySubmittedCount' => $mySubmittedCount,
            'withMeCount' => $withMeCount,
            'holderCount' => $withMeCount,
            'flaggedCount' => $flaggedCount,
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
            $fromRole = $user->activeRole()?->name ?? $user->roleAssignment?->role?->name ?? 'User';
            $imRoleName = $imAssignment?->role?->name ?? 'IM';

            $movement = Movement::create([
                'indent_id' => $indent->id,
                'from' => $user->cpf,
                'from_role' => $fromRole,
                'to' => $nextHolderCpf,
                'to_role' => $imRoleName,
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
     * Prevents N+1 queries by eager loading all relations in batch.
     */
    public function show(Indent $indent): View
    {
        Gate::authorize('view', $indent);

        $indent->load([
            'installation.area',
            'creator.roleAssignment.role',
            'currentHolder.roleAssignment.role',
            'movements' => fn ($q) => $q->with([
                'fromUser',
                'toUser',
                'attachments',
            ])->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'participants.user.roleAssignment.role',
            'participants.user.roleAssignment.area',
            'participants.user.roleAssignment.installation',
            'attachments.uploaderUser',
        ]);

        // Link indent relation on attachments in memory to prevent N+1 queries during policy checks
        $indent->attachments->each(fn ($att) => $att->setRelation('indent', $indent));

        $currentCpf = auth()->user()?->cpf;
        $earlierParticipants = $indent->participants
            ->where('cpf', '!=', $currentCpf)
            ->values();

        return view('indents.show', [
            'indent' => $indent,
            'earlierParticipants' => $earlierParticipants,
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
            $fromRole = $user->activeRole()?->name ?? $user->roleAssignment?->role?->name ?? 'User';
            $imRoleName = $imAssignment?->role?->name ?? 'IM';

            $movement = Movement::create([
                'indent_id' => $indent->id,
                'from' => $user->cpf,
                'from_role' => $fromRole,
                'to' => $nextHolderCpf,
                'to_role' => $imRoleName,
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

    /**
     * Search active users by CPF, Name, Designation or Role for the recipient picker (AJAX / Fetch).
     * Supports filtering by expected workflow role.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));
        $excludeCpf = $request->input('exclude_cpf', $request->user()?->cpf);
        $indentId = $request->input('indent_id');
        $roleFilter = $request->input('role');

        if (! $roleFilter && $indentId) {
            $indent = Indent::with('currentHolder.roleAssignment.role')->find($indentId);
            $roleFilter = $indent?->getNextExpectedRole();
        }

        $query = User::active();

        if ($roleFilter && $roleFilter !== 'all') {
            $roles = (array) $roleFilter;
            if (in_array('LMW', $roles, true) && ! in_array('LM Works', $roles, true)) {
                $roles[] = 'LM Works';
            }
            if (in_array('LM Works', $roles, true) && ! in_array('LMW', $roles, true)) {
                $roles[] = 'LMW';
            }
            $query->whereHas('roleAssignment.role', fn ($rq) => $rq->whereIn('name', $roles));
        }

        if ($term !== '') {
            $query->search($term);
        }

        if ($excludeCpf) {
            $query->where('cpf', '!=', $excludeCpf);
        }

        $users = $query->with(['roleAssignment.role', 'roleAssignment.area', 'roleAssignment.installation'])
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(function (User $u) {
                $roleName = $u->roleAssignment?->role?->name ?? 'User';
                $roleScope = $u->roleAssignment?->installation?->name
                    ?? $u->roleAssignment?->area?->name
                    ?? $u->location;

                return [
                    'cpf' => $u->cpf,
                    'name' => $u->name,
                    'designation' => $u->designation,
                    'location' => $u->location,
                    'role' => $roleName,
                    'scope' => $roleScope,
                ];
            });

        return response()->json($users);
    }

    /**
     * Forward, forward with rejection, or return an indent.
     * Specification:
     * - Strict sequence: USER -> IM -> SAM -> SM -> HES -> LMW -> ICO -> CE
     * - Only current holder can forward (Policy).
     * - Only IM, SAM, SM can reject (with mandatory reason).
     * - Return to any earlier participant.
     */
    public function forward(ForwardIndentRequest $request, Indent $indent): RedirectResponse
    {
        Gate::authorize('forward', $indent);

        $validated = $request->validated();
        $user = $request->user();
        $recipientCpf = $validated['recipient_cpf'];
        $action = $validated['action'];
        $isRejected = (bool) ($validated['is_rejected'] ?? false);
        $remark = $validated['remark'] ?? null;

        if ($action === MovementAction::REJECT->value) {
            $isRejected = true;
        }

        $recipient = User::with('roleAssignment.role')->where('cpf', $recipientCpf)->first();
        $fromRole = $user->activeRole()?->name ?? $user->roleAssignment?->role?->name ?? 'User';
        $toRole = $recipient?->activeRole()?->name ?? $recipient?->roleAssignment?->role?->name ?? 'User';

        DB::transaction(function () use ($indent, $user, $recipientCpf, $action, $isRejected, $remark, $fromRole, $toRole) {
            // 1. Add a movement
            Movement::create([
                'indent_id' => $indent->id,
                'from' => $user->cpf,
                'from_role' => $fromRole,
                'to' => $recipientCpf,
                'to_role' => $toRole,
                'action' => $action,
                'is_rejected' => $isRejected,
                'remark' => $remark,
            ]);

            // 2. Set current_holder_cpf and flag file if rejected
            $indent->update([
                'current_holder_cpf' => $recipientCpf,
                'is_rejected' => $indent->is_rejected || $isRejected,
            ]);

            // 3. Add recipient to indent_participants
            IndentParticipant::firstOrCreate([
                'indent_id' => $indent->id,
                'cpf' => $recipientCpf,
            ]);
        });

        $recipientName = $recipient ? "{$recipient->name} ({$recipient->cpf})" : $recipientCpf;

        $flashMessage = match (true) {
            $isRejected => "Indent #{$indent->number} has been forwarded with rejection flag to {$recipientName}.",
            $action === MovementAction::RETURN->value => "Indent #{$indent->number} returned to {$recipientName}.",
            default => "Indent #{$indent->number} successfully forwarded to {$recipientName}.",
        };

        return redirect()
            ->route('indents.show', $indent)
            ->with('status', $flashMessage);
    }

    /**
     * Update work execution status (Accept, In Progress, Completed).
     * Exclusively accessible by the assigned Civil Engineer (CE) holding the indent.
     */
    public function updateStatus(Request $request, Indent $indent): RedirectResponse
    {
        Gate::authorize('updateStatus', $indent);

        $validated = $request->validate([
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in([
                IndentStatus::ASSIGNED->value,
                IndentStatus::IN_PROGRESS->value,
                IndentStatus::CLOSED->value,
            ])],
            'remark' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $newStatus = IndentStatus::from($validated['status']);
        $remark = trim((string) ($validated['remark'] ?? ''));

        $action = match ($newStatus) {
            IndentStatus::ASSIGNED => MovementAction::ACCEPT->value,
            IndentStatus::IN_PROGRESS => MovementAction::START_WORK->value,
            IndentStatus::CLOSED => MovementAction::CLOSE->value,
            default => MovementAction::COMMENT->value,
        };

        if ($remark === '') {
            $remark = match ($newStatus) {
                IndentStatus::ASSIGNED => 'Indent work accepted by Civil Engineer.',
                IndentStatus::IN_PROGRESS => 'Civil execution and physical work started on site.',
                IndentStatus::CLOSED => 'Civil work successfully completed and handed over.',
                default => 'Status updated.',
            };
        }

        $userRole = $user->activeRole()?->name ?? $user->roleAssignment?->role?->name ?? 'CE';

        DB::transaction(function () use ($indent, $user, $newStatus, $action, $remark, $request, $userRole) {
            $indent->update([
                'status' => $newStatus,
            ]);

            $movement = Movement::create([
                'indent_id' => $indent->id,
                'from' => $user->cpf,
                'from_role' => $userRole,
                'to' => $user->cpf,
                'to_role' => $userRole,
                'action' => $action,
                'is_rejected' => false,
                'remark' => $remark,
            ]);

            $this->handleAttachments($request, $indent, $movement);
        });

        $statusLabel = match ($newStatus) {
            IndentStatus::ASSIGNED => 'Accepted',
            IndentStatus::IN_PROGRESS => 'In Progress',
            IndentStatus::CLOSED => 'Completed & Closed',
            default => $newStatus->value,
        };

        return redirect()
            ->route('indents.show', $indent)
            ->with('status', "Indent #{$indent->number} status updated to '{$statusLabel}'.");
    }
}

