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

        // Cache lookup maps for areas and installations (Gujarat Industrial Belt)
        $areaVad = Area::where('code', 'VAD')->first();
        $areaSur = Area::where('code', 'SUR')->first();
        $areaAnk = Area::where('code', 'ANK')->first();

        $instVad1 = Installation::where('code', 'VAD-01')->first();
        $instSur1 = Installation::where('code', 'SUR-01')->first();
        $instAnk1 = Installation::where('code', 'ANK-01')->first();

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
            // SM (Surface Manager - Vadodara & Surat)
            [
                'cpf' => 'SM001',
                'role_id' => $roleModels['SM']->id,
                'area_id' => $areaVad?->id,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'SM002',
                'role_id' => $roleModels['SM']->id,
                'area_id' => $areaSur?->id,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            // SAMs (Surface Area Manager - Vadodara & Surat)
            [
                'cpf' => 'SAM001',
                'role_id' => $roleModels['SAM']->id,
                'area_id' => $areaVad?->id,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            [
                'cpf' => 'SAM002',
                'role_id' => $roleModels['SAM']->id,
                'area_id' => $areaSur?->id,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ],
            // IMs (Installation Manager - Vadodara, Surat, Ankleshwar)
            [
                'cpf' => 'IM001',
                'role_id' => $roleModels['IM']->id,
                'area_id' => null,
                'installation_id' => $instVad1?->id,
                'assigned_by' => 'SAM001',
            ],
            [
                'cpf' => 'IM002',
                'role_id' => $roleModels['IM']->id,
                'area_id' => null,
                'installation_id' => $instSur1?->id,
                'assigned_by' => 'SAM002',
            ],
            [
                'cpf' => 'IM003',
                'role_id' => $roleModels['IM']->id,
                'area_id' => null,
                'installation_id' => $instAnk1?->id,
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
                'cpf' => 'HES002',
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
                'installation_id' => $instVad1?->id,
                'assigned_by' => 'IM001',
            ],
            [
                'cpf' => 'CE002',
                'role_id' => $roleModels['CE']->id,
                'area_id' => null,
                'installation_id' => $instSur1?->id,
                'assigned_by' => 'IM002',
            ],
            [
                'cpf' => 'CE003',
                'role_id' => $roleModels['CE']->id,
                'area_id' => null,
                'installation_id' => $instAnk1?->id,
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

        // Always ensure Super Admin has the Super Admin role assigned
        RoleAssignment::firstOrCreate(
            ['cpf' => 'ADMIN001'],
            [
                'role_id' => $roleModels['Super Admin']->id,
                'area_id' => null,
                'installation_id' => null,
                'assigned_by' => 'ADMIN001',
            ]
        );

        // Only populate initial sample role assignments on a fresh install (when table only has <= 1 assignment)
        // This ensures any manual assignments or role revocations made by an administrator are preserved.
        if (RoleAssignment::count() <= 1) {
            foreach ($assignments as $assignData) {
                if ($assignData['cpf'] === 'ADMIN001') {
                    continue;
                }
                RoleAssignment::firstOrCreate(
                    ['cpf' => $assignData['cpf']],
                    $assignData
                );
            }
        }
    }
}
