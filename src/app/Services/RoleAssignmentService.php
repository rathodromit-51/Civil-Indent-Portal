<?php

namespace App\Services;

use App\Exceptions\RoleAssignmentException;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Installation;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RoleAssignmentService
{
    /**
     * Assign a role to a user ensuring all domain rules are met.
     *
     * Rules enforced:
     * 1. Target user exists and is active.
     * 2. One role per CPF: Rejects if the CPF already has an active role assignment.
     * 3. One holder per (role, scope) unless role->multi_holder is true.
     * 4. Scope consistency (area/installation/none).
     * 5. Atomicity via DB Transaction.
     *
     * @param  User  $actor  The user performing the assignment
     * @param  array  $data  Payload [cpf, role_id, area_id, installation_id, from, to]
     * @return RoleAssignment
     *
     * @throws RoleAssignmentException
     */
    public function assign(User $actor, array $data): RoleAssignment
    {
        $cpf = trim($data['cpf'] ?? '');
        $targetUser = User::where('cpf', $cpf)->first();

        if (! $targetUser) {
            throw new RoleAssignmentException("User with CPF [{$cpf}] was not found in the system.");
        }

        // Rule 1: One role per CPF
        $existingAssignment = RoleAssignment::with('role')->where('cpf', $cpf)->first();
        if ($existingAssignment) {
            $existingRoleName = $existingAssignment->role?->name ?? 'Assigned Role';
            throw new RoleAssignmentException(
                "User {$targetUser->name} ({$cpf}) already holds the role '{$existingRoleName}'. A user can only hold one role at a time. Remove the current role before assigning a new one."
            );
        }

        $role = Role::find($data['role_id']);
        if (! $role) {
            throw new RoleAssignmentException('Selected role does not exist.');
        }

        // Normalize & Validate Scope
        $areaId = ! empty($data['area_id']) ? (int) $data['area_id'] : null;
        $installationId = ! empty($data['installation_id']) ? (int) $data['installation_id'] : null;

        if ($role->scope_type === 'area') {
            if (! $areaId) {
                throw new RoleAssignmentException("Role '{$role->name}' requires an Area scope.");
            }
            $area = Area::find($areaId);
            if (! $area) {
                throw new RoleAssignmentException("Selected Area [ID {$areaId}] does not exist.");
            }
            $installationId = null;
        } elseif ($role->scope_type === 'installation') {
            if (! $installationId) {
                throw new RoleAssignmentException("Role '{$role->name}' requires an Installation scope.");
            }
            $installation = Installation::find($installationId);
            if (! $installation) {
                throw new RoleAssignmentException("Selected Installation [ID {$installationId}] does not exist.");
            }
            // Auto-link area from installation
            $areaId = $installation->area_id;
        } else {
            // scope_type === 'none'
            $areaId = null;
            $installationId = null;
        }

        // Rule 2: One holder per (role, scope) unless multi_holder is true
        if (! $role->multi_holder) {
            $holderQuery = RoleAssignment::with(['user', 'area', 'installation'])->where('role_id', $role->id);

            if ($role->scope_type === 'none') {
                $existingHolder = $holderQuery->first();
                $scopeDesc = 'organization-wide';
            } elseif ($role->scope_type === 'area') {
                $existingHolder = $holderQuery->where('area_id', $areaId)->first();
                $scopeDesc = 'for Area ' . ($existingHolder?->area?->name ?? Area::find($areaId)?->name ?? "#{$areaId}");
            } elseif ($role->scope_type === 'installation') {
                $existingHolder = $holderQuery->where('installation_id', $installationId)->first();
                $scopeDesc = 'for Installation ' . ($existingHolder?->installation?->name ?? Installation::find($installationId)?->name ?? "#{$installationId}");
            } else {
                $existingHolder = null;
                $scopeDesc = '';
            }

            if ($existingHolder) {
                $holderName = $existingHolder->user?->name ?? $existingHolder->cpf;
                throw new RoleAssignmentException(
                    "Role '{$role->name}' is single-holder ({$scopeDesc}) and is already assigned to {$holderName} ({$existingHolder->cpf}). Only one holder is permitted."
                );
            }
        }

        $from = ! empty($data['from']) ? $data['from'] : now()->toDateString();
        $to = ! empty($data['to']) ? $data['to'] : null;

        // Atomically create assignment & update user designation inside a DB transaction
        return DB::transaction(function () use ($role, $targetUser, $areaId, $installationId, $actor, $from, $to) {
            $assignment = RoleAssignment::create([
                'role_id' => $role->id,
                'cpf' => $targetUser->cpf,
                'area_id' => $areaId,
                'installation_id' => $installationId,
                'assigned_by' => $actor->cpf,
                'from' => $from,
                'to' => $to,
            ]);

            // Keep user designation in sync with their active role
            $targetUser->update([
                'designation' => $role->name,
            ]);

            // Record audit log
            AuditLog::create([
                'who' => $actor->cpf,
                'what' => 'assign',
                'when' => now(),
                'details' => [
                    'target_cpf' => $targetUser->cpf,
                    'target_name' => $targetUser->name,
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'area_id' => $areaId,
                    'area_name' => $areaId ? Area::find($areaId)?->name : null,
                    'installation_id' => $installationId,
                    'installation_name' => $installationId ? Installation::find($installationId)?->name : null,
                    'from' => $from,
                    'to' => $to,
                ],
            ]);

            return $assignment;
        });
    }

    /**
     * Remove a role assignment from a user inside a DB transaction.
     *
     * @param  User  $actor
     * @param  RoleAssignment  $assignment
     * @return void
     */
    public function remove(User $actor, RoleAssignment $assignment): void
    {
        if ($actor->cpf === $assignment->cpf) {
            throw new RoleAssignmentException('You cannot remove your own role assignment.');
        }

        DB::transaction(function () use ($actor, $assignment) {
            $user = User::where('cpf', $assignment->cpf)->first();
            $role = $assignment->role;
            $area = $assignment->area;
            $installation = $assignment->installation;

            $assignment->delete();

            if ($user) {
                $user->update([
                    'designation' => 'Normal User',
                ]);
            }

            // Record audit log
            AuditLog::create([
                'who' => $actor->cpf,
                'what' => 'remove',
                'when' => now(),
                'details' => [
                    'target_cpf' => $assignment->cpf,
                    'target_name' => $user?->name,
                    'role_id' => $assignment->role_id,
                    'role_name' => $role?->name,
                    'area_id' => $assignment->area_id,
                    'area_name' => $area?->name,
                    'installation_id' => $assignment->installation_id,
                    'installation_name' => $installation?->name,
                ],
            ]);
        });
    }

    /**
     * Retrieve the list of roles that the actor is authorized to assign.
     *
     * @param  User  $actor
     * @return Collection
     */
    public function getAssignableRolesFor(User $actor): Collection
    {
        if ($actor->isSuperAdmin()) {
            return Role::orderBy('id')->get();
        }

        $actorRoleName = $actor->roleAssignment?->role?->name;

        if ($actorRoleName === 'SM') {
            return Role::whereIn('name', ['SAM', 'IM'])->orderBy('id')->get();
        }

        if ($actorRoleName === 'SAM') {
            return Role::where('name', 'IM')->get();
        }

        return new Collection();
    }

    /**
     * Retrieve the list of areas available to the actor when assigning.
     *
     * @param  User  $actor
     * @return Collection
     */
    public function getAssignableAreasFor(User $actor): Collection
    {
        if ($actor->isSuperAdmin()) {
            return Area::orderBy('name')->get();
        }

        $actorAreaId = $actor->roleAssignment?->area_id;
        if ($actorAreaId) {
            return Area::where('id', $actorAreaId)->get();
        }

        return new Collection();
    }

    /**
     * Retrieve the list of installations available to the actor when assigning.
     *
     * @param  User  $actor
     * @return Collection
     */
    public function getAssignableInstallationsFor(User $actor): Collection
    {
        if ($actor->isSuperAdmin()) {
            return Installation::with('area')->orderBy('name')->get();
        }

        $actorAreaId = $actor->roleAssignment?->area_id;
        if ($actorAreaId) {
            return Installation::with('area')->where('area_id', $actorAreaId)->orderBy('name')->get();
        }

        return new Collection();
    }
}
