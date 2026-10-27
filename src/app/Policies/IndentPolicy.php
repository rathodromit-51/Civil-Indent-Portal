<?php

namespace App\Policies;

use App\Models\Indent;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IndentPolicy
{
    /**
     * Determine whether the user can view any indents.
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Determine whether the user can view the indent.
     */
    public function view(User $user, Indent $indent): Response
    {
        // Creator and current holder can always view
        if ($user->cpf === $indent->creator_cpf || $user->cpf === $indent->current_holder_cpf) {
            return Response::allow();
        }

        // Super Admin can view all
        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        // Associated workflow participant
        if ($indent->participants()->where('cpf', $user->cpf)->exists()) {
            return Response::allow();
        }

        // Drafts are strictly private to their creator
        if ($indent->is_draft) {
            return Response::deny('Only the creator can view this draft indent.');
        }

        // Any assigned role holder can view published indents
        if ($user->roleAssignment()->exists()) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to view this indent.');
    }

    /**
     * Determine whether the user can create indents.
     */
    public function create(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Determine whether the user can edit/update the indent.
     * Specification Rule FRIND-04:
     * - Drafts are editable ONLY by their creator.
     * - After submission, an indent can NO LONGER be edited.
     */
    public function update(User $user, Indent $indent): Response
    {
        if (!$indent->is_draft) {
            return Response::deny('Submitted indents cannot be edited.');
        }

        if ($user->cpf !== $indent->creator_cpf) {
            return Response::deny('Only the creator can edit this draft indent.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the indent.
     */
    public function delete(User $user, Indent $indent): Response
    {
        if (!$indent->is_draft) {
            return Response::deny('Submitted indents cannot be deleted.');
        }

        if ($user->cpf !== $indent->creator_cpf) {
            return Response::deny('Only the creator can delete this draft indent.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can attach files to the indent.
     * Authorized users include:
     * - Super Administrators.
     * - Creator of the indent.
     * - Current workflow holder (e.g. IM, AE, EE reviewing/acting on the indent).
     * - Recorded workflow participants in the indent chain.
     * - Role holders belonging to this indent's installation or organization.
     */
    public function addAttachment(User $user, Indent $indent): Response
    {
        // 1. Super Admin can always attach
        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        // 2. If indent is draft, only creator can attach
        if ($indent->is_draft) {
            return $user->cpf === $indent->creator_cpf
                ? Response::allow()
                : Response::deny('Only the creator can add attachments to a draft indent.');
        }

        // 3. Current holder or creator can attach files to active indents
        if ($user->cpf === $indent->current_holder_cpf || $user->cpf === $indent->creator_cpf) {
            return Response::allow();
        }

        // 4. Any participant in this indent's workflow
        if ($indent->participants()->where('cpf', $user->cpf)->exists()) {
            return Response::allow();
        }

        // 5. Assigned role holders for this indent's installation or global
        if ($user->roleAssignment()->exists()) {
            $assignment = $user->roleAssignment;
            if ($assignment && ($assignment->installation_id === null || $assignment->installation_id === $indent->installation_id)) {
                return Response::allow();
            }
        }

        return Response::deny('You are not authorized to attach files to this indent.');
    }

    /**
     * Determine whether the user can forward or return the indent.
     * Specification: Only the current holder can forward (Policy).
     */
    public function forward(User $user, Indent $indent): Response
    {
        if ($indent->is_draft) {
            return Response::deny('Draft indents must be submitted before they can be forwarded.');
        }

        if ($indent->status === \App\Enums\IndentStatus::CLOSED) {
            return Response::deny('Closed indents cannot be forwarded.');
        }

        if ($user->cpf !== $indent->current_holder_cpf) {
            return Response::deny('Only the current holder can forward this indent.');
        }

        if ($user->hasRole('CE') && ! $user->isSuperAdmin()) {
            return Response::deny('Civil Engineers (CE) are the terminal execution post and manage work status rather than forwarding.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can reject or forward with rejection.
     * Rule: Only management roles (IM, SAM, SM) holding the indent can reject.
     */
    public function reject(User $user, Indent $indent): Response
    {
        $forwardCheck = $this->forward($user, $indent);
        if ($forwardCheck->denied()) {
            return $forwardCheck;
        }

        if (! $user->isSuperAdmin() && ! $user->hasRole(['IM', 'SAM', 'SM'])) {
            return Response::deny('Only management authorities (IM, SAM, SM) have the authority to reject indents.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the execution status (Accept, In Progress, Completed).
     * Rule: Only the Civil Engineer (CE) holding the indent can update execution status.
     */
    public function updateStatus(User $user, Indent $indent): Response
    {
        if ($indent->is_draft) {
            return Response::deny('Draft indents cannot have their execution status updated.');
        }

        if ($user->cpf !== $indent->current_holder_cpf) {
            return Response::deny('Only the current holder can update the indent status.');
        }

        if (! $user->isSuperAdmin() && ! $user->hasRole('CE')) {
            return Response::deny('Only the assigned Civil Engineer (CE) can update the work execution status.');
        }

        return Response::allow();
    }
}
