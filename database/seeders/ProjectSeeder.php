<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Project::create([
            'name' => 'Projeto Teste',
            'slug' => 'projeto-teste',
            'description' => 'api de pagamentos',
            'active' => true,
            'check_status_url' => '/health',
            'base_url' => 'https://teste.tech/api/v1',
            'organization_id' => 1,
        ]);
    }
}
