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
     * - 1 Super Admin
     * - 1 SM (Site Manager)
     * - 2 SAM (Senior Assistant Manager)
     * - 3 IM (Indent Manager)
     * - 1 HES (Head of Engineering Services)
     * - 1 LM Works (Line Manager Works)
     * - 1 ICO (Indent Control Officer)
     * - 3 CE (Civil Engineer)
     * - 5 Normal Users
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

            // 1 SM (Site Manager)
            [
                'cpf' => 'SM001',
                'name' => 'Vikram Sharma',
                'designation' => 'SM',
                'location' => 'Site Alpha',
                'email' => 'sm1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 2 SAM (Senior Assistant Manager)
            [
                'cpf' => 'SAM001',
                'name' => 'Anita Desai',
                'designation' => 'SAM',
                'location' => 'Site Alpha',
                'email' => 'sam1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'SAM002',
                'name' => 'Rajesh Verma',
                'designation' => 'SAM',
                'location' => 'Site Beta',
                'email' => 'sam2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 3 IM (Indent Manager)
            [
                'cpf' => 'IM001',
                'name' => 'Pooja Nair',
                'designation' => 'IM',
                'location' => 'Site Alpha',
                'email' => 'im1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'IM002',
                'name' => 'Kiran Rao',
                'designation' => 'IM',
                'location' => 'Site Beta',
                'email' => 'im2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'IM003',
                'name' => 'Sunil Gupta',
                'designation' => 'IM',
                'location' => 'Site Gamma',
                'email' => 'im3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 HES (Head of Engineering Services)
            [
                'cpf' => 'HES001',
                'name' => 'Dr. Arindam Bose',
                'designation' => 'HES',
                'location' => 'Headquarters',
                'email' => 'hes@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 LM Works (Line Manager Works)
            [
                'cpf' => 'LMW001',
                'name' => 'Manoj Kulkarni',
                'designation' => 'LM Works',
                'location' => 'Site Alpha',
                'email' => 'lmworks@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 1 ICO (Indent Control Officer)
            [
                'cpf' => 'ICO001',
                'name' => 'Meera Krishnan',
                'designation' => 'ICO',
                'location' => 'Headquarters',
                'email' => 'ico@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 3 CE (Civil Engineer)
            [
                'cpf' => 'CE001',
                'name' => 'Deepak Patel',
                'designation' => 'CE',
                'location' => 'Site Alpha',
                'email' => 'ce1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'CE002',
                'name' => 'Sneha Roy',
                'designation' => 'CE',
                'location' => 'Site Beta',
                'email' => 'ce2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'CE003',
                'name' => 'Tariq Ansari',
                'designation' => 'CE',
                'location' => 'Site Gamma',
                'email' => 'ce3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],

            // 5 Normal Users
            [
                'cpf' => 'USER001',
                'name' => 'Rohan Mehta',
                'designation' => 'User',
                'location' => 'Site Alpha',
                'email' => 'user1@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'USER002',
                'name' => 'Kavita Joshi',
                'designation' => 'User',
                'location' => 'Site Beta',
                'email' => 'user2@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'USER003',
                'name' => 'Arun Pandey',
                'designation' => 'User',
                'location' => 'Site Gamma',
                'email' => 'user3@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => false,
            ],
            [
                'cpf' => 'USER004',
                'name' => 'Divya Pillai',
                'designation' => 'User',
                'location' => 'Regional Office',
                'email' => 'user4@civilportal.local',
                'password' => $defaultHashedPassword,
                'active' => true,
            ],
            [
                'cpf' => 'USER005',
                'name' => 'Sanjay Chawla',
                'designation' => 'User',
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
