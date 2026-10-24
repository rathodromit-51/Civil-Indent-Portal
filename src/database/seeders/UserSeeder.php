<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeded users:
     * - 1 Super Admin (Super Administrator)
     * - 1 SM (Surface Manager)
     * - 2 SAM (Surface Area Manager)
     * - 3 IM (Installation Manager)
     * - 1 HES (Head Eng. Services)
     * - 1 LM Works (Civil Head)
     * - 1 ICO (Incharge Civil Ops)
     * - 3 CE (Civil Engineer)
     * - 5 Users (Normal user)
     * Total: 18 users (Idempotent using updateOrCreate on 'cpf')
     */
    public function run(): void
    {
        $defaultHashedPassword = Hash::make(config('auth.dev_password', 'secret123'));

        $users = [
            // 1 Super Admin
            [
                'cpf' => 'ADMIN001',
                'name' => 'Super Administrator',
                'designation' => 'Super Admin',
                'location' => 'Headquarters',
                'email' => 'superadmin@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 SM (Surface Manager)
            [
                'cpf' => 'SM001',
                'name' => 'Vikram Sharma',
                'designation' => 'Surface Manager (SM)',
                'location' => 'Site Alpha',
                'email' => 'sm1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 2 SAM (Surface Area Manager)
            [
                'cpf' => 'SAM001',
                'name' => 'Anita Desai',
                'designation' => 'Surface Area Manager (SAM)',
                'location' => 'Site Alpha',
                'email' => 'sam1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'SAM002',
                'name' => 'Rajesh Verma',
                'designation' => 'Surface Area Manager (SAM)',
                'location' => 'Site Beta',
                'email' => 'sam2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 3 IM (Installation Manager)
            [
                'cpf' => 'IM001',
                'name' => 'Pooja Nair',
                'designation' => 'Installation Manager (IM)',
                'location' => 'Site Alpha',
                'email' => 'im1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'IM002',
                'name' => 'Kiran Rao',
                'designation' => 'Installation Manager (IM)',
                'location' => 'Site Beta',
                'email' => 'im2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'IM003',
                'name' => 'Sunil Gupta',
                'designation' => 'Installation Manager (IM)',
                'location' => 'Site Gamma',
                'email' => 'im3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 HES (Head Eng. Services)
            [
                'cpf' => 'HES001',
                'name' => 'Dr. Arindam Bose',
                'designation' => 'Head Eng. Services (HES)',
                'location' => 'Headquarters',
                'email' => 'hes@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 LM Works (Civil Head)
            [
                'cpf' => 'LMW001',
                'name' => 'Manoj Kulkarni',
                'designation' => 'Civil Head (LM Works)',
                'location' => 'Site Alpha',
                'email' => 'lmworks@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 ICO (Incharge Civil Ops)
            [
                'cpf' => 'ICO001',
                'name' => 'Meera Krishnan',
                'designation' => 'Incharge Civil Ops (ICO)',
                'location' => 'Headquarters',
                'email' => 'ico@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 3 CE (Civil Engineer)
            [
                'cpf' => 'CE001',
                'name' => 'Deepak Patel',
                'designation' => 'Civil Engineer (CE)',
                'location' => 'Site Alpha',
                'email' => 'ce1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'CE002',
                'name' => 'Sneha Roy',
                'designation' => 'Civil Engineer (CE)',
                'location' => 'Site Beta',
                'email' => 'ce2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'CE003',
                'name' => 'Tariq Ansari',
                'designation' => 'Civil Engineer (CE)',
                'location' => 'Site Gamma',
                'email' => 'ce3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 5 Normal Users
            [
                'cpf' => 'USER001',
                'name' => 'Rohan Mehta',
                'designation' => 'Normal User',
                'location' => 'Site Alpha',
                'email' => 'user1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'USER002',
                'name' => 'Kavita Joshi',
                'designation' => 'Normal User',
                'location' => 'Site Beta',
                'email' => 'user2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'USER003',
                'name' => 'Arun Pandey',
                'designation' => 'Normal User',
                'location' => 'Site Gamma',
                'email' => 'user3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'USER004',
                'name' => 'Divya Pillai',
                'designation' => 'Normal User',
                'location' => 'Regional Office',
                'email' => 'user4@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'USER005',
                'name' => 'Sanjay Chawla',
                'designation' => 'Normal User',
                'location' => 'Headquarters',
                'email' => 'user5@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['cpf' => $userData['cpf']],
                $userData
            );
        }
    }
}
