<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Development Admin',
            'profile_image' => '/image',
            'email' => 'dev@localhost.com',
            'password' => bcrypt('password'),
            'organization_id' => null,
            'branch_id' => null,
            'is_active' => true,
            'can_login' => true,
        ]);

        $this->command->info('Development admin user created!');
        $this->command->info('Email: dev@localhost.com');
        $this->command->info('Password: password');
    }
}
