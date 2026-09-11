<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::create([
            'name' => 'Demo company',
            'slug' => Organization::uniqueSlug('Demo company'),
            'plan' => Organization::PLAN_TRIAL,
            'trial_ends_at' => now()->addDays(14)->toDateString(),
            'default_vat_rate' => 15,
            'zatca_environment' => 'sandbox',
            'status' => 'active',
        ]);

        app()->instance('current_organization_id', $organization->id);

        User::create([
            'organization_id' => $organization->id,
            'email' => 'admin@example.com',
            'password' => 'abc123',
            'name' => 'Admin',
            'show_password' => 'abc123',
            'user_type' => User::TYPE_ADMIN,
            'is_active' => true,
        ]);

        app(OrganizationProvisioner::class)->provision($organization);
    }
}
