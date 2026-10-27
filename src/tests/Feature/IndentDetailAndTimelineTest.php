<?php

namespace Tests\Feature;

use App\Enums\IndentPriority;
use App\Enums\IndentStatus;
use App\Enums\MovementAction;
use App\Models\Attachment;
use App\Models\Indent;
use App\Models\Installation;
use App\Models\Movement;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IndentDetailAndTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $im;
    protected User $sam;
    protected User $sm;
    protected User $hes;
    protected User $stranger;
    protected Installation $installation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->creator = User::where('cpf', 'USER001')->firstOrFail();
        $this->im = User::where('cpf', 'IM001')->firstOrFail();
        $this->sam = User::where('cpf', 'SAM001')->firstOrFail();
        $this->sm = User::where('cpf', 'SM001')->firstOrFail();
        $this->hes = User::where('cpf', 'HES001')->firstOrFail();
        $this->stranger = User::where('cpf', 'USER002')->firstOrFail();

        $this->installation = Installation::firstOrFail();

        // Assign roles
        $this->assignRole($this->im, 'IM', $this->installation->id, null);
        $this->assignRole($this->sam, 'SAM', null, $this->installation->area_id);
        $this->assignRole($this->sm, 'SM', null, $this->installation->area_id);
        $this->assignRole($this->hes, 'HES', null, null);
        // Stranger has a role in another context, but never participates in this indent
        $this->assignRole($this->stranger, 'User', null, null);
    }

    protected function assignRole(User $user, string $roleName, ?int $installationId, ?int $areaId): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
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
    }

    /**
     * Helper to create a submitted indent with initial movement and attachment.
     */
    protected function createSubmittedIndent(): Indent
    {
        Storage::fake('private');

        $drawing = UploadedFile::fake()->create('foundation_spec.dwg', 2048, 'application/octet-stream');

        $response = $this->actingAs($this->creator)->post(route('indents.store'), [
            'title' => 'Structural Repair of Jetty Column Piles',
            'work_type' => 'Civil Maintenance',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::HIGH->value,
            'required_by' => now()->addDays(15)->format('Y-m-d'),
            'description' => 'Detailed marine pile reinforcement and epoxy concrete grouting.',
            'justification' => 'Tidal scouring detected on east pier column.',
            'action' => 'submit',
            'remark' => 'Submitting requisition for IM technical review.',
            'attachments' => [$drawing],
        ]);

        $response->assertRedirect();

        return Indent::where('title', 'Structural Repair of Jetty Column Piles')->firstOrFail();
    }

    /**
     * Test: Participant sees the entire flow from creation:
     * from -> to, role at that time, date-time, action, remark, rejection flagged, all fields and attachments.
     */
    public function test_participant_sees_entire_flow_with_all_details(): void
    {
        $indent = $this->createSubmittedIndent();

        // 1. IM forwards to SAM with remark
        $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Site inspected by IM Pooja Nair. Forwarded to SAM for area concurrence.',
        ]);

        // 2. SAM forwards with rejection flag back or to SM
        $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sm->cpf,
            'action' => MovementAction::FORWARD->value,
            'is_rejected' => '1',
            'remark' => 'Marine epoxy specifications require revision to Grade C40.',
        ]);

        $indent->refresh();

        // The creator views the page
        $creatorResponse = $this->actingAs($this->creator)->get(route('indents.show', $indent));
        $creatorResponse->assertOk();

        // Verify all fields are present
        $creatorResponse->assertSee($indent->number);
        $creatorResponse->assertSee('Structural Repair of Jetty Column Piles');
        $creatorResponse->assertSee('Civil Maintenance');
        $creatorResponse->assertSee($this->installation->name);
        $creatorResponse->assertSee($indent->required_by->format('d M Y'));
        $creatorResponse->assertSee('Detailed marine pile reinforcement and epoxy concrete grouting.');
        $creatorResponse->assertSee('Tidal scouring detected on east pier column.');

        // Verify attachments are visible
        $creatorResponse->assertSee('foundation_spec.dwg');

        // Verify full flow timeline details:
        // Step 1: Submit (from User [User] to IM [IM])
        $creatorResponse->assertSee('Step #1');
        $creatorResponse->assertSee('SUBMIT');
        $creatorResponse->assertSee($this->creator->name);
        $creatorResponse->assertSee($this->creator->cpf);
        $creatorResponse->assertSee('Submitting requisition for IM technical review.');

        // Step 2: Forward (from IM [IM] to SAM [SAM])
        $creatorResponse->assertSee('Step #2');
        $creatorResponse->assertSee('FORWARD');
        $creatorResponse->assertSee($this->im->name);
        $creatorResponse->assertSee('Site inspected by IM Pooja Nair');

        // Step 3: Rejection Flagged (from SAM [SAM] to SM [SM])
        $creatorResponse->assertSee('Step #3');
        $creatorResponse->assertSee('REJECTED');
        $creatorResponse->assertSee('Marine epoxy specifications require revision to Grade C40.');

        // Verify intermediate participant (SAM) also sees the entire flow
        $samResponse = $this->actingAs($this->sam)->get(route('indents.show', $indent));
        $samResponse->assertOk();
        $samResponse->assertSee('Step #1');
        $samResponse->assertSee('Step #2');
        $samResponse->assertSee('Step #3');
        $samResponse->assertSee('Marine epoxy specifications require revision to Grade C40.');
    }

    /**
     * Test: A stranger (non-participant who never received the indent) gets 403 Forbidden.
     */
    public function test_stranger_gets_403_forbidden(): void
    {
        $indent = $this->createSubmittedIndent();

        // IM forwards to SAM
        $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Forwarding to SAM',
        ]);

        // The stranger has never received or touched this indent
        $this->assertFalse(
            $indent->participants()->where('cpf', $this->stranger->cpf)->exists()
        );

        $response = $this->actingAs($this->stranger)->get(route('indents.show', $indent));

        $response->assertForbidden();
    }

    /**
     * Test: Stranger cannot view a draft indent either (403 Forbidden).
     */
    public function test_stranger_cannot_view_draft_indent(): void
    {
        $draft = Indent::create([
            'title' => 'Confidential Foundation Assessment',
            'work_type' => 'Civil Maintenance',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::LOW,
            'required_by' => now()->addDays(30),
            'status' => IndentStatus::OPEN,
            'is_draft' => true,
            'creator_cpf' => $this->creator->cpf,
            'current_holder_cpf' => $this->creator->cpf,
        ]);

        // Creator can view their draft
        $creatorRes = $this->actingAs($this->creator)->get(route('indents.show', $draft));
        $creatorRes->assertOk();

        // Stranger gets 403 Forbidden
        $strangerRes = $this->actingAs($this->stranger)->get(route('indents.show', $draft));
        $strangerRes->assertForbidden();
    }

    /**
     * Test: The page makes a sane number of queries (checks eager loading and no N+1).
     */
    public function test_show_page_makes_sane_number_of_queries_without_n_plus_one(): void
    {
        $indent = $this->createSubmittedIndent();

        // Build a multi-step movement chain
        $this->actingAs($this->im)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sam->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Step 2: IM to SAM',
        ]);

        $this->actingAs($this->sam)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->sm->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Step 3: SAM to SM',
        ]);

        $this->actingAs($this->sm)->post(route('indents.forward', $indent), [
            'recipient_cpf' => $this->hes->cpf,
            'action' => MovementAction::FORWARD->value,
            'remark' => 'Step 4: SM to HES',
        ]);

        // Add an extra attachment
        Storage::fake('private');
        $attachmentFile = UploadedFile::fake()->create('pile_corrosion_photo.jpg', 500, 'image/jpeg');
        $this->actingAs($this->hes)->post(route('indents.attachments.store', $indent), [
            'attachments' => [$attachmentFile],
        ]);

        $indent->refresh();

        // Enable query log
        DB::enableQueryLog();
        DB::flushQueryLog();

        // Act as creator viewing the page
        $response = $this->actingAs($this->creator)->get(route('indents.show', $indent));
        $response->assertOk();

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        // Verify total query count is sane and bounded (<= 25 queries total for full page with auth, layout, and all relations)
        $this->assertLessThanOrEqual(
            25,
            $queryCount,
            "Indent show page executed {$queryCount} queries, which exceeds the sane threshold. Check for N+1 queries."
        );

        // Verify that no individual table query is repeated per movement
        // (i.e. movements table is queried once, roles once, role_assignments once)
        $queriesByTable = [];
        foreach ($queryLog as $q) {
            $sql = strtolower($q['query']);
            if (preg_match('/from\s+[`"]?([a-z_]+)[`"]?/i', $sql, $matches)) {
                $table = $matches[1];
                $queriesByTable[$table] = ($queriesByTable[$table] ?? 0) + 1;
            }
        }

        // Each movement shouldn't cause an independent query on movements or role_assignments
        $this->assertLessThanOrEqual(2, $queriesByTable['movements'] ?? 0);
        $this->assertLessThanOrEqual(3, $queriesByTable['role_assignments'] ?? 0);
        $this->assertLessThanOrEqual(3, $queriesByTable['roles'] ?? 0);

        DB::disableQueryLog();
    }

    /**
     * Test indents index table renders correct updated status (e.g. Closed) instead of defaulting to Open.
     */
    public function test_indents_index_displays_updated_status_when_closed_by_ce(): void
    {
        $indent = Indent::create([
            'number' => 'CI-2026-9999',
            'title' => 'Structural Crack Repair at GGS-1',
            'work_type' => 'Structural Repair',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::HIGH,
            'required_by' => now()->addDays(7),
            'status' => IndentStatus::CLOSED,
            'is_draft' => false,
            'creator_cpf' => $this->creator->cpf,
            'current_holder_cpf' => 'CE001',
        ]);

        Movement::create([
            'indent_id' => $indent->id,
            'from' => 'CE001',
            'from_role' => 'CE',
            'to' => 'CE001',
            'to_role' => 'CE',
            'action' => MovementAction::CLOSE->value,
            'is_rejected' => false,
            'remark' => 'Civil work successfully completed and handed over.',
        ]);

        $response = $this->actingAs($this->creator)->get(route('indents.index', ['tab' => 'all']));
        $response->assertOk();
        $response->assertSee('badge-closed', false);
        $response->assertSee('Closed');
        $response->assertSee('Close (CE)');
    }
}
