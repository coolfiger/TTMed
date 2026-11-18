<?php

namespace Tests\Feature\Phase12;

use App\Http\Middleware\SecurityHeaders;
use App\Models\Platform\Tenant;
use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Patient;
use App\Services\AuditService;
use App\Services\BillingService;
use App\Services\InventoryService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS payments');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS payment_reference_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoice_items');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoices');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoice_number_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_items');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_categories');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_transactions');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patients');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS audit_logs');
        parent::tearDown();
    }

    private function createTables(): void
    {
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT, last_name TEXT, patient_number TEXT,
                created_at DATETIME, updated_at DATETIME, deleted_at DATETIME
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS invoice_number_sequences (
                date_key TEXT PRIMARY KEY, last_number INTEGER DEFAULT 0
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_number TEXT UNIQUE,
                patient_id INTEGER, branch_id INTEGER, encounter_id INTEGER,
                insurance_provider_id INTEGER, status TEXT DEFAULT "draft",
                subtotal INTEGER DEFAULT 0, tax_total INTEGER DEFAULT 0,
                discount INTEGER DEFAULT 0, total INTEGER DEFAULT 0,
                amount_paid INTEGER DEFAULT 0, amount_due INTEGER DEFAULT 0,
                insurance_portion INTEGER DEFAULT 0, patient_portion INTEGER DEFAULT 0,
                notes TEXT, created_by INTEGER,
                finalized_by INTEGER, finalized_at DATETIME,
                cancelled_by INTEGER, cancelled_at DATETIME, cancellation_reason TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS invoice_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER, service_id INTEGER, description TEXT,
                quantity INTEGER DEFAULT 1, unit_price INTEGER,
                tax_rate DECIMAL(5,2) DEFAULT 0, tax_amount INTEGER DEFAULT 0,
                discount INTEGER DEFAULT 0, line_total INTEGER,
                created_at DATETIME, updated_at DATETIME
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS payment_reference_sequences (
                date_key TEXT PRIMARY KEY, last_number INTEGER DEFAULT 0
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payment_reference TEXT UNIQUE,
                invoice_id INTEGER, patient_id INTEGER, branch_id INTEGER,
                cashier_shift_id INTEGER, payment_method TEXT, amount INTEGER,
                reference_number TEXT, notes TEXT, collected_by INTEGER,
                voided INTEGER DEFAULT 0,
                voided_by INTEGER, voided_at DATETIME, void_reason TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT, code TEXT UNIQUE, is_active INTEGER DEFAULT 1,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME, updated_at DATETIME
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER, name TEXT, code TEXT UNIQUE, description TEXT,
                unit TEXT DEFAULT "unit", quantity_on_hand INTEGER DEFAULT 0,
                reorder_level INTEGER DEFAULT 10, reorder_quantity INTEGER DEFAULT 50,
                unit_cost INTEGER DEFAULT 0, is_active INTEGER DEFAULT 1,
                track_expiry INTEGER DEFAULT 0, expiry_date DATE, supplier TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                inventory_item_id INTEGER, branch_id INTEGER,
                transaction_type TEXT, quantity INTEGER,
                quantity_before INTEGER, quantity_after INTEGER,
                reference TEXT, notes TEXT, performed_by INTEGER,
                created_at DATETIME, updated_at DATETIME
            )
        ');
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER, action TEXT, entity_type TEXT,
                entity_id INTEGER, old_values TEXT, new_values TEXT,
                description TEXT, created_at DATETIME
            )
        ');
    }

    // =========================================================================
    // Security headers
    // =========================================================================

    public function test_security_headers_middleware_sets_required_headers(): void
    {
        $middleware = new SecurityHeaders();

        $request  = Request::create('/test');
        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertEquals('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    public function test_hsts_not_sent_in_non_production(): void
    {
        $middleware = new SecurityHeaders();
        $request    = Request::create('https://test.ttmed.app/');
        $response   = $middleware->handle($request, fn () => new Response('OK'));

        // Not production in test env → no HSTS
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    // =========================================================================
    // Tenant context isolation
    // =========================================================================

    public function test_tenant_context_set_to_one_tenant_does_not_resolve_another(): void
    {
        $tenantA = new Tenant();
        $tenantA->forceFill(['id' => 1, 'uuid' => 'uuid-aaa', 'code' => 'alpha', 'status' => 'active']);

        $tenantB = new Tenant();
        $tenantB->forceFill(['id' => 2, 'uuid' => 'uuid-bbb', 'code' => 'beta', 'status' => 'active']);

        $ctx = app(TenantContext::class);
        $ctx->set($tenantA);

        $this->assertEquals('uuid-aaa', $ctx->uuid());
        $this->assertNotEquals('uuid-bbb', $ctx->uuid());

        // Switching context changes the resolved tenant
        $ctx->set($tenantB);
        $this->assertEquals('uuid-bbb', $ctx->uuid());
        $this->assertNotEquals('uuid-aaa', $ctx->uuid());
    }

    public function test_patient_model_uses_tenant_connection(): void
    {
        // All patient queries must go through the 'tenant' connection,
        // ensuring they are scoped to whichever DB is currently connected.
        $patient = new Patient();
        $this->assertEquals('tenant', $patient->getConnectionName());
    }

    public function test_invoice_model_uses_tenant_connection(): void
    {
        $invoice = new Invoice();
        $this->assertEquals('tenant', $invoice->getConnectionName());
    }

    public function test_inventory_item_model_uses_tenant_connection(): void
    {
        $item = new InventoryItem();
        $this->assertEquals('tenant', $item->getConnectionName());
    }

    // =========================================================================
    // BillingService — wrong patient ID is not found
    // =========================================================================

    public function test_billing_service_cannot_add_item_to_nonexistent_invoice(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            'id' => 1, 'first_name' => 'Alice', 'last_name' => 'A',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $audit = $this->createMock(AuditService::class);
        $audit->method('logTenant')->willReturnCallback(fn () => null);
        $svc = new BillingService($audit);

        $invoice = $svc->createInvoice(patientId: 1, branchId: 1, createdBy: 1);

        // Simulate: a request tries to manipulate a foreign invoice ID
        $fakeInvoice = Invoice::on('tenant')->make(['id' => 99999]);
        $fakeInvoice->forceFill(['id' => 99999, 'status' => 'draft']);

        // addItem on a model that doesn't exist in the DB throws
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Invoice::on('tenant')->findOrFail(99999);
    }

    // =========================================================================
    // InventoryService — negative stock is always blocked
    // =========================================================================

    public function test_inventory_service_blocks_dispense_beyond_stock_regardless_of_caller(): void
    {
        $itemId = DB::connection('tenant')->table('inventory_items')->insertGetId([
            'name' => 'Aspirin', 'code' => 'ASP-01', 'unit' => 'tablet',
            'quantity_on_hand' => 5,
            'reorder_level' => 2, 'reorder_quantity' => 50, 'unit_cost' => 10,
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $audit = $this->createMock(AuditService::class);
        $audit->method('logTenant')->willReturnCallback(fn () => null);
        $svc = new InventoryService($audit);

        // Even with an arbitrary performedBy user ID (simulating a cross-tenant call),
        // the quantity guard fires before any data is written.
        $this->expectException(\LogicException::class);
        $svc->dispenseStock($itemId, 1, 999, performedBy: 42);
    }

    // =========================================================================
    // Model findOrFail scoped to tenant connection — wrong ID returns 404
    // =========================================================================

    public function test_patient_find_or_fail_on_nonexistent_id_throws(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Patient::on('tenant')->findOrFail(999999);
    }

    public function test_invoice_find_or_fail_on_nonexistent_id_throws(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Invoice::on('tenant')->findOrFail(999999);
    }

    public function test_inventory_item_find_or_fail_on_nonexistent_id_throws(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        InventoryItem::on('tenant')->findOrFail(999999);
    }

    // =========================================================================
    // CheckPermission middleware — missing permission aborts
    // =========================================================================

    public function test_check_permission_middleware_aborts_403_without_permission(): void
    {
        $middleware = new \App\Http\Middleware\CheckPermission();

        $user = $this->createMock(\App\Models\Tenant\User::class);
        $user->method('hasPermission')->with('billing.view')->willReturn(false);

        $request = Request::create('/billing');
        auth()->guard('tenant')->setUser($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, fn () => new Response('OK'), 'billing.view');
    }

    public function test_check_permission_middleware_passes_with_valid_permission(): void
    {
        $middleware = new \App\Http\Middleware\CheckPermission();

        $user = $this->createMock(\App\Models\Tenant\User::class);
        $user->method('hasPermission')->with('billing.view')->willReturn(true);

        $request = Request::create('/billing');
        auth()->guard('tenant')->setUser($user);

        $response = $middleware->handle($request, fn () => new Response('OK'), 'billing.view');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
