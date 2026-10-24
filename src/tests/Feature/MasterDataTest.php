<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Installation;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed initial data
        $this->seed(UserSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * Test role seeder complies strictly with Specification §3.
     */
    public function test_roles_conform_to_spec_section_3(): void
    {
        // CE has multi_holder = true, scoped to installation
        $ce = Role::where('name', 'CE')->first();
        $this->assertNotNull($ce);
        $this->assertEquals('installation', $ce->scope_type);
        $this->assertTrue((bool)$ce->multi_holder);

        // IM has multi_holder = false, scoped to installation
        $im = Role::where('name', 'IM')->first();
        $this->assertNotNull($im);
        $this->assertEquals('installation', $im->scope_type);
        $this->assertFalse((bool)$im->multi_holder);

        // SAM has multi_holder = false, scoped to area
        $sam = Role::where('name', 'SAM')->first();
        $this->assertNotNull($sam);
        $this->assertEquals('area', $sam->scope_type);
        $this->assertFalse((bool)$sam->multi_holder);

        // SM has multi_holder = false, scoped to area
        $sm = Role::where('name', 'SM')->first();
        $this->assertNotNull($sm);
        $this->assertEquals('area', $sm->scope_type);
        $this->assertFalse((bool)$sm->multi_holder);

        // Global roles: Super Admin, User, HES, LM Works, ICO
        foreach (['Super Admin', 'User', 'HES', 'LM Works', 'ICO'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $this->assertNotNull($role, "Role {$roleName} should exist");
            $this->assertEquals('none', $role->scope_type, "Role {$roleName} should have scope_type = none");
        }
    }

    /**
     * Test seeders are strictly idempotent (can run repeatedly without error).
     */
    public function test_master_data_and_role_seeders_are_idempotent(): void
    {
        $areaCountBefore = Area::count();
        $installationCountBefore = Installation::count();
        $roleCountBefore = Role::count();

        // Run seeders again
        $this->seed(MasterDataSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertEquals($areaCountBefore, Area::count());
        $this->assertEquals($installationCountBefore, Installation::count());
        $this->assertEquals($roleCountBefore, Role::count());
    }

    /**
     * Test unauthenticated access to admin routes is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.areas.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('admin.roles.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test non-admin user receives 403 Forbidden on admin master data routes.
     */
    public function test_regular_user_cannot_access_admin_screens(): void
    {
        $user = User::where('cpf', 'USER001')->first(); // Regular user
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get(route('admin.areas.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get(route('admin.roles.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get(route('admin.areas.create'));
        $response->assertStatus(403);
    }

    /**
     * Test Super Admin can view areas, installations, and roles screens.
     */
    public function test_super_admin_can_view_admin_screens(): void
    {
        $admin = User::where('cpf', 'ADMIN001')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get(route('admin.areas.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Data Management');
        $response->assertSee('Site Alpha');

        $response = $this->actingAs($admin)->get(route('admin.roles.index'));
        $response->assertStatus(200);
        $response->assertSee('System Roles');
        $response->assertSee('Spec §3');
        $response->assertSee('CE');
        $response->assertSee('IM');
        $response->assertSee('SAM');
    }

    /**
     * Test Super Admin can create, update, and delete an Area.
     */
    public function test_super_admin_can_crud_area(): void
    {
        $admin = User::where('cpf', 'ADMIN001')->first();

        // Create Area
        $createResponse = $this->actingAs($admin)->post(route('admin.areas.store'), [
            'name' => 'Delta Offshore Basin',
            'code' => 'AREA-DELTA',
        ]);

        $createResponse->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseHas('areas', [
            'name' => 'Delta Offshore Basin',
            'code' => 'AREA-DELTA',
        ]);

        $area = Area::where('code', 'AREA-DELTA')->first();

        // Update Area
        $updateResponse = $this->actingAs($admin)->put(route('admin.areas.update', $area), [
            'name' => 'Delta Deepwater Complex',
            'code' => 'AREA-DELTA-DW',
        ]);

        $updateResponse->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseHas('areas', [
            'id' => $area->id,
            'name' => 'Delta Deepwater Complex',
            'code' => 'AREA-DELTA-DW',
        ]);

        // Delete Area
        $deleteResponse = $this->actingAs($admin)->delete(route('admin.areas.destroy', $area));
        $deleteResponse->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseMissing('areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test area validation enforces unique code and required fields.
     */
    public function test_area_validation_rules(): void
    {
        $admin = User::where('cpf', 'ADMIN001')->first();

        // Empty code and name
        $response = $this->actingAs($admin)->from(route('admin.areas.create'))->post(route('admin.areas.store'), [
            'name' => '',
            'code' => '',
        ]);
        $response->assertRedirect(route('admin.areas.create'));
        $response->assertSessionHasErrors(['name', 'code']);

        // Duplicate code ('ALPHA' already seeded)
        $response = $this->actingAs($admin)->from(route('admin.areas.create'))->post(route('admin.areas.store'), [
            'name' => 'Another Alpha',
            'code' => 'ALPHA',
        ]);
        $response->assertRedirect(route('admin.areas.create'));
        $response->assertSessionHasErrors(['code']);
    }

    /**
     * Test Super Admin can create, update, and delete an Installation.
     */
    public function test_super_admin_can_crud_installation(): void
    {
        $admin = User::where('cpf', 'ADMIN001')->first();
        $area = Area::first();

        // Create Installation
        $createResponse = $this->actingAs($admin)->post(route('admin.installations.store'), [
            'area_id' => $area->id,
            'name' => 'Subsea Manifold 4',
            'code' => 'INST-SSM-4',
        ]);

        $createResponse->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseHas('installations', [
            'area_id' => $area->id,
            'name' => 'Subsea Manifold 4',
            'code' => 'INST-SSM-4',
        ]);

        $installation = Installation::where('code', 'INST-SSM-4')->first();

        // Update Installation
        $updateResponse = $this->actingAs($admin)->put(route('admin.installations.update', $installation), [
            'area_id' => $area->id,
            'name' => 'Subsea Manifold 4 Upgraded',
            'code' => 'INST-SSM-4-UP',
        ]);

        $updateResponse->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseHas('installations', [
            'id' => $installation->id,
            'name' => 'Subsea Manifold 4 Upgraded',
            'code' => 'INST-SSM-4-UP',
        ]);

        // Delete Installation
        $deleteResponse = $this->actingAs($admin)->delete(route('admin.installations.destroy', $installation));
        $deleteResponse->assertRedirect(route('admin.areas.index'));
        $this->assertDatabaseMissing('installations', [
            'id' => $installation->id,
        ]);
    }

    /**
     * Test installation validation requires valid area and unique code.
     */
    public function test_installation_validation_rules(): void
    {
        $admin = User::where('cpf', 'ADMIN001')->first();

        // Non-existent area
        $response = $this->actingAs($admin)->from(route('admin.installations.create'))->post(route('admin.installations.store'), [
            'area_id' => 99999,
            'name' => 'Invalid Test',
            'code' => 'INST-INV-1',
        ]);
        $response->assertRedirect(route('admin.installations.create'));
        $response->assertSessionHasErrors(['area_id']);

        // Duplicate installation code ('ALP-01' already seeded)
        $response = $this->actingAs($admin)->from(route('admin.installations.create'))->post(route('admin.installations.store'), [
            'area_id' => Area::first()->id,
            'name' => 'Duplicate Code Test',
            'code' => 'ALP-01',
        ]);
        $response->assertRedirect(route('admin.installations.create'));
        $response->assertSessionHasErrors(['code']);
    }
}
