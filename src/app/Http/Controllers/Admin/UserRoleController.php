<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RoleAssignmentException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserRoleController extends Controller
{
    public function __construct(
        protected RoleAssignmentService $roleAssignmentService
    ) {}

    /**
     * Display the Users & Roles management screen with CPF/Name search.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', RoleAssignment::class);

        $search = trim((string) $request->input('search', ''));

        $usersQuery = User::with([
            'roleAssignment.role',
            'roleAssignment.area',
            'roleAssignment.installation',
            'roleAssignment.assigner',
        ])->orderBy('name');

        if ($search !== '') {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('cpf', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $users = $usersQuery->paginate(15)->withQueryString();

        $actor = $request->user();
        $assignableRoles = $this->roleAssignmentService->getAssignableRolesFor($actor);
        $assignableAreas = $this->roleAssignmentService->getAssignableAreasFor($actor);
        $assignableInstallations = $this->roleAssignmentService->getAssignableInstallationsFor($actor);

        // Summary stats
        $totalUsers = User::count();
        $assignedUsersCount = RoleAssignment::distinct('cpf')->count('cpf');
        $unassignedUsersCount = max(0, $totalUsers - $assignedUsersCount);

        // Audit Logs (paginated at 7 logs per page)
        $auditLogs = AuditLog::with('actor')
            ->latest('when')
            ->latest('id')
            ->paginate(7, ['*'], 'logs_page')
            ->withQueryString();

        return view('admin.users-roles.index', [
            'users' => $users,
            'search' => $search,
            'assignableRoles' => $assignableRoles,
            'assignableAreas' => $assignableAreas,
            'assignableInstallations' => $assignableInstallations,
            'actor' => $actor,
            'totalUsers' => $totalUsers,
            'assignedUsersCount' => $assignedUsersCount,
            'unassignedUsersCount' => $unassignedUsersCount,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Assign a role to a specified CPF.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', RoleAssignment::class);

        $validated = $request->validate([
            'cpf' => ['required', 'string', 'exists:users,cpf'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'installation_id' => ['nullable', 'integer', 'exists:installations,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        // Check policy authorization
        $response = Gate::inspect('assign', [
            RoleAssignment::class,
            $role,
            ! empty($validated['area_id']) ? (int) $validated['area_id'] : null,
            ! empty($validated['installation_id']) ? (int) $validated['installation_id'] : null,
        ]);

        if ($response->denied()) {
            return back()
                ->withInput()
                ->with('error', $response->message() ?: 'Unauthorized role assignment request.');
        }

        try {
            $assignment = $this->roleAssignmentService->assign($request->user(), $validated);

            return redirect()
                ->route('admin.users-roles.index', ['search' => $validated['cpf']])
                ->with('status', "Role '{$role->name}' successfully assigned to user {$assignment->cpf}.");
        } catch (RoleAssignmentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove a role assignment.
     */
    public function destroy(Request $request, RoleAssignment $roleAssignment): RedirectResponse
    {
        Gate::authorize('viewAny', RoleAssignment::class);

        // Check policy authorization
        $response = Gate::inspect('delete', $roleAssignment);

        if ($response->denied()) {
            return back()->with('error', $response->message() ?: 'Unauthorized to remove this role assignment.');
        }

        try {
            $cpf = $roleAssignment->cpf;
            $roleName = $roleAssignment->role?->name ?? 'Role';

            $this->roleAssignmentService->remove($request->user(), $roleAssignment);

            return back()->with('status', "Role '{$roleName}' successfully removed from user {$cpf}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
