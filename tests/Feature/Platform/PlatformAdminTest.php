<?php

namespace Tests\Feature\Platform;

use App\Models\Platform\PlatformUser;
use App\Models\Platform\SubscriptionPlan;
use App\Models\Platform\Subscription;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    public function test_platform_user_is_super_admin(): void
    {
        $user = new PlatformUser(['role' => 'super_admin']);

        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_platform_user_admin_is_not_super_admin(): void
    {
        $user = new PlatformUser(['role' => 'admin']);

        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_platform_user_support_is_not_super_admin(): void
    {
        $user = new PlatformUser(['role' => 'support']);

        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_subscription_plan_has_feature(): void
    {
        $plan = new SubscriptionPlan(['features' => ['patients', 'queue', 'billing']]);

        $this->assertTrue($plan->hasFeature('patients'));
        $this->assertTrue($plan->hasFeature('queue'));
        $this->assertFalse($plan->hasFeature('lab'));
    }

    public function test_subscription_plan_has_feature_handles_null(): void
    {
        $plan = new SubscriptionPlan(['features' => null]);

        $this->assertFalse($plan->hasFeature('patients'));
    }

    public function test_subscription_plan_has_feature_handles_empty_array(): void
    {
        $plan = new SubscriptionPlan(['features' => []]);

        $this->assertFalse($plan->hasFeature('patients'));
    }

    public function test_active_subscription_is_active(): void
    {
        $sub = new Subscription([
            'status'     => 'active',
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertTrue($sub->isActive());
    }

    public function test_expired_subscription_is_not_active(): void
    {
        $sub = new Subscription([
            'status'     => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($sub->isActive());
    }

    public function test_cancelled_subscription_is_not_active(): void
    {
        $sub = new Subscription([
            'status'     => 'cancelled',
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertFalse($sub->isActive());
    }
}
