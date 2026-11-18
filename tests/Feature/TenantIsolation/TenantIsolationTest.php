<?php

namespace Tests\Feature\TenantIsolation;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantContext;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_tenant_context_resolves_singleton(): void
    {
        $ctx1 = app(TenantContext::class);
        $ctx2 = app(TenantContext::class);

        $this->assertSame($ctx1, $ctx2);
    }

    public function test_tenant_context_is_unresolved_by_default(): void
    {
        $ctx = app(TenantContext::class);

        $this->assertFalse($ctx->resolved());
        $this->assertNull($ctx->get());
    }

    public function test_tenant_context_resolves_correctly(): void
    {
        $tenant = new Tenant(['code' => 'testclinic', 'name' => 'Test Clinic', 'status' => 'active']);
        $tenant->forceFill(['id' => 1, 'uuid' => 'test-uuid-1234']);

        $ctx = app(TenantContext::class);
        $ctx->set($tenant);

        $this->assertTrue($ctx->resolved());
        $this->assertSame($tenant, $ctx->get());
        $this->assertEquals(1, $ctx->id());
        $this->assertEquals('test-uuid-1234', $ctx->uuid());
    }

    public function test_tenant_storage_path_includes_uuid(): void
    {
        $uuid = 'abc-123';
        $tenant = new Tenant();
        $tenant->forceFill(['uuid' => $uuid]);

        $ctx = app(TenantContext::class);
        $ctx->set($tenant);

        // storagePath() uses config('tenancy.storage_root') + '/' + uuid
        $this->assertStringContainsString($uuid, $ctx->storagePath());
    }

    public function test_tenant_storage_path_appends_subpath(): void
    {
        $uuid = 'abc-999';
        $tenant = new Tenant();
        $tenant->forceFill(['uuid' => $uuid]);

        $ctx = app(TenantContext::class);
        $ctx->set($tenant);

        $full = $ctx->storagePath('prescriptions/2024');
        $this->assertStringContainsString($uuid, $full);
        $this->assertStringContainsString('prescriptions/2024', $full);
    }

    public function test_tenant_context_uuid_method_returns_null_when_unresolved(): void
    {
        $ctx = app(TenantContext::class);

        $this->assertNull($ctx->uuid());
    }

    public function test_tenant_context_id_method_returns_null_when_unresolved(): void
    {
        $ctx = app(TenantContext::class);

        $this->assertNull($ctx->id());
    }
}
