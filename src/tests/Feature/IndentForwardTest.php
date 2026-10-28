<?php

namespace Tests\Feature;

use App\Enums\IndentPriority;
use App\Enums\IndentStatus;
use App\Enums\MovementAction;
use App\Models\Indent;
use App\Models\IndentParticipant;
use App\Models\Installation;
use App\Models\Movement;
use App\Models\RoleAssignment;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndentForwardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $im;
    protected User $sam;
    protected User $sm;
    protected User $nonHolder;
    protected Installation $installation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(RoleSeeder::class);

        // Seeded actors
        $this->user = User::where('cpf', 'USER001')->firstOrFail();
        $this->im = User::where('cpf', 'IM001')->firstOrFail();
        $this->sam = User::where('cpf', 'SAM001')->firstOrFail();
        $this->sm = User::where('cpf', 'SM001')->firstOrFail();
        $this->nonHolder = User::where('cpf', 'USER002')->firstOrFail();

        $this->installation = Installation::firstOrFail();

        // Ensure IM001 is assigned to this installation
        RoleAssignment::updateOrCreate(
            ['cpf' => $this->im->cpf],
            [
                'role_id' => \App\Models\Role::where('name', 'IM')->firstOrFail()->id,
                'installation_id' => $this->installation->id,
                'area_id' => null,
                'assigned_by' => 'ADMIN001',
            ]
        );

        // Ensure SAM001 has SAM role
        RoleAssignment::updateOrCreate(
            ['cpf' => $this->sam->cpf],
            [
                'role_id' => \App\Models\Role::where('name', 'SAM')->firstOrFail()->id,
                'installation_id' => null,
                'area_id' => $this->installation->area_id,
                'assigned_by' => 'ADMIN001',
            ]
        );

        // Ensure SM001 has SM role
        RoleAssignment::updateOrCreate(
            ['cpf' => $this->sm->cpf],
            [
                'role_id' => \App\Models\Role::where('name', 'SM')->firstOrFail()->id,
                'installation_id' => null,
                'area_id' => $this->installation->area_id,
                'assigned_by' => 'ADMIN001',
            ]
        );
    }

    /**
     * Helper to create a submitted indent currently held by IM001.
     */
    protected function createSubmittedIndent(): Indent
    {
        $response = $this->actingAs($this->user)->post(route('indents.store'), [
            'title' => 'Structural Repair of GGS-1 Pipeline Support',
            'work_type' => 'Civil Maintenance',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::HIGH->value,
            'required_by' => now()->addDays(14)->format('Y-m-d'),
            'description' => 'Replace corroded structural steel columns.',
            'justification' => 'Pipeline stability hazard.',
            'action' => 'submit',
            'remark' => 'Submitting requisition for IM review.',
        ]);

        $response->assertRedirect();

        return Indent::where('title', 'Structural Repair of GGS-1 Pipeline Support')->firstOrFail();
    }

    /**
     * Test 1: Complete UI movement workflow: User -> IM -> SAM -> SM
     */
    public function test_indent_moves_user_to_im_to_sam_to_sm(): void
    {
        // 1. User submits indent -> held by IM
        $indent = $this->createSubmittedIndent();

        $this->assertEquals($this->im->cpf, $indent->fresh()->current_holder_cpf);
        $this->assertFalse($indent->fresh()->is_draft);
        $this->assertEquals(IndentStatus::OPEN, $indent->fresh()->status);
        $this->assertTrue(
            $indent->participants()->where('cpf', $this->user->cpf)->exists()
        );
        $this->assertTrue(
            $indent->participants()->where('cpf', $this->im->cpf)->exists()
        );

        // 2. IM forwards to SAM
        $response1 = $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Forwarded from IM to SAM Anita Desai for area engineering review.',
        ]);

        $response1->assertRedirect(route('indents.show', $indent));
        $response1->assertSessionHas('status');

        $indent->refresh();
        $this->assertEquals($this->sam->cpf, $indent->current_holder_cpf);
        $this->assertTrue(
            $indent->participants()->where('cpf', $this->sam->cpf)->exists()
        );

        // Verify movement logged in DB
        $latestMvt1 = $indent->latestMovement;
        $this->assertEquals($this->im->cpf, $latestMvt1->from);
        $this->assertEquals($this->sam->cpf, $latestMvt1->to);
        $this->assertEquals(MovementAction::FORWARD->value, $latestMvt1->action);
        $this->assertFalse($latestMvt1->is_rejected);
        $this->assertStringContainsString('Forwarded from IM to SAM', $latestMvt1->remark);

        // 3. SAM forwards to SM
        $response2 = $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sm->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Forwarded from SAM to Surface Manager Vikram Sharma for administrative concurrence.',
        ]);

        $response2->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals($this->sm->cpf, $indent->current_holder_cpf);
        $this->assertTrue(
            $indent->participants()->where('cpf', $this->sm->cpf)->exists()
        );

        // Verify latest movement logged to SM
        $latestMvt2 = $indent->latestMovement;
        $this->assertEquals($this->sam->cpf, $latestMvt2->from);
        $this->assertEquals($this->sm->cpf, $latestMvt2->to);
        $this->assertEquals(MovementAction::FORWARD->value, $latestMvt2->action);

        // Verify complete sequence of 3 movements: Submit -> Forward -> Forward
        $movements = $indent->movements()->orderBy('id')->get();
        $this->assertCount(3, $movements);
        $this->assertEquals(MovementAction::SUBMIT->value, $movements[0]->action);
        $this->assertEquals($this->user->cpf, $movements[0]->from);
        $this->assertEquals($this->im->cpf, $movements[0]->to);

        $this->assertEquals(MovementAction::FORWARD->value, $movements[1]->action);
        $this->assertEquals($this->im->cpf, $movements[1]->from);
        $this->assertEquals($this->sam->cpf, $movements[1]->to);

        $this->assertEquals(MovementAction::FORWARD->value, $movements[2]->action);
        $this->assertEquals($this->sam->cpf, $movements[2]->from);
        $this->assertEquals($this->sm->cpf, $movements[2]->to);

        // Verify all 4 participants recorded in indent_participants
        $participants = $indent->participants()->pluck('cpf')->all();
        $this->assertContains($this->user->cpf, $participants);
        $this->assertContains($this->im->cpf, $participants);
        $this->assertContains($this->sam->cpf, $participants);
        $this->assertContains($this->sm->cpf, $participants);
    }

    /**
     * Test 2: Only the current holder can forward (Policy enforcement).
     * Non-holder cannot forward (test).
     */
    public function test_non_holder_cannot_forward(): void
    {
        $indent = $this->createSubmittedIndent();

        // Indent is currently held by IM001.
        // User (creator) is NOT the holder -> cannot forward
        $response1 = $this->actingAs($this->user)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Unauthorized forward attempt by creator.',
        ]);
        $response1->assertForbidden();

        // Random other user -> cannot forward
        $response2 = $this->actingAs($this->nonHolder)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Unauthorized forward attempt by third party.',
        ]);
        $response2->assertForbidden();

        // SAM -> cannot forward before receiving it
        $response3 = $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sm->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Premature forward attempt.',
        ]);
        $response3->assertForbidden();

        // Ensure indent holder and movements remain untouched
        $this->assertEquals($this->im->cpf, $indent->fresh()->current_holder_cpf);
        $this->assertEquals(1, $indent->movements()->count());
    }

    /**
     * Test 3: Forward with rejection:
     * - Reason mandatory (validation fails if remark is omitted).
     * - File flagged (is_rejected = true on movement and indent).
     * - File NOT closed (status remains Open).
     */
    public function test_forward_with_rejection_requires_reason_and_flags_file_without_closing(): void
    {
        $indent = $this->createSubmittedIndent();

        // Attempt forward with rejection WITHOUT remark -> validation error
        $responseFail = $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'is_rejected' => 1,
            'remark' => '',
        ]);

        $responseFail->assertSessionHasErrors('remark');
        $this->assertEquals($this->im->cpf, $indent->fresh()->current_holder_cpf);
        $this->assertFalse($indent->fresh()->is_rejected);

        // Forward with rejection WITH mandatory remark
        $responseSuccess = $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'is_rejected' => 1,
            'remark' => 'Structural calculation documentation is missing from technical attachment.',
        ]);

        $responseSuccess->assertRedirect(route('indents.show', $indent));
        $responseSuccess->assertSessionHas('status');

        $indent->refresh();

        // File is FLAGGED
        $this->assertTrue($indent->is_rejected);
        $this->assertTrue($indent->isRejected());

        // File is NOT CLOSED
        $this->assertNotEquals(IndentStatus::CLOSED, $indent->status);
        $this->assertEquals(IndentStatus::OPEN, $indent->status);

        // Current holder moved to SAM
        $this->assertEquals($this->sam->cpf, $indent->current_holder_cpf);

        // Movement has is_rejected = true
        $latestMvt = $indent->latestMovement;
        $this->assertTrue($latestMvt->is_rejected);
        $this->assertEquals('Structural calculation documentation is missing from technical attachment.', $latestMvt->remark);

        // Show page displays flagged rejection indicator
        $viewResponse = $this->actingAs($this->sam)->get(route('indents.show', $indent));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Flagged');
        $viewResponse->assertSee('REJECTED');
    }

    /**
     * Test 4: Return to any earlier participant.
     */
    public function test_return_to_earlier_participant(): void
    {
        $indent = $this->createSubmittedIndent();

        // IM forwards to SAM
        $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Forwarding to SAM',
        ]);

        $this->assertEquals($this->sam->cpf, $indent->fresh()->current_holder_cpf);

        // SAM returns indent back to USER001 (the initiator)
        $returnResponse = $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->user->cpf,
            'action' => MovementAction::RETURN ->value,
            'remark' => 'Returning to initiator for additional justification.',
        ]);

        $returnResponse->assertRedirect(route('indents.show', $indent));

        $indent->refresh();
        $this->assertEquals($this->user->cpf, $indent->current_holder_cpf);

        $latestMvt = $indent->latestMovement;
        $this->assertEquals(MovementAction::RETURN ->value, $latestMvt->action);
        $this->assertEquals($this->sam->cpf, $latestMvt->from);
        $this->assertEquals($this->user->cpf, $latestMvt->to);

        // Attempt to return to non-participant -> fails validation
        $invalidReturn = $this->actingAs($this->user)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->nonHolder->cpf, // nonHolder never participated
            'action' => MovementAction::RETURN ->value,
            'remark' => 'Invalid return',
        ]);

        $invalidReturn->assertSessionHasErrors('recipient_cpf');
    }

    /**
     * Test 5: Recipient picker AJAX search endpoint returns role and scope.
     */
    public function test_recipient_picker_search_endpoint(): void
    {
        // Search by name
        $response = $this->actingAs($this->im)->getJson(route('users.search', ['q' => 'Anita']));
        $response->assertOk();
        $response->assertJsonFragment([
            'cpf' => 'SAM001',
            'name' => 'Anita Desai',
            'role' => 'SAM',
        ]);

        // Search by CPF
        $responseCpf = $this->actingAs($this->im)->getJson(route('users.search', ['q' => 'SM001']));
        $responseCpf->assertOk();
        $responseCpf->assertJsonFragment([
            'cpf' => 'SM001',
            'role' => 'SM',
        ]);

        // Excludes the logged-in searcher by default
        $responseSelf = $this->actingAs($this->im)->getJson(route('users.search', ['q' => 'IM001']));
        $responseSelf->assertOk();
        $responseSelf->assertJsonMissing([
            'cpf' => 'IM001',
        ]);
    }

    /**
     * Test 6: Inbox page "Work Queue" shows indents currently held by logged in user.
     */
    public function test_inbox_with_me_page(): void
    {
        $indent = $this->createSubmittedIndent();

        // IM holds this indent -> visible on /inbox
        $imInbox = $this->actingAs($this->im)->get(route('indents.inbox'));
        $imInbox->assertOk();
        $imInbox->assertSee($indent->number);
        $imInbox->assertSee('Work Queue');

        // User does not hold it -> not in Work Queue tab
        $userInbox = $this->actingAs($this->user)->get(route('indents.inbox'));
        $userInbox->assertOk();
        $userInbox->assertDontSee($indent->title);
        $userInbox->assertSee('No indents found');

        // IM forwards to SAM -> now in SAM's inbox
        $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'To SAM',
        ]);

        $samInbox = $this->actingAs($this->sam)->get(route('indents.inbox'));
        $samInbox->assertOk();
        $samInbox->assertSee($indent->title);

        // No longer in IM's inbox
        $imInboxAfter = $this->actingAs($this->im)->get(route('indents.inbox'));
        $imInboxAfter->assertOk();
        $imInboxAfter->assertDontSee($indent->title);
        $imInboxAfter->assertSee('No indents found');
    }
}
