<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Installation;
use App\Models\Role;
use App\Models\RoleAssignment;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds for Roles and Role Assignments.
     * Roles from spec §3:
     * - CE: multi_holder = true
     * - IM: scope_type = installation
     * - SAM: scope_type = area
     * Idempotent using updateOrCreate.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'scope_type' => 'none',
                'multi_holder' => false,
                'description' => 'Super Administrator — Full administrative access over master data and role configurations',
            ],
            [
                'name' => 'SM',
                'scope_type' => 'area',
                'multi_holder' => false,
                'description' => 'Surface Manager — Assigns SAM and IM, oversees operational area',
            ],
            [
                'name' => 'SAM',
                'scope_type' => 'area',
                'multi_holder' => false,
                'description' => 'Surface Area Manager — One per area',
            ],
            [
                'name' => 'IM',
                'scope_type' => 'installation',
                'multi_holder' => false,
                'description' => 'Installation Manager — One per installation',
            ],
            [
                'name' => 'CE',
                'scope_type' => 'installation',
                'multi_holder' => true,
                'description' => 'Civil Engineer — Executes the work (multiple engineers per installation)',
            ],
            [
                'name' => 'HES',
                'scope_type' => 'none',
                'multi_holder' => false,
                'description' => 'Head Eng. Services (Head Engineering Services) — Reviews and forwards',
            ],
            [
                'name' => 'LM Works',
                'scope_type' => 'none',
                'multi_holder' => false,
                'description' => 'Civil Head (Line Manager Works) — Sees all indents',
            ],
            [
                'name' => 'ICO',
                'scope_type' => 'none',
                'multi_holder' => false,
                'description' => 'Incharge Civil Ops (ICO) — Assigns an engineer',
            ],
            [
                'name' => 'User',
                'scope_type' => 'none',
                'multi_holder' => true,
                'description' => 'Normal user — Creates the indent',
            ],
        ];

        $roleModels = [];
        foreach ($roles as $roleData) {
            $roleModels[$roleData['name']] = Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        // Cache lookup maps for areas and installations
        $areaAlpha = Area::where('code', 'ALPHA')->first();
        $areaBeta  = Area::where('code', 'BETA')->first();
        $areaGamma = Area::where('code', 'GAMMA')->first();

        $instAlp1 = Installation::where('code', 'ALP-01')->first();
        $instBet1 = Installation::where('code', 'BET-01')->first();
        $instGam1 = Installation::where('code', 'GAM-01')->first();

        // Seed initial role assignments for the seeded test users
        $assignments = [
            // Super Admin
            [
                'cpf' => 'ADMIN001',
                'role_id' => $roleModels['Super Admin']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            // SM (Surface Manager - Site Alpha)
            [
                'cpf' => 'SM001',
                'role_id' => $roleModels['SM']->id,
                'area_id' => $areaAlpha?->id,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            // SAMs (Surface Area Manager - Site Alpha & Site Beta)
            [
                'cpf' => 'SAM001',
                'role_id' => $roleModels['SAM']->id,
                'area_id' => $areaAlpha?->id,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'SAM002',
                'role_id' => $roleModels['SAM']->id,
                'area_id' => $areaBeta?->id,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            // IMs (Installation Manager - scoped to specific installations)
            [
                'cpf' => 'IM001',
                'role_id' => $roleModels['IM']->id,
                'area_id' => null,
                'installation_id' => $instAlp1?->id,
                'assigned_by' => 'SAM001',
            ],
            [
                'cpf' => 'IM002',
                'role_id' => $roleModels['IM']->id,
                'area_id' => null,
                'installation_id' => $instBet1?->id,
                'assigned_by' => 'SAM002',
            ],
            [
                'cpf' => 'IM003',
                'role_id' => $roleModels['IM']->id,
                'area_id' => null,
                'installation_id' => $instGam1?->id,
                'assigned_by' => 'ADMIN001',
            ],
            // HES, LM Works, ICO (none scope)
            [
                'cpf' => 'HES001',
                'role_id' => $roleModels['HES']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'LMW001',
                'role_id' => $roleModels['LM Works']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'ICO001',
                'role_id' => $roleModels['ICO']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            // CEs (multi_holder on installations)
            [
                'cpf' => 'CE001',
                'role_id' => $roleModels['CE']->id,
                'area_id' => null,
                'installation_id' => $instAlp1?->id,
                'assigned_by' => 'IM001',
            ],
            [
                'cpf' => 'CE002',
                'role_id' => $roleModels['CE']->id,
                'area_id' => null,
                'installation_id' => $instBet1?->id,
                'assigned_by' => 'IM002',
            ],
            [
                'cpf' => 'CE003',
                'role_id' => $roleModels['CE']->id,
                'area_id' => null,
                'installation_id' => $instGam1?->id,
                'assigned_by' => 'ADMIN001',
            ],
            // Normal Users
            [
                'cpf' => 'USER001',
                'role_id' => $roleModels['User']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'USER002',
                'role_id' => $roleModels['User']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'USER003',
                'role_id' => $roleModels['User']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'USER004',
                'role_id' => $roleModels['User']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'USER005',
                'role_id' => $roleModels['User']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
        ];

        foreach ($assignments as $assignData) {
            RoleAssignment::updateOrCreate(
                ['cpf' => $assignData['cpf']],
                $assignData
            );
        }
    }
}
