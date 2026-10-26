<?php

namespace Tests\Feature;

use App\Enums\IndentPriority;
use App\Enums\IndentStatus;
use App\Enums\MovementAction;
use App\Models\Attachment;
use App\Models\Indent;
use App\Models\IndentParticipant;
use App\Models\Installation;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_indent_with_auto_generated_number(): void
    {
        $year = (int) date('Y');

        $indent1 = Indent::factory()->create();
        $this->assertEquals(sprintf('CI-%04d-0001', $year), $indent1->number);
        $this->assertInstanceOf(IndentStatus::class, $indent1->status);
        $this->assertEquals(IndentStatus::OPEN, $indent1->status);
        $this->assertFalse($indent1->is_draft);

        $indent2 = Indent::factory()->create();
        $this->assertEquals(sprintf('CI-%04d-0002', $year), $indent2->number);

        $indent3 = Indent::factory()->create();
        $this->assertEquals(sprintf('CI-%04d-0003', $year), $indent3->number);
    }

    public function test_custom_indent_number_is_respected_if_provided(): void
    {
        $indent = Indent::factory()->create(['number' => 'CUSTOM-9999']);
        $this->assertEquals('CUSTOM-9999', $indent->number);
    }

    public function test_indent_status_is_backed_enum(): void
    {
        $indent = Indent::factory()->create([
            'status' => IndentStatus::IN_PROGRESS,
        ]);

        $this->assertSame(IndentStatus::IN_PROGRESS, $indent->status);
        $this->assertEquals('InProgress', $indent->status->value);

        $indent->update(['status' => IndentStatus::CLOSED]);
        $indent->refresh();
        $this->assertSame(IndentStatus::CLOSED, $indent->status);
    }

    public function test_indent_draft_flag_and_scopes(): void
    {
        $published = Indent::factory()->create(['is_draft' => false]);
        $draft = Indent::factory()->draft()->create();

        $this->assertTrue($draft->is_draft);
        $this->assertFalse($published->is_draft);

        $drafts = Indent::drafts()->get();
        $this->assertTrue($drafts->contains($draft));
        $this->assertFalse($drafts->contains($published));

        $publishedList = Indent::published()->get();
        $this->assertTrue($publishedList->contains($published));
        $this->assertFalse($publishedList->contains($draft));
    }

    public function test_indent_factory_creates_indent_with_movement(): void
    {
        $indent = Indent::factory()->withMovement([
            'action' => MovementAction::SUBMIT->value,
            'remark' => 'Initial submission for technical review',
        ])->create();

        $this->assertDatabaseHas('indents', ['id' => $indent->id]);
        $this->assertCount(1, $indent->movements);

        $movement = $indent->movements->first();
        $this->assertEquals($indent->id, $movement->indent_id);
        $this->assertEquals($indent->creator_cpf, $movement->from);
        $this->assertEquals($indent->current_holder_cpf, $movement->to);
        $this->assertEquals('Submit', $movement->action);
        $this->assertFalse($movement->is_rejected);
        $this->assertEquals('Initial submission for technical review', $movement->remark);
    }

    public function test_movement_rejection_state(): void
    {
        $indent = Indent::factory()->create();

        $movement = Movement::factory()->rejected()->create([
            'indent_id' => $indent->id,
        ]);

        $this->assertTrue($movement->is_rejected);
        $this->assertEquals(MovementAction::REJECT->value, $movement->action);
    }

    public function test_indent_relationships_resolve_properly(): void
    {
        $creator = User::factory()->create(['cpf' => 'USER1001', 'name' => 'Creator User']);
        $holder = User::factory()->create(['cpf' => 'ENG2002', 'name' => 'Engineer User']);
        $installation = Installation::factory()->create(['name' => 'Ankleshwar GGS-1']);

        $indent = Indent::create([
            'title' => 'Structural wall repair at GGS-1',
            'description' => 'Fix crack near compressor area.',
            'work_type' => 'Civil Maintenance',
            'installation_id' => $installation->id,
            'priority' => IndentPriority::HIGH,
            'required_by' => '2026-11-30',
            'justification' => 'Safety requirement',
            'status' => IndentStatus::OPEN,
            'is_draft' => false,
            'creator' => $creator,
            'current_holder' => $holder,
        ]);

        $this->assertEquals('USER1001', $indent->creator_cpf);
        $this->assertEquals('ENG2002', $indent->current_holder_cpf);
        $this->assertEquals('Creator User', $indent->creator->name);
        $this->assertEquals('Engineer User', $indent->currentHolder->name);
        $this->assertEquals('Ankleshwar GGS-1', $indent->installation->name);
        $this->assertTrue($installation->indents->contains($indent));
    }

    public function test_indent_participants(): void
    {
        $indent = Indent::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        IndentParticipant::factory()->create([
            'indent_id' => $indent->id,
            'cpf' => $user1->cpf,
        ]);

        IndentParticipant::factory()->create([
            'indent_id' => $indent->id,
            'cpf' => $user2->cpf,
        ]);

        $this->assertCount(2, $indent->participants);
        $this->assertCount(2, $indent->participantUsers);
        $this->assertTrue($indent->participantUsers->pluck('cpf')->contains($user1->cpf));
        $this->assertTrue($indent->participantUsers->pluck('cpf')->contains($user2->cpf));

        // Test duplicate participant prevention
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        IndentParticipant::create([
            'indent_id' => $indent->id,
            'cpf' => $user1->cpf,
        ]);
    }

    public function test_attachments_linked_to_indent_and_optional_movement(): void
    {
        $indent = Indent::factory()->create();
        $movement = Movement::factory()->create(['indent_id' => $indent->id]);
        $uploader = User::factory()->create(['cpf' => 'UPL9999']);

        // General indent attachment without movement
        $attachment1 = Attachment::factory()->create([
            'indent_id' => $indent->id,
            'movement_id' => null,
            'uploader' => $uploader->cpf,
            'original_name' => 'site_plan.pdf',
            'stored_path' => 'attachments/site_plan.pdf',
            'size' => 1048576, // 1 MB
        ]);

        // Attachment associated with movement
        $attachment2 = Attachment::factory()->create([
            'indent_id' => $indent->id,
            'movement_id' => $movement->id,
            'uploader' => $uploader->cpf,
            'original_name' => 'site_inspection.jpg',
            'stored_path' => 'attachments/site_inspection.jpg',
            'size' => 2097152, // 2 MB
        ]);

        $this->assertCount(2, $indent->attachments);
        $this->assertCount(1, $movement->attachments);
        $this->assertNull($attachment1->movement);
        $this->assertEquals($movement->id, $attachment2->movement->id);
        $this->assertEquals('1 MB', $attachment1->formatted_size);
        $this->assertEquals('2 MB', $attachment2->formatted_size);
        $this->assertEquals('UPL9999', $attachment1->uploaderUser->cpf);
    }
}
