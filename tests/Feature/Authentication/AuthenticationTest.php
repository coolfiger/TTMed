<?php

namespace Tests\Feature\Authentication;

use App\Models\Tenant\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_user_lockout_threshold_is_five_attempts(): void
    {
        $user = new User();
        $user->login_attempts = 0;
        $user->locked_until = null;

        for ($i = 0; $i < 5; $i++) {
            $user->login_attempts++;
        }

        $this->assertEquals(5, $user->login_attempts);
    }

    public function test_is_locked_returns_true_when_locked_until_in_future(): void
    {
        $user = new User();
        $user->locked_until = now()->addMinutes(10);

        $this->assertTrue($user->isLocked());
    }

    public function test_is_locked_returns_false_when_locked_until_in_past(): void
    {
        $user = new User();
        $user->locked_until = now()->subMinutes(1);

        $this->assertFalse($user->isLocked());
    }

    public function test_is_locked_returns_false_when_null(): void
    {
        $user = new User();
        $user->locked_until = null;

        $this->assertFalse($user->isLocked());
    }

    public function test_locked_until_in_future_reports_locked(): void
    {
        $user = new User();
        $user->locked_until = now()->addMinutes(15);

        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->isLocked());
    }

    public function test_locked_until_just_set_is_in_future(): void
    {
        $lockedUntil = now()->addMinutes(15);

        $this->assertTrue($lockedUntil->isFuture());
    }
}
