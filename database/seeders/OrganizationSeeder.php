<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Gabriel Teste',
            'email' => 'teste@teste.com',
            'password' => 'teste123',
        ]);

        Role::create([
            'name' => 'Teste',
            'slug' => 'teste',
        ]);

        Organization::create([
            'name' => 'teste',
            'slug' => 'teste',
            'active' => true,
            'owner_id' => 1,
        ]);
    }
}
