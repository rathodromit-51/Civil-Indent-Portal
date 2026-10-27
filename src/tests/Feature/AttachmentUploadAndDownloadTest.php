<?php

namespace Tests\Feature;

use App\Enums\IndentPriority;
use App\Models\Attachment;
use App\Models\Indent;
use App\Models\Installation;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentUploadAndDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;
    protected User $otherUser;
    protected User $superAdmin;
    protected User $imUser;
    protected Installation $installation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->creator = User::where('cpf', 'USER001')->firstOrFail();
        $this->otherUser = User::where('cpf', 'USER002')->firstOrFail();
        $this->superAdmin = User::where('cpf', 'ADMIN001')->firstOrFail();

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
     * Helper to build valid indent payload.
     */
    protected function basePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Structural Survey of River Crossing Culvert',
            'work_type' => 'Bridge Maintenance',
            'installation_id' => $this->installation->id,
            'priority' => IndentPriority::HIGH->value,
            'required_by' => now()->addDays(20)->format('Y-m-d'),
            'description' => 'Heavy CAD scans of structural members and foundation pile locations.',
            'justification' => 'Scouring detected around bridge abutments.',
            'action' => 'draft',
        ], $overrides);
    }

    /**
     * Test 1: Upload a 90 MB file to private filesystem disk.
     */
    public function test_user_can_upload_90mb_file_and_it_stores_on_private_disk(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        // Create a 90 MB simulated CAD file (90 * 1024 KB = 92,160 KB)
        $largeFile = UploadedFile::fake()->create('bridge_elevation_scan.dwg', 90 * 1024, 'application/octet-stream');

        $payload = $this->basePayload([
            'attachments' => [$largeFile],
        ]);

        $response = $this->actingAs($this->creator)->post(route('indents.store'), $payload);

        $response->assertRedirect(route('indents.index', ['tab' => 'drafts']));
        $this->assertDatabaseHas('indents', [
            'title' => 'Structural Survey of River Crossing Culvert',
            'creator_cpf' => $this->creator->cpf,
            'is_draft' => true,
        ]);

        $indent = Indent::where('title', 'Structural Survey of River Crossing Culvert')->firstOrFail();

        $this->assertDatabaseHas('attachments', [
            'indent_id' => $indent->id,
            'uploader' => $this->creator->cpf,
            'original_name' => 'bridge_elevation_scan.dwg',
        ]);

        $attachment = Attachment::where('indent_id', $indent->id)->firstOrFail();
        $this->assertEquals(90 * 1024 * 1024, $attachment->size);
        $this->assertStringStartsWith('indents/' . $indent->id . '/', $attachment->stored_path);

        // Verify stored strictly on private disk, NOT public disk
        Storage::disk('private')->assertExists($attachment->stored_path);
        Storage::disk('public')->assertMissing($attachment->stored_path);
    }

    /**
     * Test 2: Executable files (.exe, etc.) are strictly rejected.
     */
    public function test_executable_files_are_strictly_rejected(): void
    {
        Storage::fake('private');

        $exeFile = UploadedFile::fake()->create('malicious_tool.exe', 1024, 'application/x-msdownload');

        $payload = $this->basePayload([
            'title' => 'Attempted Malware Attachment Indent',
            'attachments' => [$exeFile],
        ]);

        $response = $this->actingAs($this->creator)->post(route('indents.store'), $payload);

        $response->assertSessionHasErrors(['attachments.0']);
        $this->assertDatabaseMissing('attachments', [
            'original_name' => 'malicious_tool.exe',
        ]);
        $this->assertDatabaseMissing('indents', [
            'title' => 'Attempted Malware Attachment Indent',
        ]);
    }

    /**
     * Test 3: Files exceeding 100 MB limit are rejected.
     */
    public function test_file_exceeding_100mb_is_rejected(): void
    {
        Storage::fake('private');

        // 105 MB file (105 * 1024 KB = 107,520 KB)
        $oversizedFile = UploadedFile::fake()->create('huge_drone_survey.zip', 105 * 1024, 'application/zip');

        $payload = $this->basePayload([
            'title' => 'Oversized Survey File Indent',
            'attachments' => [$oversizedFile],
        ]);

        $response = $this->actingAs($this->creator)->post(route('indents.store'), $payload);

        $response->assertSessionHasErrors(['attachments.0']);
        $this->assertDatabaseMissing('attachments', [
            'original_name' => 'huge_drone_survey.zip',
        ]);
    }

    /**
     * Test 4: A user outside the chain gets 403 Forbidden on the download URL.
     */
    public function test_user_outside_the_chain_gets_403_forbidden_on_download(): void
    {
        Storage::fake('private');

        $pdfFile = UploadedFile::fake()->create('geotech_report.pdf', 2048, 'application/pdf');

        $payload = $this->basePayload([
            'title' => 'Soil Bearing Capacity Investigation',
            'attachments' => [$pdfFile],
        ]);

        $this->actingAs($this->creator)->post(route('indents.store'), $payload);

        $attachment = Attachment::where('original_name', 'geotech_report.pdf')->firstOrFail();

        // USER002 is outside the chain (not creator, not holder, not participant, not admin)
        $response = $this->actingAs($this->otherUser)->get(route('attachments.download', $attachment));

        $response->assertForbidden();
    }

    /**
     * Test 5: Authorized users (Creator, Holder, Super Admin) can stream download.
     */
    public function test_authorized_users_can_stream_download_attachment(): void
    {
        Storage::fake('private');

        $dxfFile = UploadedFile::fake()->create('approved_drawing.dxf', 1024, 'application/dxf');

        $payload = $this->basePayload([
            'title' => 'Storm Water Drain Layout',
            'attachments' => [$dxfFile],
        ]);

        $this->actingAs($this->creator)->post(route('indents.store'), $payload);

        $attachment = Attachment::where('original_name', 'approved_drawing.dxf')->firstOrFail();

        // 1. Indent Creator can download
        $creatorResponse = $this->actingAs($this->creator)->get(route('attachments.download', $attachment));
        $creatorResponse->assertOk();
        $this->assertTrue(
            str_contains($creatorResponse->headers->get('content-disposition') ?? '', 'approved_drawing.dxf')
        );

        // 2. Super Admin can download
        $adminResponse = $this->actingAs($this->superAdmin)->get(route('attachments.download', $attachment));
        $adminResponse->assertOk();
    }

    /**
     * Test 6: Multiple allowed file types can be uploaded in a single submission.
     */
    public function test_multiple_allowed_file_types_can_be_uploaded(): void
    {
        Storage::fake('private');

        $files = [
            UploadedFile::fake()->create('site_photo.jpg', 500, 'image/jpeg'),
            UploadedFile::fake()->create('bill_of_quantities.xlsx', 800, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            UploadedFile::fake()->create('contour_map.kml', 300, 'application/vnd.google-earth.kml+xml'),
            UploadedFile::fake()->create('tender_spec.docx', 1200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            UploadedFile::fake()->create('soil_logs.zip', 4000, 'application/zip'),
        ];

        $payload = $this->basePayload([
            'title' => 'Comprehensive Refinery Substation Expansion',
            'attachments' => $files,
        ]);

        $response = $this->actingAs($this->creator)->post(route('indents.store'), $payload);

        $response->assertRedirect(route('indents.index', ['tab' => 'drafts']));

        $indent = Indent::where('title', 'Comprehensive Refinery Substation Expansion')->firstOrFail();
        $this->assertEquals(5, $indent->attachments()->count());

        foreach ($files as $file) {
            $this->assertDatabaseHas('attachments', [
                'indent_id' => $indent->id,
                'original_name' => $file->name,
            ]);
        }
    }

    /**
     * Test 7: Creator can delete draft attachment, removing both storage file and DB record.
     */
    public function test_creator_can_delete_draft_attachment(): void
    {
        Storage::fake('private');

        $file = UploadedFile::fake()->create('draft_notes.pdf', 500, 'application/pdf');

        $payload = $this->basePayload([
            'title' => 'Temporary Pavement Marking',
            'attachments' => [$file],
        ]);

        $this->actingAs($this->creator)->post(route('indents.store'), $payload);

        $attachment = Attachment::where('original_name', 'draft_notes.pdf')->firstOrFail();
        $path = $attachment->stored_path;

        Storage::disk('private')->assertExists($path);

        // Other user cannot delete
        $unauthorizedDelete = $this->actingAs($this->otherUser)->delete(route('attachments.destroy', $attachment));
        $unauthorizedDelete->assertForbidden();
        Storage::disk('private')->assertExists($path);

        // Creator can delete
        $authorizedDelete = $this->actingAs($this->creator)->delete(route('attachments.destroy', $attachment));
        $authorizedDelete->assertRedirect();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('private')->assertMissing($path);
    }

    /**
     * Test 8: Authorized person can attach file with indent in indent route (POST /indents/{indent}/attachments).
     */
    public function test_authorized_person_can_attach_file_to_indent_in_indent_route(): void
    {
        Storage::fake('private');

        // Create indent
        $this->actingAs($this->creator)->post(route('indents.store'), $this->basePayload([
            'title' => 'Structural Audit of Compressor Foundation',
        ]));

        $indent = Indent::where('title', 'Structural Audit of Compressor Foundation')->firstOrFail();

        // Indent creator attaches a file via the indent route
        $drawingFile = UploadedFile::fake()->create('foundation_detail_v2.dwg', 4096, 'application/octet-stream');

        $response = $this->actingAs($this->creator)->post(route('indents.attachments.store', $indent), [
            'attachments' => [$drawingFile],
        ]);

        $response->assertRedirect(route('indents.show', $indent));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('attachments', [
            'indent_id' => $indent->id,
            'uploader' => $this->creator->cpf,
            'original_name' => 'foundation_detail_v2.dwg',
        ]);

        $attachment = Attachment::where('original_name', 'foundation_detail_v2.dwg')->firstOrFail();
        Storage::disk('private')->assertExists($attachment->stored_path);

        // IM (current holder / installation manager) can also attach a file to a submitted indent via indent route
        $this->actingAs($this->creator)->post(route('indents.store'), $this->basePayload([
            'title' => 'Submitted Indent for IM Review',
            'action' => 'submit',
        ]));

        $submittedIndent = Indent::where('title', 'Submitted Indent for IM Review')->firstOrFail();

        $imReport = UploadedFile::fake()->create('im_technical_review.pdf', 1024, 'application/pdf');

        $imResponse = $this->actingAs($this->imUser)->post(route('indents.attachments.store', $submittedIndent), [
            'attachments' => [$imReport],
        ]);

        $imResponse->assertRedirect(route('indents.show', $submittedIndent));

        $this->assertDatabaseHas('attachments', [
            'indent_id' => $submittedIndent->id,
            'uploader' => $this->imUser->cpf,
            'original_name' => 'im_technical_review.pdf',
        ]);
    }

    /**
     * Test 9: Unauthorized person outside the chain gets 403 Forbidden when attempting to attach file in indent route.
     */
    public function test_unauthorized_person_cannot_attach_file_to_indent_in_indent_route(): void
    {
        Storage::fake('private');

        $this->actingAs($this->creator)->post(route('indents.store'), $this->basePayload([
            'title' => 'Pavement Repair at Gate 2',
        ]));

        $indent = Indent::where('title', 'Pavement Repair at Gate 2')->firstOrFail();

        $intruderFile = UploadedFile::fake()->create('unsolicited_proposal.pdf', 1024, 'application/pdf');

        // Other user (not creator, not holder, not admin, not participant) attempts to attach
        $response = $this->actingAs($this->otherUser)->post(route('indents.attachments.store', $indent), [
            'attachments' => [$intruderFile],
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('attachments', [
            'indent_id' => $indent->id,
            'original_name' => 'unsolicited_proposal.pdf',
        ]);
    }
}
