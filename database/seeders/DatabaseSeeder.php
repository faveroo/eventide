<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $roles = Role::query()
            ->whereIn('slug', ['owner', 'project-manager', 'member'])
            ->get()
            ->keyBy('slug');

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
            $createdUser = User::query()->updateOrCreate(
                ['email' => $user['email']],
                $user,
            );

            return [
                $createdUser->email => $createdUser,
            ];
        });

        $eventide = Organization::query()->updateOrCreate(
            ['slug' => 'eventide'],
            [
                'name' => 'Eventide',
                'owner_id' => $users['teste@teste.com']->id,
                'active' => true,
            ],
        );

        $sandbox = Organization::query()->updateOrCreate(
            ['slug' => 'sandbox-labs'],
            [
                'name' => 'Sandbox Labs',
                'owner_id' => $users['manager@teste.com']->id,
                'active' => true,
            ],
        );

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

        $paymentsApi = Project::query()->updateOrCreate(
            ['slug' => 'payments-api'],
            [
                'name' => 'Payments API',
                'description' => 'API de pagamentos para integrações externas.',
                'active' => true,
                'check_status_url' => '/health',
                'base_url' => 'https://payments.eventide.test/api/v1',
                'organization_id' => $eventide->id,
                'project_manager_id' => $eventideManagerMembershipId,
            ],
        );

        $statusPortal = Project::query()->updateOrCreate(
            ['slug' => 'status-portal'],
            [
                'name' => 'Status Portal',
                'description' => 'Portal público para acompanhar disponibilidade dos serviços.',
                'active' => true,
                'check_status_url' => '/status',
                'base_url' => 'https://status.eventide.test',
                'organization_id' => $eventide->id,
                'project_manager_id' => $eventideOwnerMembershipId,
            ],
        );

        $sandboxProject = Project::query()->updateOrCreate(
            ['slug' => 'internal-tools'],
            [
                'name' => 'Internal Tools',
                'description' => 'Ferramentas internas para validação de fluxos.',
                'active' => true,
                'check_status_url' => '/health',
                'base_url' => 'https://sandbox.eventide.test/tools',
                'organization_id' => $sandbox->id,
                'project_manager_id' => $sandboxManagerMembershipId,
            ],
        );

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
        $membership = UserOrganization::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
            ],
            [
                'role_id' => $role->id,
            ],
        );

        $membership->syncRoles($role);

        return $membership->id;
    }
}
