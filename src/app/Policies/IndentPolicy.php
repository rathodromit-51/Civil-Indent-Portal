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
        if (! $indent->is_draft) {
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
        if (! $indent->is_draft) {
            return Response::deny('Submitted indents cannot be deleted.');
        }

        if ($user->cpf !== $indent->creator_cpf) {
            return Response::deny('Only the creator can delete this draft indent.');
        }

        return Response::allow();
    }
}
