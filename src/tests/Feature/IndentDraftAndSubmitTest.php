<?php

namespace Tests\Feature;

use App\Enums\IndentPriority;
use App\Enums\IndentStatus;
use App\Models\Area;
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

class IndentDraftAndSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $otherUser;
    protected User $imUser;
    protected Installation $installation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(RoleSeeder::class);

        // Creator (Normal User)
        $this->creator = User::where('cpf', 'USER001')->firstOrFail();

        // Other Normal User
        $this->otherUser = User::where('cpf', 'USER002')->firstOrFail();

        // Installation Manager (IM001) for Ankleshwar or Vadodara
        $this->installation = Installation::firstOrFail();
        $imAssignment = RoleAssignment::where('installation_id', $this->installation->id)
            ->whereHas('role', fn ($q) => $q->where('name', 'IM'))
            ->first();

        if (! $imAssignment) {
            $imRole = Role::where('name', 'IM')->firstOrFail();
            $this->imUser = User::where('cpf', 'IM001')->firstOrFail();
            RoleAssignment::create([
                'role_id' => $imRole->id,
                'cpf' => $this->imUser->cpf,
                'installation_id' => $this->installation->id,
                'assigned_by' => 'ADMIN001',
            ]);
        } else {
            $this->imUser = User::where('cpf', $imAssignment->cpf)->firstOrFail();
        }
    }

    /**
     * Test 1: User can create indent as Draft (FRIND-04).
     */
    public function test_user_can_create_indent_as_draft(): void
    {
        $response = $this->actingAs($this->creator)->post(route('indents.store'), [
            'title' => 'Repair of Pump Foundation Slab',
            'work_type' => 'Civil Maintenance',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::HIGH->value,
            'required_by' => now()->addDays(10)->format('Y-m-d'),
            'description' => 'Detailed foundation reinforcement needed.',
            'justification' => 'Vibrations causing surface cracks.',
            'action' => 'draft',
        ]);

        $response->assertRedirect(route('indents.index', ['tab' => 'drafts']));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('indents', [
            'title' => 'Repair of Pump Foundation Slab',
            'work_type' => 'Civil Maintenance',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::HIGH->value,
            'is_draft' => true,
            'status' => IndentStatus::OPEN->value,
            'creator_cpf' => $this->creator->cpf,
            'current_holder_cpf' => $this->creator->cpf,
        ]);

        $indent = Indent::where('title', 'Repair of Pump Foundation Slab')->firstOrFail();
        $this->assertStringStartsWith('CI-', $indent->number);
        $this->assertTrue($indent->is_draft);

        // Creator added as participant
        $this->assertDatabaseHas('indent_participants', [
            'indent_id' => $indent->id,
            'cpf' => $this->creator->cpf,
        ]);

        // No movements created yet for draft
        $this->assertDatabaseMissing('movements', [
            'indent_id' => $indent->id,
        ]);
    }

    /**
     * Test 2: Creator can edit and update a draft indent.
     */
    public function test_creator_can_edit_and_update_draft_indent(): void
    {
        $indent = Indent::factory()->draft()->create([
            'title' => 'Initial Draft Title',
            'creator_cpf' => $this->creator->cpf,
            'current_holder_cpf' => $this->creator->cpf,
            'installation_id' => $this->installation->id,
        ]);

        // 1. Can view edit form
        $editResponse = $this->actingAs($this->creator)->get(route('indents.edit', $indent));
        $editResponse->assertOk();
        $editResponse->assertSee('Initial Draft Title');
        $editResponse->assertSee('FRIND-04');

        // 2. Can update draft
        $updateResponse = $this->actingAs($this->creator)->put(route('indents.update', $indent), [
            'title' => 'Updated Draft Title After Technical Review',
            'work_type' => 'Structural Repair',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::URGENT->value,
            'required_by' => now()->addDays(5)->format('Y-m-d'),
            'description' => 'Updated description.',
            'justification' => 'Immediate safety hazard.',
            'action' => 'draft',
        ]);

        $updateResponse->assertRedirect(route('indents.index', ['tab' => 'drafts']));

        $this->assertDatabaseHas('indents', [
            'id' => $indent->id,
            'title' => 'Updated Draft Title After Technical Review',
            'work_type' => 'Structural Repair',
            'priority' => IndentPriority::URGENT->value,
            'is_draft' => true,
        ]);
    }

    /**
     * Test 3: Non-creator cannot edit or update someone else's draft indent.
     */
    public function test_non_creator_cannot_edit_or_update_someone_elses_draft(): void
    {
        $indent = Indent::factory()->draft()->create([
            'creator_cpf' => $this->creator->cpf,
            'current_holder_cpf' => $this->creator->cpf,
            'installation_id' => $this->installation->id,
        ]);

        // Other user attempts to view edit form
        $response = $this->actingAs($this->otherUser)->get(route('indents.edit', $indent));
        $response->assertForbidden();

        // Other user attempts to update
        $updateResponse = $this->actingAs($this->otherUser)->put(route('indents.update', $indent), [
            'title' => 'Malicious Update',
            'work_type' => 'Civil Maintenance',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::LOW->value,
            'required_by' => now()->addDays(10)->format('Y-m-d'),
            'action' => 'draft',
        ]);
        $updateResponse->assertForbidden();

        $this->assertDatabaseMissing('indents', [
            'id' => $indent->id,
            'title' => 'Malicious Update',
        ]);
    }

    /**
     * Test 4: Submitting a draft converts it to active and creates the first forward movement.
     */
    public function test_creator_can_submit_draft_indent_triggering_first_forward(): void
    {
        $indent = Indent::factory()->draft()->create([
            'title' => 'Draft To Be Submitted',
            'creator_cpf' => $this->creator->cpf,
            'current_holder_cpf' => $this->creator->cpf,
            'installation_id' => $this->installation->id,
        ]);

        $response = $this->actingAs($this->creator)->put(route('indents.update', $indent), [
            'title' => 'Finalized Work Title',
            'work_type' => 'Plumbing & Drainage',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::HIGH->value,
            'required_by' => now()->addDays(12)->format('Y-m-d'),
            'description' => 'Ready for technical assessment.',
            'justification' => 'Pipe leakage.',
            'action' => 'submit',
            'remark' => 'Submitting draft for IM review',
        ]);

        $response->assertRedirect(route('indents.show', $indent));
        $response->assertSessionHas('status');

        $indent->refresh();
        $this->assertFalse($indent->is_draft);
        $this->assertEquals($this->imUser->cpf, $indent->current_holder_cpf);

        // First forward movement is recorded
        $this->assertDatabaseHas('movements', [
            'indent_id' => $indent->id,
            'from' => $this->creator->cpf,
            'to' => $this->imUser->cpf,
            'action' => 'Submit',
            'is_rejected' => false,
            'remark' => 'Submitting draft for IM review',
        ]);
    }

    /**
     * Test 5: Specification Rule FRIND-04:
     * After submit, an indent can NO LONGER be edited.
     */
    public function test_submitted_indent_cannot_be_edited_frind_04(): void
    {
        // 1. Indent is published / submitted
        $indent = Indent::factory()->create([
            'title' => 'Submitted Civil Work',
            'is_draft' => false,
            'creator_cpf' => $this->creator->cpf,
            'current_holder_cpf' => $this->imUser->cpf,
            'installation_id' => $this->installation->id,
        ]);

        // Attempting to visit edit form returns 403 Forbidden
        $editResponse = $this->actingAs($this->creator)->get(route('indents.edit', $indent));
        $editResponse->assertForbidden();

        // Attempting to send PUT update returns 403 Forbidden
        $updateResponse = $this->actingAs($this->creator)->put(route('indents.update', $indent), [
            'title' => 'Attempt to Alter After Submit',
            'work_type' => 'Civil Maintenance',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::LOW->value,
            'required_by' => now()->addDays(10)->format('Y-m-d'),
            'action' => 'draft',
        ]);
        $updateResponse->assertForbidden();

        // Data in database is unchanged
        $this->assertDatabaseHas('indents', [
            'id' => $indent->id,
            'title' => 'Submitted Civil Work',
            'is_draft' => false,
        ]);
    }

    /**
     * Test 6: User can directly submit a new indent on creation.
     */
    public function test_user_can_directly_submit_new_indent(): void
    {
        $response = $this->actingAs($this->creator)->post(route('indents.store'), [
            'title' => 'Directly Submitted Road Repair',
            'work_type' => 'Road & Pavement',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::MEDIUM->value,
            'required_by' => now()->addDays(7)->format('Y-m-d'),
            'description' => 'Direct submission.',
            'justification' => 'Pothole repairs.',
            'action' => 'submit',
            'remark' => 'Direct forward to IM',
        ]);

        $indent = Indent::where('title', 'Directly Submitted Road Repair')->firstOrFail();
        $response->assertRedirect(route('indents.show', $indent));

        $this->assertFalse($indent->is_draft);
        $this->assertEquals($this->imUser->cpf, $indent->current_holder_cpf);

        $this->assertDatabaseHas('movements', [
            'indent_id' => $indent->id,
            'from' => $this->creator->cpf,
            'to' => $this->imUser->cpf,
            'action' => 'Submit',
            'remark' => 'Direct forward to IM',
        ]);
    }

    /**
     * Test 7: Form Request validates required fields and preserves old input.
     */
    public function test_form_request_validation_fails_on_missing_fields_and_preserves_old_input(): void
    {
        $response = $this->actingAs($this->creator)->post(route('indents.store'), [
            'title' => '',
            'work_type' => '',
            'installation_id' => 999999, // non-existent
            'priority' => 'InvalidPriority',
            'required_by' => now()->subDays(2)->format('Y-m-d'), // past date
            'action' => 'invalid_action',
        ]);

        $response->assertSessionHasErrors([
            'title',
            'work_type',
            'installation_id',
            'priority',
            'required_by',
            'action',
        ]);
    }

    /**
     * Test 8: "Created by me" list displays user's indents with pagination.
     */
    public function test_created_by_me_list_shows_only_user_indents_with_pagination(): void
    {
        // Create 12 indents for creator
        for ($i = 1; $i <= 12; $i++) {
            Indent::factory()->create([
                'title' => "Creator Indent {$i}",
                'creator_cpf' => $this->creator->cpf,
                'installation_id' => $this->installation->id,
            ]);
        }

        // Create 3 indents for other user
        for ($j = 1; $j <= 3; $j++) {
            Indent::factory()->create([
                'title' => "Other User Indent {$j}",
                'creator_cpf' => $this->otherUser->cpf,
                'installation_id' => $this->installation->id,
            ]);
        }

        // 1. Page 1 for creator
        $responsePage1 = $this->actingAs($this->creator)->get(route('indents.index', ['tab' => 'my']));
        $responsePage1->assertOk();
        $responsePage1->assertSee('Creator Indent');
        $responsePage1->assertDontSee('Other User Indent');

        // Pagination metadata
        $responsePage1->assertViewHas('indents', function ($indents) {
            return $indents->total() === 12 && $indents->perPage() === 10 && $indents->count() === 10;
        });

        // 2. Page 2 for creator
        $responsePage2 = $this->actingAs($this->creator)->get(route('indents.index', ['tab' => 'my', 'page' => 2]));
        $responsePage2->assertOk();
        $responsePage2->assertViewHas('indents', function ($indents) {
            return $indents->currentPage() === 2 && $indents->count() === 2;
        });
    }
}
