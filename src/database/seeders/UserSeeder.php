<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeded users with Gujarat asset locations:
     * - 1 Super Admin (Gandhinagar HQ)
     * - 2 SM (Surface Managers - Vadodara & Surat)
     * - 2 SAM (Surface Area Managers - Vadodara & Surat)
     * - 3 IM (Installation Managers - Vadodara, Surat, Ankleshwar)
     * - 2 HES (Head Eng. Services - Gandhinagar HQ & Surat)
     * - 1 LM Works (Civil Head - Vadodara)
     * - 1 ICO (Incharge Civil Ops - Gandhinagar HQ)
     * - 3 CE (Civil Engineers - Vadodara, Surat, Ankleshwar)
     * - 5 Users (Normal users)
     * Total: 20 users (Idempotent using updateOrCreate on 'cpf')
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
                'location' => 'Gandhinagar HQ',
                'email' => 'superadmin@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 2 SM (Surface Manager)
            [
                'cpf' => 'SM001',
                'name' => 'Vikram Sharma',
                'designation' => 'Surface Manager (SM)',
                'location' => 'Vadodara',
                'email' => 'sm1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'SM002',
                'name' => 'Rohit Sharma',
                'designation' => 'Surface Manager (SM)',
                'location' => 'Surat',
                'email' => 'sm2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 2 SAM (Surface Area Manager)
            [
                'cpf' => 'SAM001',
                'name' => 'Anita Desai',
                'designation' => 'Surface Area Manager (SAM)',
                'location' => 'Vadodara',
                'email' => 'sam1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'SAM002',
                'name' => 'Rajesh Verma',
                'designation' => 'Surface Area Manager (SAM)',
                'location' => 'Surat',
                'email' => 'sam2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 3 IM (Installation Manager)
            [
                'cpf' => 'IM001',
                'name' => 'Pooja Nair',
                'designation' => 'Installation Manager (IM)',
                'location' => 'Vadodara',
                'email' => 'im1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'IM002',
                'name' => 'Kiran Rao',
                'designation' => 'Installation Manager (IM)',
                'location' => 'Surat',
                'email' => 'im2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'IM003',
                'name' => 'Sunil Gupta',
                'designation' => 'Installation Manager (IM)',
                'location' => 'Ankleshwar',
                'email' => 'im3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 2 HES (Head Eng. Services)
            [
                'cpf' => 'HES001',
                'name' => 'Dr. Arindam Bose',
                'designation' => 'Head Eng. Services (HES)',
                'location' => 'Gandhinagar HQ',
                'email' => 'hes@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'HES002',
                'name' => 'Dr. Subodh Goel',
                'designation' => 'Head Eng. Services (HES)',
                'location' => 'Surat',
                'email' => 'hes2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 LM Works (Civil Head)
            [
                'cpf' => 'LMW001',
                'name' => 'Manoj Kulkarni',
                'designation' => 'Civil Head (LM Works)',
                'location' => 'Vadodara',
                'email' => 'lmworks@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 ICO (Incharge Civil Ops)
            [
                'cpf' => 'ICO001',
                'name' => 'Meera Krishnan',
                'designation' => 'Incharge Civil Ops (ICO)',
                'location' => 'Gandhinagar HQ',
                'email' => 'ico@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 3 CE (Civil Engineer)
            [
                'cpf' => 'CE001',
                'name' => 'Deepak Patel',
                'designation' => 'Civil Engineer (CE)',
                'location' => 'Vadodara',
                'email' => 'ce1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'CE002',
                'name' => 'Sneha Roy',
                'designation' => 'Civil Engineer (CE)',
                'location' => 'Surat',
                'email' => 'ce2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'CE003',
                'name' => 'Tariq Ansari',
                'designation' => 'Civil Engineer (CE)',
                'location' => 'Ankleshwar',
                'email' => 'ce3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 5 Normal Users
            [
                'cpf' => 'USER001',
                'name' => 'Rohan Mehta',
                'designation' => 'Normal User',
                'location' => 'Vadodara',
                'email' => 'user1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'USER002',
                'name' => 'Kavita Joshi',
                'designation' => 'Normal User',
                'location' => 'Surat',
                'email' => 'user2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'USER003',
                'name' => 'Arun Pandey',
                'designation' => 'Normal User',
                'location' => 'Ankleshwar',
                'email' => 'user3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'USER004',
                'name' => 'Divya Pillai',
                'designation' => 'Normal User',
                'location' => 'Ahmedabad (Regional Office)',
                'email' => 'user4@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'USER005',
                'name' => 'Sanjay Chawla',
                'designation' => 'Normal User',
                'location' => 'Gandhinagar HQ',
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
