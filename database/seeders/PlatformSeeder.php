<?php

namespace Database\Seeders;

use App\Models\Platform\PlatformUser;
use App\Models\Platform\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPlans();
        $this->seedSuperAdmin();
    }

    private function seedPlans(): void
    {
        $plans = [
            [
                'uuid'             => Str::uuid(),
                'name'             => 'Starter',
                'slug'             => 'starter',
                'description'      => 'Up to 5 users. Ideal for a solo or small practice.',
                'user_limit'       => 5,
                'storage_limit_gb' => 10,
                'monthly_price'    => 149900,
                'annual_price'     => 1499000,
                'features'         => json_encode(['patients', 'queue', 'encounters', 'prescriptions', 'billing', 'appointments']),
                'is_active'        => true,
            ],
            [
                'uuid'             => Str::uuid(),
                'name'             => 'Professional',
                'slug'             => 'professional',
                'description'      => 'Up to 25 users. Full feature set for established practices.',
                'user_limit'       => 25,
                'storage_limit_gb' => 50,
                'monthly_price'    => 349900,
                'annual_price'     => 3499000,
                'features'         => json_encode(['patients', 'queue', 'encounters', 'prescriptions', 'billing', 'appointments', 'lab', 'inventory', 'reports', 'documents', 'referrals']),
                'is_active'        => true,
            ],
            [
                'uuid'             => Str::uuid(),
                'name'             => 'Enterprise',
                'slug'             => 'enterprise',
                'description'      => 'Unlimited users. Multi-branch, full audit trail, priority support.',
                'user_limit'       => 999,
                'storage_limit_gb' => 200,
                'monthly_price'    => 749900,
                'annual_price'     => 7499000,
                'features'         => json_encode(['patients', 'queue', 'encounters', 'prescriptions', 'billing', 'appointments', 'lab', 'inventory', 'reports', 'documents', 'referrals', 'multi_branch', 'api_access']),
                'is_active'        => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('Subscription plans seeded.');
    }

    private function seedSuperAdmin(): void
    {
        $email = env('PLATFORM_ADMIN_EMAIL', 'admin@ttmed.app');
        $password = env('PLATFORM_ADMIN_PASSWORD', 'changeme123!');

        if (PlatformUser::where('email', $email)->exists()) {
            $this->command->info("Platform admin already exists: {$email}");
            return;
        }

        PlatformUser::create([
            'uuid'        => Str::uuid(),
            'name'        => 'Platform Administrator',
            'email'       => $email,
            'password'    => Hash::make($password),
            'role'        => 'super_admin',
            'mfa_enabled' => false,
            'is_active'   => true,
        ]);

        $this->command->info("Platform super admin created: {$email}");
        $this->command->warn('Change the password immediately after first login.');
    }
}
