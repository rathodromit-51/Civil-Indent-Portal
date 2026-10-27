<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttachmentPolicy
{
    /**
     * Determine whether the user can download/view the attachment.
     * Enforces that users outside the indent workflow chain receive a 403 Forbidden.
     */
    public function view(User $user, Attachment $attachment): Response
    {
        $indent = $attachment->indent;

        if (! $indent) {
            return Response::deny('Associated indent not found.');
        }

        // 1. Creator and current workflow holder can always view/download
        if ($user->cpf === $indent->creator_cpf || $user->cpf === $indent->current_holder_cpf) {
            return Response::allow();
        }

        // 2. Super Administrator can view all
        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        // 3. Any recorded participant in the indent workflow chain
        if ($indent->participants()->where('cpf', $user->cpf)->exists()) {
            return Response::allow();
        }

        // 4. Draft indent attachments are strictly private to creator
        if ($indent->is_draft) {
            return Response::deny('Draft indent attachments are only accessible by the creator.');
        }

        // 5. Authorized role holders in the organization can inspect published indents
        if ($user->roleAssignment()->exists()) {
            return Response::allow();
        }

        // Outside the chain
        return Response::deny('You do not have permission to access or download this attachment.');
    }

    /**
     * Determine whether the user can delete the attachment.
     */
    public function delete(User $user, Attachment $attachment): Response
    {
        $indent = $attachment->indent;

        // Can only delete from draft indents by the creator
        if ($indent && $indent->is_draft && $user->cpf === $indent->creator_cpf) {
            return Response::allow();
        }

        return Response::deny('Attachments cannot be removed once submitted or by unauthorized users.');
    }
}
