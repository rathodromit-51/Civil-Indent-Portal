<?php

namespace Tests\Feature;

use App\Enums\IndentPriority;
use App\Enums\IndentStatus;
use App\Enums\MovementAction;
use App\Models\Indent;
use App\Models\Installation;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrictWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $im;
    protected User $sam;
    protected User $sm;
    protected User $hes;
    protected User $lmw;
    protected User $ico;
    protected User $ce;
    protected Installation $installation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->user = User::where('cpf', 'USER001')->firstOrFail();
        $this->im = User::where('cpf', 'IM001')->firstOrFail();
        $this->sam = User::where('cpf', 'SAM001')->firstOrFail();
        $this->sm = User::where('cpf', 'SM001')->firstOrFail();
        $this->hes = User::where('cpf', 'HES001')->firstOrFail();
        $this->lmw = User::where('cpf', 'LMW001')->firstOrFail();
        $this->ico = User::where('cpf', 'ICO001')->firstOrFail();
        $this->ce = User::where('cpf', 'CE001')->firstOrFail();

        $this->installation = Installation::firstOrFail();

        // Ensure roles and assignments are explicitly aligned
        $this->ensureRole($this->im, 'IM', $this->installation->id, null);
        $this->ensureRole($this->sam, 'SAM', null, $this->installation->area_id);
        $this->ensureRole($this->sm, 'SM', null, $this->installation->area_id);
        $this->ensureRole($this->hes, 'HES', null, null);
        $this->ensureRole($this->lmw, 'LM Works', null, null);
        $this->ensureRole($this->ico, 'ICO', null, null);
        $this->ensureRole($this->ce, 'CE', $this->installation->id, null);
    }

    protected function ensureRole(User $user, string $roleName, ?int $installationId, ?int $areaId): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        RoleAssignment::updateOrCreate(
            ['cpf' => $user->cpf],
            [
                'role_id' => $role->id,
                'installation_id' => $installationId,
                'area_id' => $areaId,
                'assigned_by' => 'ADMIN001',
            ]
        );
    }

    /**
     * Helper to create and submit an initial indent by USER.
     */
    protected function createSubmittedIndent(): Indent
    {
        $response = $this->actingAs($this->user)->post(route('indents.store'), [
            'title' => 'Structural Overhaul of Compressor Bay Foundation',
            'work_type' => 'Civil Construction',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::HIGH->value,
            'required_by' => now()->addDays(20)->format('Y-m-d'),
            'description' => 'Reinforce cracked foundation base under Gas Compressor 2.',
            'justification' => 'Vibration risk to high pressure gas lines.',
            'action' => 'submit',
            'remark' => 'Submitting initial requisition for IM review.',
        ]);

        $response->assertRedirect();

        return Indent::where('title', 'Structural Overhaul of Compressor Bay Foundation')->firstOrFail();
    }

    /**
     * Test 1: Full 8-Stage Sequential Workflow:
     * USER -> IM -> SAM -> SM -> HES -> LMW -> ICO -> CE
     * CE Updates status: Accept (Assigned) -> Start Work (InProgress) -> Complete (Closed).
     */
    public function test_full_eight_stage_workflow_pipeline(): void
    {
        // Stage 1: USER creates & submits -> auto routes to IM
        $indent = $this->createSubmittedIndent();

        $this->assertEquals($this->im->cpf, $indent->current_holder_cpf);
        $this->assertEquals(IndentStatus::OPEN, $indent->status);
        $this->assertEquals('SAM', $indent->getNextExpectedRole());

        // Stage 2: IM forwards to SAM
        $resIM = $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'IM Pooja Nair verified site conditions. Forwarding to SAM.',
        ]);
        $resIM->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals($this->sam->cpf, $indent->current_holder_cpf);
        $this->assertEquals('SM', $indent->getNextExpectedRole());

        // Stage 3: SAM forwards to SM
        $resSAM = $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sm->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'SAM Anita Desai analyzed indent. Recommending technical routing.',
        ]);
        $resSAM->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals($this->sm->cpf, $indent->current_holder_cpf);
        $this->assertEquals('HES', $indent->getNextExpectedRole());

        // Stage 4: SM forwards to HES (peer technical head)
        $resSM = $this->actingAs($this->sm)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->hes->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'SM Vikram Sharma concurred. Routing to Head Engineering Services.',
        ]);
        $resSM->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals($this->hes->cpf, $indent->current_holder_cpf);
        $this->assertEquals('LMW', $indent->getNextExpectedRole());

        // Stage 5: HES forwards to LMW (Lead Manager of Works)
        $resHES = $this->actingAs($this->hes)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->lmw->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'HES Dr. Arindam Bose assigned to Civil Works department.',
        ]);
        $resHES->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals($this->lmw->cpf, $indent->current_holder_cpf);
        $this->assertEquals('ICO', $indent->getNextExpectedRole());

        // Stage 6: LMW forwards to ICO (Incharge Civil Ops)
        $resLMW = $this->actingAs($this->lmw)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->ico->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'LMW Manoj Kulkarni accepted. Forwarding to Surface ICO for execution.',
        ]);
        $resLMW->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals($this->ico->cpf, $indent->current_holder_cpf);
        $this->assertEquals('CE', $indent->getNextExpectedRole());

        // Stage 7: ICO forwards to CE (Civil Engineer)
        $resICO = $this->actingAs($this->ico)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->ce->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'ICO Meera Krishnan tasking field Civil Engineer Deepak Patel.',
        ]);
        $resICO->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals($this->ce->cpf, $indent->current_holder_cpf);
        $this->assertTrue($indent->isHeldByCe());

        // Stage 8: CE Milestone Updates
        // 8a. CE Accepts task -> status becomes Assigned
        $resAccept = $this->actingAs($this->ce)->post(route('indents.status.update', $indent), [
            'status' => IndentStatus::ASSIGNED->value,
            'remark' => 'CE Deepak Patel accepted site work assignment.',
        ]);
        $resAccept->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals(IndentStatus::ASSIGNED, $indent->status);
        $this->assertEquals(MovementAction::ACCEPT->value, $indent->latestMovement->action);

        // 8b. CE Starts physical work -> status becomes InProgress
        $resStart = $this->actingAs($this->ce)->post(route('indents.status.update', $indent), [
            'status' => IndentStatus::IN_PROGRESS->value,
            'remark' => 'Excavation and foundation scaffolding started on site.',
        ]);
        $resStart->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals(IndentStatus::IN_PROGRESS, $indent->status);
        $this->assertEquals(MovementAction::START_WORK->value, $indent->latestMovement->action);

        // 8c. CE Completes work -> status becomes Closed
        $resClose = $this->actingAs($this->ce)->post(route('indents.status.update', $indent), [
            'status' => IndentStatus::CLOSED->value,
            'remark' => 'Reinforced concrete poured, cured, and handed back to operations.',
        ]);
        $resClose->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals(IndentStatus::CLOSED, $indent->status);
        $this->assertEquals(MovementAction::CLOSE->value, $indent->latestMovement->action);
    }

    /**
     * Test 2: Out of order forward attempts are blocked by validation.
     */
    public function test_out_of_order_forward_is_prevented(): void
    {
        $indent = $this->createSubmittedIndent();
        $this->assertEquals($this->im->cpf, $indent->current_holder_cpf);

        // IM attempts to jump sequence directly to SM -> fails
        $badJump1 = $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sm->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Trying to skip SAM directly to SM',
        ]);
        $badJump1->assertSessionHasErrors('recipient_cpf');

        // IM attempts to jump directly to CE -> fails
        $badJump2 = $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->ce->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Trying to skip to CE',
        ]);
        $badJump2->assertSessionHasErrors('recipient_cpf');

        // Forward to proper role (SAM) succeeds
        $goodForward1 = $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Proper forward to SAM',
        ]);
        $goodForward1->assertSessionHasNoErrors();
        $indent->refresh();

        // SAM attempts to jump directly to HES -> fails
        $badJump3 = $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->hes->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Trying to skip SM directly to HES',
        ]);
        $badJump3->assertSessionHasErrors('recipient_cpf');
    }

    /**
     * Test 3: Rejection authority:
     * - Only IM, SAM, and SM have authority to reject.
     * - Non-management (HES, LMW, ICO, CE, USER) cannot reject.
     * - Rejection requires mandatory reason.
     * - Rejection flags the file and routes back to creator.
     */
    public function test_rejection_authority_and_creator_display(): void
    {
        $indent = $this->createSubmittedIndent();
        $this->assertEquals($this->im->cpf, $indent->current_holder_cpf);

        // IM rejects back to creator
        $rejectResponse = $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->user->cpf,
            'action' => MovementAction::FORWARD->value,
            'is_rejected' => '1',
            'remark' => 'Scope too broad. Break down into Phase 1 foundation and Phase 2 piping.',
        ]);
        $rejectResponse->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertTrue($indent->is_rejected);
        $this->assertEquals(IndentStatus::OPEN, $indent->status); // Remains open!
        $this->assertEquals($this->user->cpf, $indent->current_holder_cpf);

        // Creator views the indent show page -> rejection banner is displayed
        $creatorView = $this->actingAs($this->user)->get(route('indents.show', $indent));
        $creatorView->assertOk();
        $creatorView->assertSee('Indent Flagged');
        $creatorView->assertSee('Rejected by');
        $creatorView->assertSee('Mandatory Rejection Reason:');
        $creatorView->assertSee('Scope too broad');
        $creatorView->assertSee($this->im->name);
        $creatorView->assertSee('IM');

        // Creator dashboard / index flagged tab
        $inboxView = $this->actingAs($this->user)->get(route('indents.index', ['tab' => 'flagged']));
        $inboxView->assertOk();
        $inboxView->assertSee($indent->number);
        $inboxView->assertSee('Flagged');
    }

    /**
     * Test 4: Non-management roles (e.g. HES, LMW, ICO, CE) cannot reject.
     */
    public function test_non_management_roles_cannot_reject(): void
    {
        $indent = $this->createSubmittedIndent();

        // Move to HES
        $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);
        $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sm->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);
        $this->actingAs($this->sm)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->hes->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);

        $indent->refresh();
        $this->assertEquals($this->hes->cpf, $indent->current_holder_cpf);

        // HES attempts to reject -> denied by validation/policy
        $hesReject = $this->actingAs($this->hes)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->user->cpf,
            'action' => MovementAction::FORWARD->value,
            'is_rejected' => '1',
            'remark' => 'Technical head trying to reject.',
        ]);

        $hesReject->assertSessionHasErrors('action');
        $this->assertFalse($indent->fresh()->is_rejected);
    }

    /**
     * Test 5: Only CE can update execution status.
     */
    public function test_execution_status_updates_exclusive_to_ce(): void
    {
        $indent = $this->createSubmittedIndent();

        // Indent currently held by IM -> IM cannot update execution status
        $imAttempt = $this->actingAs($this->im)->post(route('indents.status.update', $indent), [
            'status' => IndentStatus::IN_PROGRESS->value,
            'remark' => 'Unauthorized update by IM',
        ]);
        $imAttempt->assertForbidden();

        // Move indent all the way to CE
        $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);
        $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sm->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);
        $this->actingAs($this->sm)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->hes->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);
        $this->actingAs($this->hes)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->lmw->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);
        $this->actingAs($this->lmw)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->ico->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);
        $this->actingAs($this->ico)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->ce->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);

        $indent->refresh();
        $this->assertEquals($this->ce->cpf, $indent->current_holder_cpf);

        // CE views the show page -> Site Execution Panel renders, Forwarding Card is hidden
        $ceView = $this->actingAs($this->ce)->get(route('indents.show', $indent));
        $ceView->assertOk();
        $ceView->assertSee('Site Execution Panel (Civil Engineer)');
        $ceView->assertSee('Current State: Open');
        $ceView->assertDontSee('Workflow Actions: Forward or Return');

        // CE is terminal execution role -> cannot forward
        $ceForwardAttempt = $this->actingAs($this->ce)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->ico->cpf,
            'action' => MovementAction::FORWARD->value,
        ]);
        $ceForwardAttempt->assertForbidden();

        // Another user (e.g. ICO) attempts to update status on CE's indent -> 403 Forbidden
        $icoAttempt = $this->actingAs($this->ico)->post(route('indents.status.update', $indent), [
            'status' => IndentStatus::IN_PROGRESS->value,
            'remark' => 'Unauthorized update by ICO',
        ]);
        $icoAttempt->assertForbidden();

        // CE successfully updates status
        $ceSuccess = $this->actingAs($this->ce)->post(route('indents.status.update', $indent), [
            'status' => IndentStatus::ASSIGNED->value,
            'remark' => 'Accepted work.',
        ]);
        $ceSuccess->assertRedirect(route('indents.show', $indent));
        $this->assertEquals(IndentStatus::ASSIGNED, $indent->fresh()->status);
    }
}
