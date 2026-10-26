<?php

namespace Tests\Feature;

use App\Exceptions\RoleAssignmentException;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Installation;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sm;
    protected User $sam;
    protected User $normalUser;
    protected Area $areaVad;
    protected Area $areaSur;
    protected Installation $instVad1;
    protected Installation $instSur1;
    protected RoleAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->service = app(RoleAssignmentService::class);

        $this->admin = User::where('cpf', 'ADMIN001')->firstOrFail();
        $this->sm = User::where('cpf', 'SM001')->firstOrFail();
        $this->sam = User::where('cpf', 'SAM001')->firstOrFail();
        $this->normalUser = User::where('cpf', 'USER001')->firstOrFail();

        $this->areaVad = Area::where('code', 'VAD')->firstOrFail();
        $this->areaSur = Area::where('code', 'SUR')->firstOrFail();
        $this->instVad1 = Installation::where('code', 'VAD-01')->firstOrFail();
        $this->instSur1 = Installation::where('code', 'SUR-01')->firstOrFail();
    }

    /**
     * Rule 1: Super Admin can assign any role and it creates an audit log.
     */
    public function test_super_admin_can_assign_any_role_and_creates_audit_log(): void
    {
        $samRole = Role::where('name', 'SAM')->firstOrFail();
        $response = Gate::forUser($this->admin)->inspect('assign', [
            RoleAssignment::class,
            $samRole,
            $this->areaSur->id,
            null,
        ]);
        $this->assertTrue($response->allowed());

        $newUser = User::factory()->create(['cpf' => 'NEWADMIN01', 'name' => 'Admin Assignee']);

        // Remove existing SAM in Surat to test assignment cleanly
        RoleAssignment::where('role_id', $samRole->id)->where('area_id', $this->areaSur->id)->delete();

        $assignment = $this->service->assign($this->admin, [
            'cpf' => $newUser->cpf,
            'role_id' => $samRole->id,
            'area_id' => $this->areaSur->id,
        ]);

        $this->assertNotNull($assignment);
        $this->assertDatabaseHas('audit_logs', [
            'who' => 'ADMIN001',
            'what' => 'assign',
        ]);

        $log = AuditLog::where('who', 'ADMIN001')->where('what', 'assign')->latest()->first();
        $this->assertNotNull($log->when);
        $this->assertEquals('NEWADMIN01', $log->details['target_cpf']);
        $this->assertEquals('SAM', $log->details['role_name']);
        $this->assertEquals($this->areaSur->id, $log->details['area_id']);
    }

    /**
     * Rule 2: SM can assign SAM and IM within own area, creating an audit log.
     */
    public function test_sm_can_assign_im_in_own_area_and_creates_audit_log(): void
    {
        $imRole = Role::where('name', 'IM')->firstOrFail();
        $candidate = User::factory()->create(['cpf' => 'CAND001', 'name' => 'Candidate One']);

        // Delete any existing IM on Vadodara 01 to avoid single-holder conflict
        RoleAssignment::where('role_id', $imRole->id)->where('installation_id', $this->instVad1->id)->delete();

        $response = $this->actingAs($this->sm)->post(route('admin.users-roles.store'), [
            'cpf' => $candidate->cpf,
            'role_id' => $imRole->id,
            'installation_id' => $this->instVad1->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('role_assignments', [
            'cpf' => 'CAND001',
            'role_id' => $imRole->id,
            'installation_id' => $this->instVad1->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'who' => 'SM001',
            'what' => 'assign',
        ]);

        $log = AuditLog::where('who', 'SM001')->where('what', 'assign')->latest()->first();
        $this->assertEquals('CAND001', $log->details['target_cpf']);
        $this->assertEquals('IM', $log->details['role_name']);
        $this->assertEquals($this->instVad1->id, $log->details['installation_id']);
    }

    /**
     * Rule 3: SM cannot assign SAM or IM outside own area, and no audit log is created.
     */
    public function test_sm_cannot_assign_outside_own_area(): void
    {
        $imRole = Role::where('name', 'IM')->firstOrFail();
        $candidate = User::factory()->create(['cpf' => 'OUT001', 'name' => 'Outside Candidate']);

        $initialLogsCount = AuditLog::count();

        // SM001 (Vadodara) tries to assign IM to Surat installation (SUR-01)
        $response = $this->actingAs($this->sm)->post(route('admin.users-roles.store'), [
            'cpf' => $candidate->cpf,
            'role_id' => $imRole->id,
            'installation_id' => $this->instSur1->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('own designated area', session('error'));

        $this->assertDatabaseMissing('role_assignments', ['cpf' => 'OUT001']);
        $this->assertEquals($initialLogsCount, AuditLog::count());
    }

    /**
     * Rule 4: SM cannot assign unauthorized roles (e.g. HES or Super Admin).
     */
    public function test_sm_cannot_assign_unauthorized_role(): void
    {
        $hesRole = Role::where('name', 'HES')->firstOrFail();
        $candidate = User::factory()->create(['cpf' => 'UNAUTH01', 'name' => 'Unauth Candidate']);

        $initialLogsCount = AuditLog::count();

        $response = $this->actingAs($this->sm)->post(route('admin.users-roles.store'), [
            'cpf' => $candidate->cpf,
            'role_id' => $hesRole->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('only assign SAM and IM roles', session('error'));

        $this->assertDatabaseMissing('role_assignments', ['cpf' => 'UNAUTH01']);
        $this->assertEquals($initialLogsCount, AuditLog::count());
    }

    /**
     * Rule 5: SAM can assign IM only in own area.
     */
    public function test_sam_can_assign_im_only_in_own_area(): void
    {
        $imRole = Role::where('name', 'IM')->firstOrFail();
        $samRole = Role::where('name', 'SAM')->firstOrFail();

        // 1. SAM assigning IM in own area installation -> ALLOWED
        $resImOwn = Gate::forUser($this->sam)->inspect('assign', [
            RoleAssignment::class,
            $imRole,
            null,
            $this->instVad1->id,
        ]);
        $this->assertTrue($resImOwn->allowed());

        // 2. SAM assigning IM in other area installation -> DENIED
        $resImOther = Gate::forUser($this->sam)->inspect('assign', [
            RoleAssignment::class,
            $imRole,
            null,
            $this->instSur1->id,
        ]);
        $this->assertTrue($resImOther->denied());

        // 3. SAM assigning SAM -> DENIED
        $resSam = Gate::forUser($this->sam)->inspect('assign', [
            RoleAssignment::class,
            $samRole,
            $this->areaVad->id,
            null,
        ]);
        $this->assertTrue($resSam->denied());
    }

    /**
     * Rule 6: Normal user attempts to view screen or assign roles are rejected with 403.
     */
    public function test_normal_user_attempts_are_rejected_with_403(): void
    {
        $imRole = Role::where('name', 'IM')->firstOrFail();
        $candidate = User::factory()->create(['cpf' => 'CANDUSER01']);

        // 1. Screen view attempt -> 403 Forbidden
        $resView = $this->actingAs($this->normalUser)->get(route('admin.users-roles.index'));
        $resView->assertForbidden();

        // 2. Role assignment attempt -> 403 Forbidden
        $resStore = $this->actingAs($this->normalUser)->post(route('admin.users-roles.store'), [
            'cpf' => $candidate->cpf,
            'role_id' => $imRole->id,
            'installation_id' => $this->instVad1->id,
        ]);
        $resStore->assertForbidden();

        $this->assertDatabaseMissing('role_assignments', ['cpf' => 'CANDUSER01']);
        $this->assertDatabaseMissing('audit_logs', ['who' => $this->normalUser->cpf]);
    }

    /**
     * Rule 7: One role per CPF constraint is strictly enforced.
     */
    public function test_assigning_to_cpf_that_already_has_role_is_rejected(): void
    {
        $this->assertNotNull($this->normalUser->roleAssignment);
        $imRole = Role::where('name', 'IM')->firstOrFail();

        $this->expectException(RoleAssignmentException::class);
        $this->expectExceptionMessageMatches('/already holds the role/i');

        $this->service->assign($this->admin, [
            'cpf' => $this->normalUser->cpf,
            'role_id' => $imRole->id,
            'installation_id' => $this->instVad1->id,
        ]);
    }

    /**
     * Rule 8: Single-holder per (role, scope) constraint is enforced.
     */
    public function test_single_holder_role_scope_constraint_enforced(): void
    {
        $samRole = Role::where('name', 'SAM')->firstOrFail();
        $this->assertFalse((bool) $samRole->multi_holder);

        $newUser = User::factory()->create(['cpf' => 'NEW001']);

        $this->expectException(RoleAssignmentException::class);
        $this->expectExceptionMessageMatches('/Only one holder is permitted/i');

        // SAM001 already holds SAM in Vadodara area
        $this->service->assign($this->admin, [
            'cpf' => $newUser->cpf,
            'role_id' => $samRole->id,
            'area_id' => $this->areaVad->id,
        ]);
    }

    /**
     * Rule 9: Multi-holder roles allow multiple holders.
     */
    public function test_multi_holder_roles_allow_multiple_assignments(): void
    {
        $ceRole = Role::where('name', 'CE')->firstOrFail();
        $this->assertTrue((bool) $ceRole->multi_holder);

        $userA = User::factory()->create(['cpf' => 'CEA001']);
        $userB = User::factory()->create(['cpf' => 'CEB001']);

        $assignA = $this->service->assign($this->admin, [
            'cpf' => $userA->cpf,
            'role_id' => $ceRole->id,
            'installation_id' => $this->instVad1->id,
        ]);

        $assignB = $this->service->assign($this->admin, [
            'cpf' => $userB->cpf,
            'role_id' => $ceRole->id,
            'installation_id' => $this->instVad1->id,
        ]);

        $this->assertDatabaseHas('role_assignments', ['id' => $assignA->id, 'cpf' => 'CEA001']);
        $this->assertDatabaseHas('role_assignments', ['id' => $assignB->id, 'cpf' => 'CEB001']);
    }

    /**
     * Rule 10: Role removal logs an audit entry and resets user designation.
     */
    public function test_role_removal_logs_audit_record_and_resets_designation(): void
    {
        $user = User::factory()->create(['cpf' => 'REMOVETEST01']);
        $ceRole = Role::where('name', 'CE')->firstOrFail();

        $assignment = $this->service->assign($this->admin, [
            'cpf' => $user->cpf,
            'role_id' => $ceRole->id,
            'installation_id' => $this->instVad1->id,
        ]);

        $this->assertEquals('CE', $user->fresh()->designation);

        // Remove the role
        $this->service->remove($this->admin, $assignment);

        $this->assertDatabaseMissing('role_assignments', ['id' => $assignment->id]);
        $this->assertEquals('Normal User', $user->fresh()->designation);

        // Verify audit log for remove
        $this->assertDatabaseHas('audit_logs', [
            'who' => 'ADMIN001',
            'what' => 'remove',
        ]);

        $removeLog = AuditLog::where('who', 'ADMIN001')->where('what', 'remove')->latest()->first();
        $this->assertEquals('REMOVETEST01', $removeLog->details['target_cpf']);
        $this->assertEquals('CE', $removeLog->details['role_name']);
    }

    /**
     * Rule 11: Scope consistency validation.
     */
    public function test_role_scope_consistency_is_strictly_validated(): void
    {
        $samRole = Role::where('name', 'SAM')->firstOrFail();
        $candidate = User::factory()->create(['cpf' => 'NOSCOPE01']);

        // Area role without area should fail
        $this->expectException(RoleAssignmentException::class);
        $this->expectExceptionMessageMatches('/requires an Area scope/i');

        $this->service->assign($this->admin, [
            'cpf' => $candidate->cpf,
            'role_id' => $samRole->id,
            'area_id' => null,
        ]);
    }

    /**
     * Rule 12 / Checkpoint: SM assigns IM, a normal user's attempt is rejected, audit rows visible.
     */
    public function test_checkpoint_sm_assigns_im_normal_user_rejected_audit_rows_visible(): void
    {
        $imRole = Role::where('name', 'IM')->firstOrFail();
        $candidate = User::factory()->create(['cpf' => 'CHECKPOINT01', 'name' => 'Harish Patel']);

        // Clear existing assignment on installation VAD-01 to ensure clear single-holder slot
        RoleAssignment::where('role_id', $imRole->id)->where('installation_id', $this->instVad1->id)->delete();

        // 1. SM assigns IM at Vadodara 01
        $assignResponse = $this->actingAs($this->sm)->post(route('admin.users-roles.store'), [
            'cpf' => $candidate->cpf,
            'role_id' => $imRole->id,
            'installation_id' => $this->instVad1->id,
        ]);

        $assignResponse->assertRedirect();
        $assignResponse->assertSessionHas('status');

        $this->assertDatabaseHas('role_assignments', [
            'cpf' => 'CHECKPOINT01',
            'role_id' => $imRole->id,
            'installation_id' => $this->instVad1->id,
        ]);

        // 2. Audit row is created in database
        $this->assertDatabaseHas('audit_logs', [
            'who' => 'SM001',
            'what' => 'assign',
        ]);

        // 3. Normal user's attempt is rejected with 403
        $normalAttempt = $this->actingAs($this->normalUser)->post(route('admin.users-roles.store'), [
            'cpf' => 'SOMEOTHER01',
            'role_id' => $imRole->id,
            'installation_id' => $this->instVad1->id,
        ]);
        $normalAttempt->assertForbidden();

        // 4. Audit rows are visible on the screen
        $screenResponse = $this->actingAs($this->sm)->get(route('admin.users-roles.index'));
        $screenResponse->assertOk();
        $screenResponse->assertSee('Audit Logs');
        $screenResponse->assertSee('SM001');
        $screenResponse->assertSee('ASSIGN');
        $screenResponse->assertSee('CHECKPOINT01');
        $screenResponse->assertSee('Harish Patel');
        $screenResponse->assertSee('IM');
        $screenResponse->assertSee($this->instVad1->name);
    }

    /**
     * Test Web Route: Screen search user by CPF or name.
     */
    public function test_screen_search_user_by_cpf_or_name(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users-roles.index', [
            'search' => 'SAM001',
        ]));

        $response->assertOk();
        $response->assertSee('Anita Desai');
        $response->assertSee('SAM001');
    }

    /**
     * Test Web Route: Store endpoint rejects already assigned CPF.
     */
    public function test_store_route_rejects_already_assigned_cpf(): void
    {
        $imRole = Role::where('name', 'IM')->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('admin.users-roles.store'), [
            'cpf' => $this->normalUser->cpf,
            'role_id' => $imRole->id,
            'installation_id' => $this->instVad1->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('already holds the role', session('error'));
    }

    /**
     * Test Web Route: Remove role endpoint.
     */
    public function test_destroy_route_removes_role(): void
    {
        $assignment = $this->normalUser->roleAssignment;
        $this->assertNotNull($assignment);

        $response = $this->actingAs($this->admin)->delete(route('admin.users-roles.destroy', $assignment));

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('role_assignments', ['id' => $assignment->id]);
        $this->assertDatabaseHas('audit_logs', [
            'who' => 'ADMIN001',
            'what' => 'remove',
        ]);
    }

    /**
     * Test Web Route: Super Admin cannot remove own role (self-lockout prevention).
     */
    public function test_super_admin_cannot_remove_own_role(): void
    {
        $adminAssignment = $this->admin->roleAssignment;
        $this->assertNotNull($adminAssignment);

        // 1. Direct DELETE request is denied
        $response = $this->actingAs($this->admin)->delete(route('admin.users-roles.destroy', $adminAssignment));
        $response->assertSessionHas('error', 'You cannot remove your own role assignment.');
        $this->assertDatabaseHas('role_assignments', ['id' => $adminAssignment->id]);

        // 2. UI renders 'Current User' indicator and no remove button on own row
        $screenResponse = $this->actingAs($this->admin)->get(route('admin.users-roles.index'));
        $screenResponse->assertOk();
        $screenResponse->assertSee('🔒 Current User');
    }

    /**
     * Test Pagination renders cleanly with custom template.
     */
    public function test_pagination_renders_cleanly_with_custom_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users-roles.index'));
        $response->assertOk();
        $response->assertSee('Showing');
        $response->assertSee('users');
        $response->assertSee('Next &rsaquo;', false);
    }

    /**
     * Test Audit Logs pagination displays exactly 7 logs per page.
     */
    public function test_audit_logs_pagination_displays_seven_logs_per_page(): void
    {
        // Seed 15 audit logs
        for ($i = 1; $i <= 15; $i++) {
            AuditLog::create([
                'who' => 'ADMIN001',
                'what' => 'assign',
                'when' => now()->subMinutes(20 - $i),
                'details' => [
                    'target_cpf' => "LOGCPF{$i}",
                    'target_name' => "User {$i}",
                    'role_name' => 'IM',
                ],
            ]);
        }

        // Request page 1
        $responsePage1 = $this->actingAs($this->admin)->get(route('admin.users-roles.index'));
        $responsePage1->assertOk();
        $responsePage1->assertViewHas('auditLogs', function ($logs) {
            return $logs->perPage() === 7 && $logs->count() === 7 && $logs->total() >= 15;
        });
        $responsePage1->assertSee('Total Events (7 per page)');
        $responsePage1->assertSee('logs');

        // Request page 2
        $responsePage2 = $this->actingAs($this->admin)->get(route('admin.users-roles.index', ['logs_page' => 2]));
        $responsePage2->assertOk();
        $responsePage2->assertViewHas('auditLogs', function ($logs) {
            return $logs->currentPage() === 2 && $logs->count() === 7;
        });
    }
}
