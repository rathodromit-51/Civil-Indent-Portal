<?php

namespace App\Policies;

use App\Models\Installation;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RoleAssignmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the role assignments management page.
     * Allowed: Super Admin, SM, SAM.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->hasRole(['SM', 'SAM']);
    }

    /**
     * Determine whether the user can assign a specific role with the given scope.
     *
     * Rules:
     * - Super Admin assigns anything.
     * - SM assigns SAM and IM (within their own area).
     * - SAM assigns IM only in own area.
     */
    public function assign(User $actor, Role $role, ?int $areaId, ?int $installationId): Response
    {
        // 1. Super Admin assigns anything
        if ($actor->isSuperAdmin()) {
            return Response::allow();
        }

        $actorRoleName = $actor->roleAssignment?->role?->name;
        $actorAreaId = $actor->roleAssignment?->area_id;

        // 2. SM (Surface Manager) assigns SAM and IM in own area
        if ($actorRoleName === 'SM') {
            if (! in_array($role->name, ['SAM', 'IM'], true)) {
                return Response::deny('Surface Managers (SM) can only assign SAM and IM roles.');
            }

            if (! $actorAreaId) {
                return Response::deny('Your Surface Manager account is not associated with an operational area.');
            }

            if ($role->name === 'SAM') {
                if ((int) $areaId !== (int) $actorAreaId) {
                    return Response::deny('Surface Managers can only assign a SAM within their own designated area.');
                }

                return Response::allow();
            }

            if ($role->name === 'IM') {
                $installation = Installation::find($installationId);
                if (! $installation || (int) $installation->area_id !== (int) $actorAreaId) {
                    return Response::deny('Surface Managers can only assign an IM to installations within their own designated area.');
                }

                return Response::allow();
            }
        }

        // 3. SAM (Surface Area Manager) assigns IM only in own area
        if ($actorRoleName === 'SAM') {
            if ($role->name !== 'IM') {
                return Response::deny('Surface Area Managers (SAM) can only assign IM roles.');
            }

            if (! $actorAreaId) {
                return Response::deny('Your Surface Area Manager account is not associated with an operational area.');
            }

            $installation = Installation::find($installationId);
            if (! $installation || (int) $installation->area_id !== (int) $actorAreaId) {
                return Response::deny('Surface Area Managers can only assign an IM to installations within their own area.');
            }

            return Response::allow();
        }

        return Response::deny('You do not have permission to assign roles.');
    }

    /**
     * Determine whether the user can remove a role assignment.
     */
    public function delete(User $actor, RoleAssignment $assignment): Response
    {
        // Prevent users (including Super Admin) from removing their own role to prevent self-lockout
        if ($actor->cpf === $assignment->cpf) {
            return Response::deny('You cannot remove your own role assignment.');
        }

        // Super Admin can remove any other role
        if ($actor->isSuperAdmin()) {
            return Response::allow();
        }

        $actorRoleName = $actor->roleAssignment?->role?->name;
        $actorAreaId = $actor->roleAssignment?->area_id;
        $targetRoleName = $assignment->role?->name;

        // SM can remove SAM or IM in their own area
        if ($actorRoleName === 'SM' && $actorAreaId) {
            if ($targetRoleName === 'SAM' && (int) $assignment->area_id === (int) $actorAreaId) {
                return Response::allow();
            }

            if ($targetRoleName === 'IM') {
                $instAreaId = $assignment->installation?->area_id;
                if ((int) $instAreaId === (int) $actorAreaId) {
                    return Response::allow();
                }
            }

            return Response::deny('Surface Managers can only remove SAM and IM roles within their own operational area.');
        }

        // SAM can remove IM in their own area
        if ($actorRoleName === 'SAM' && $actorAreaId) {
            if ($targetRoleName === 'IM') {
                $instAreaId = $assignment->installation?->area_id;
                if ((int) $instAreaId === (int) $actorAreaId) {
                    return Response::allow();
                }
            }

            return Response::deny('Surface Area Managers can only remove IM roles within their own area.');
        }

        return Response::deny('You do not have permission to remove this role assignment.');
    }
}
