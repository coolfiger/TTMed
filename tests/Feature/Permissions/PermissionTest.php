<?php

namespace Tests\Feature\Permissions;

use App\Models\Tenant\Permission;
use App\Models\Tenant\Role;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    public function test_has_permission_returns_true_when_user_has_permission(): void
    {
        $permission = new Permission(['slug' => 'patients.view', 'name' => 'View Patients', 'module' => 'patients']);

        $role = $this->createMock(Role::class);
        $role->method('getAttribute')
            ->with('permissions')
            ->willReturn(new Collection([$permission]));

        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getAllPermissions'])
            ->getMock();
        $user->method('getAllPermissions')
            ->willReturn(new Collection([$permission]));

        $this->assertTrue($user->hasPermission('patients.view'));
    }

    public function test_has_permission_returns_false_when_user_lacks_permission(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getAllPermissions'])
            ->getMock();
        $user->method('getAllPermissions')
            ->willReturn(new Collection([]));

        $this->assertFalse($user->hasPermission('patients.delete'));
    }

    public function test_has_any_permission_returns_true_on_first_match(): void
    {
        $permission = new Permission(['slug' => 'billing.view', 'name' => 'View Billing', 'module' => 'billing']);

        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getAllPermissions'])
            ->getMock();
        $user->method('getAllPermissions')
            ->willReturn(new Collection([$permission]));

        $this->assertTrue($user->hasAnyPermission(['patients.view', 'billing.view', 'reports.view']));
    }

    public function test_has_any_permission_returns_false_when_none_match(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getAllPermissions'])
            ->getMock();
        $user->method('getAllPermissions')
            ->willReturn(new Collection([]));

        $this->assertFalse($user->hasAnyPermission(['patients.delete', 'billing.refund']));
    }

    public function test_permission_slug_format_is_module_dot_action(): void
    {
        $slugs = [
            'patients.view', 'patients.create', 'patients.edit',
            'queue.view', 'billing.view', 'reports.operational',
        ];

        foreach ($slugs as $slug) {
            $this->assertMatchesRegularExpression('/^[a-z_]+\.[a-z_]+$/', $slug, "Slug {$slug} should follow module.action format");
        }
    }
}
