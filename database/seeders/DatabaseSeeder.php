<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect([
            ['name' => 'Owner', 'slug' => 'owner'],
            ['name' => 'Project Manager', 'slug' => 'project-manager'],
            ['name' => 'Member', 'slug' => 'member'],
        ])->mapWithKeys(function (array $role): array {
            return [
                $role['slug'] => Role::create($role),
            ];
        });

        $users = collect([
            [
                'name' => 'Gabriel Teste',
                'email' => 'teste@teste.com',
                'password' => Hash::make('teste123'),
            ],
            [
                'name' => 'Beatriz Manager',
                'email' => 'manager@teste.com',
                'password' => Hash::make('teste123'),
            ],
            [
                'name' => 'Lucas Member',
                'email' => 'member@teste.com',
                'password' => Hash::make('teste123'),
            ],
        ])->mapWithKeys(function (array $user): array {
            $createdUser = User::create($user);

            return [
                $createdUser->email => $createdUser,
            ];
        });

        $eventide = Organization::create([
            'name' => 'Eventide',
            'slug' => 'eventide',
            'owner_id' => $users['teste@teste.com']->id,
            'active' => true,
        ]);

        $sandbox = Organization::create([
            'name' => 'Sandbox Labs',
            'slug' => 'sandbox-labs',
            'owner_id' => $users['manager@teste.com']->id,
            'active' => true,
        ]);

        $eventideOwnerMembershipId = $this->createMembership(
            user: $users['teste@teste.com'],
            organization: $eventide,
            role: $roles['owner'],
        );

        $eventideManagerMembershipId = $this->createMembership(
            user: $users['manager@teste.com'],
            organization: $eventide,
            role: $roles['project-manager'],
        );

        $this->createMembership(
            user: $users['member@teste.com'],
            organization: $eventide,
            role: $roles['member'],
        );

        $sandboxManagerMembershipId = $this->createMembership(
            user: $users['manager@teste.com'],
            organization: $sandbox,
            role: $roles['owner'],
        );

        $paymentsApi = Project::create([
            'name' => 'Payments API',
            'slug' => 'payments-api',
            'description' => 'API de pagamentos para integrações externas.',
            'active' => true,
            'check_status_url' => '/health',
            'base_url' => 'https://payments.eventide.test/api/v1',
            'organization_id' => $eventide->id,
            'project_manager_id' => $eventideManagerMembershipId,
        ]);

        $statusPortal = Project::create([
            'name' => 'Status Portal',
            'slug' => 'status-portal',
            'description' => 'Portal público para acompanhar disponibilidade dos serviços.',
            'active' => true,
            'check_status_url' => '/status',
            'base_url' => 'https://status.eventide.test',
            'organization_id' => $eventide->id,
            'project_manager_id' => $eventideOwnerMembershipId,
        ]);

        $sandboxProject = Project::create([
            'name' => 'Internal Tools',
            'slug' => 'internal-tools',
            'description' => 'Ferramentas internas para validação de fluxos.',
            'active' => true,
            'check_status_url' => '/health',
            'base_url' => 'https://sandbox.eventide.test/tools',
            'organization_id' => $sandbox->id,
            'project_manager_id' => $sandboxManagerMembershipId,
        ]);

        Activity::create([
            'organization_id' => $eventide->id,
            'project_id' => $paymentsApi->id,
            'user_id' => $users['teste@teste.com']->id,
            'type' => 'project.created',
            'subject_type' => Project::class,
            'subject_id' => $paymentsApi->id,
            'description' => 'Projeto Payments API criado.',
            'metadata' => [
                'project' => $paymentsApi->slug,
            ],
        ]);

        Activity::create([
            'organization_id' => $eventide->id,
            'project_id' => $statusPortal->id,
            'user_id' => $users['manager@teste.com']->id,
            'type' => 'manager.assigned',
            'subject_type' => Project::class,
            'subject_id' => $statusPortal->id,
            'description' => 'Gerente atribuido ao projeto Status Portal.',
            'metadata' => [
                'manager_membership_id' => $eventideOwnerMembershipId,
            ],
        ]);

        Activity::create([
            'organization_id' => $sandbox->id,
            'project_id' => $sandboxProject->id,
            'user_id' => $users['manager@teste.com']->id,
            'type' => 'project.created',
            'subject_type' => Project::class,
            'subject_id' => $sandboxProject->id,
            'description' => 'Projeto Internal Tools criado.',
            'metadata' => [
                'project' => $sandboxProject->slug,
            ],
        ]);
    }

    private function createMembership(User $user, Organization $organization, Role $role): int
    {
        return DB::table('user_organization')->insertGetId([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
