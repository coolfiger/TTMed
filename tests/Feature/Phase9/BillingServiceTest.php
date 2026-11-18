<?php

namespace Tests\Feature\Phase9;

use App\Models\Tenant\CashierShift;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use App\Models\Tenant\Payment;
use App\Services\AuditService;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    private BillingService $svc;
    private int $patientId = 1;
    private int $branchId  = 1;
    private int $userId    = 1;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT, email TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT, last_name TEXT, patient_number TEXT,
                created_at DATETIME, updated_at DATETIME, deleted_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER,
                code TEXT UNIQUE,
                name TEXT,
                description TEXT,
                price INTEGER DEFAULT 0,
                cost INTEGER DEFAULT 0,
                tax_rate DECIMAL(5,2) DEFAULT 0,
                taxable INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                is_default INTEGER DEFAULT 0,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS insurance_providers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT, code TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS invoice_number_sequences (
                date_key TEXT PRIMARY KEY,
                last_number INTEGER DEFAULT 0
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_number TEXT UNIQUE,
                patient_id INTEGER,
                branch_id INTEGER,
                encounter_id INTEGER,
                insurance_provider_id INTEGER,
                status TEXT DEFAULT "draft",
                subtotal INTEGER DEFAULT 0,
                tax_total INTEGER DEFAULT 0,
                discount INTEGER DEFAULT 0,
                total INTEGER DEFAULT 0,
                amount_paid INTEGER DEFAULT 0,
                amount_due INTEGER DEFAULT 0,
                insurance_portion INTEGER DEFAULT 0,
                patient_portion INTEGER DEFAULT 0,
                notes TEXT,
                created_by INTEGER,
                finalized_by INTEGER,
                finalized_at DATETIME,
                cancelled_by INTEGER,
                cancelled_at DATETIME,
                cancellation_reason TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS invoice_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER,
                service_id INTEGER,
                description TEXT,
                quantity INTEGER DEFAULT 1,
                unit_price INTEGER,
                tax_rate DECIMAL(5,2) DEFAULT 0,
                tax_amount INTEGER DEFAULT 0,
                discount INTEGER DEFAULT 0,
                line_total INTEGER,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS insurance_claims (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER,
                insurance_provider_id INTEGER,
                claim_reference TEXT,
                status TEXT DEFAULT "pending",
                claimed_amount INTEGER,
                approved_amount INTEGER DEFAULT 0,
                paid_amount INTEGER DEFAULT 0,
                submitted_date DATE,
                approved_date DATE,
                paid_date DATE,
                notes TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS cashier_shifts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                branch_id INTEGER,
                cashier_id INTEGER,
                status TEXT DEFAULT "open",
                opened_at DATETIME,
                closed_at DATETIME,
                approved_at DATETIME,
                approved_by INTEGER,
                opening_float INTEGER DEFAULT 0,
                closing_float INTEGER,
                expected_cash INTEGER DEFAULT 0,
                actual_cash INTEGER,
                variance INTEGER,
                notes TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payment_reference TEXT UNIQUE,
                invoice_id INTEGER,
                patient_id INTEGER,
                branch_id INTEGER,
                cashier_shift_id INTEGER,
                payment_method TEXT,
                amount INTEGER,
                reference_number TEXT,
                notes TEXT,
                collected_by INTEGER,
                voided INTEGER DEFAULT 0,
                voided_by INTEGER,
                voided_at DATETIME,
                void_reason TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS payment_reference_sequences (
                date_key TEXT PRIMARY KEY,
                last_number INTEGER DEFAULT 0
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

        DB::connection('tenant')->table('patients')->insert([
            'id' => $this->patientId, 'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $audit     = $this->createMock(AuditService::class);
        $audit->method('logTenant')->willReturnCallback(fn () => null);

        $this->svc = new BillingService($audit);
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS payment_reference_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS payments');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS cashier_shifts');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS insurance_claims');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoice_items');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoices');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoice_number_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS insurance_providers');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS services');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patients');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS users');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS audit_logs');
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeInvoice(): Invoice
    {
        return $this->svc->createInvoice(
            patientId: $this->patientId,
            branchId:  $this->branchId,
            createdBy: $this->userId,
        );
    }

    private function makeService(int $price = 5000, bool $taxable = false): int
    {
        return DB::connection('tenant')->table('services')->insertGetId([
            'code'       => 'SVC-' . uniqid(),
            'name'       => 'Consultation',
            'price'      => $price,
            'taxable'    => $taxable ? 1 : 0,
            'tax_rate'   => 12.50,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Invoice creation
    // -------------------------------------------------------------------------

    public function test_create_invoice_returns_draft(): void
    {
        $invoice = $this->makeInvoice();

        $this->assertSame('draft', $invoice->status);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
    }

    public function test_invoice_numbers_are_sequential(): void
    {
        $i1 = $this->makeInvoice();
        $i2 = $this->makeInvoice();

        $n1 = (int) substr($i1->invoice_number, -4);
        $n2 = (int) substr($i2->invoice_number, -4);
        $this->assertSame($n1 + 1, $n2);
    }

    // -------------------------------------------------------------------------
    // Line items
    // -------------------------------------------------------------------------

    public function test_add_item_updates_totals(): void
    {
        $invoice = $this->makeInvoice();

        $this->svc->addItem(
            invoice:        $invoice,
            serviceId:      null,
            description:    'Blood test',
            quantity:       1,
            unitPriceCents: 3000,
        );

        $invoice->refresh();
        $this->assertSame(3000, $invoice->subtotal);
        $this->assertSame(3000, $invoice->total);
        $this->assertSame(3000, $invoice->amount_due);
    }

    public function test_add_item_with_tax(): void
    {
        $invoice = $this->makeInvoice();

        $this->svc->addItem(
            invoice:        $invoice,
            serviceId:      null,
            description:    'Consultation',
            quantity:       1,
            unitPriceCents: 10000,
            taxRate:        12.5,
        );

        $invoice->refresh();
        $this->assertSame(10000, $invoice->subtotal);
        $this->assertSame(1250, $invoice->tax_total);
        $this->assertSame(11250, $invoice->total);
    }

    public function test_cannot_add_item_to_non_draft(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 1000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $this->expectException(\LogicException::class);
        $this->svc->addItem($invoice, null, 'Extra', 1, 500);
    }

    public function test_remove_item_updates_totals(): void
    {
        $invoice = $this->makeInvoice();
        $item    = $this->svc->addItem($invoice, null, 'Item', 1, 2000);
        $invoice->refresh();

        $this->svc->removeItem($invoice, $item->id);
        $invoice->refresh();

        $this->assertSame(0, $invoice->subtotal);
        $this->assertSame(0, $invoice->total);
    }

    public function test_cannot_remove_item_from_non_draft(): void
    {
        $invoice = $this->makeInvoice();
        $item    = $this->svc->addItem($invoice, null, 'Item', 1, 1000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $this->expectException(\LogicException::class);
        $this->svc->removeItem($invoice, $item->id);
    }

    // -------------------------------------------------------------------------
    // Finalization
    // -------------------------------------------------------------------------

    public function test_cannot_finalize_empty_invoice(): void
    {
        $invoice = $this->makeInvoice();

        $this->expectException(\LogicException::class);
        $this->svc->finalizeInvoice($invoice, $this->userId);
    }

    public function test_finalize_sets_status_and_timestamp(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 5000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $this->assertTrue($invoice->isFinalized());
        $this->assertNotNull($invoice->finalized_at);
        $this->assertSame($this->userId, $invoice->finalized_by);
    }

    // -------------------------------------------------------------------------
    // Payments
    // -------------------------------------------------------------------------

    public function test_collect_payment_reduces_amount_due(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Consultation', 1, 10000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $payment = $this->svc->collectPayment(
            invoice:     $invoice,
            amountCents: 5000,
            method:      'cash',
            collectedBy: $this->userId,
        );

        $invoice->refresh();
        $this->assertSame(5000, $invoice->amount_paid);
        $this->assertSame(5000, $invoice->amount_due);
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertStringStartsWith('PAY-', $payment->payment_reference);
    }

    public function test_full_payment_marks_invoice_paid(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Consultation', 1, 10000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $this->svc->collectPayment($invoice, 10000, 'cash', $this->userId);
        $invoice->refresh();

        $this->assertTrue($invoice->isPaid());
        $this->assertSame(0, $invoice->amount_due);
    }

    public function test_cannot_overpay(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 5000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $this->expectException(\InvalidArgumentException::class);
        $this->svc->collectPayment($invoice, 9999, 'cash', $this->userId);
    }

    public function test_cannot_pay_draft_invoice(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 5000);

        $this->expectException(\LogicException::class);
        $this->svc->collectPayment($invoice, 5000, 'cash', $this->userId);
    }

    public function test_payment_references_are_sequential(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 2, 5000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $p1 = $this->svc->collectPayment($invoice, 3000, 'cash', $this->userId);
        $p2 = $this->svc->collectPayment($invoice, 7000, 'cash', $this->userId);

        $n1 = (int) substr($p1->payment_reference, -4);
        $n2 = (int) substr($p2->payment_reference, -4);
        $this->assertSame($n1 + 1, $n2);
    }

    // -------------------------------------------------------------------------
    // Void payment
    // -------------------------------------------------------------------------

    public function test_void_payment_reinstates_amount_due(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 10000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $payment = $this->svc->collectPayment($invoice, 10000, 'cash', $this->userId);
        $invoice->refresh();
        $this->assertTrue($invoice->isPaid());

        $this->svc->voidPayment($payment, $this->userId, 'Error');
        $invoice->refresh();

        $this->assertSame('finalized', $invoice->status);
        $this->assertSame(10000, $invoice->amount_due);
        $this->assertTrue($payment->fresh()->isVoided());
    }

    public function test_cannot_void_already_voided_payment(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 5000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();

        $payment = $this->svc->collectPayment($invoice, 5000, 'cash', $this->userId);
        $this->svc->voidPayment($payment, $this->userId, 'Once');

        $this->expectException(\LogicException::class);
        $this->svc->voidPayment($payment->fresh(), $this->userId, 'Again');
    }

    // -------------------------------------------------------------------------
    // Cashier shifts
    // -------------------------------------------------------------------------

    public function test_open_shift(): void
    {
        $shift = $this->svc->openShift($this->branchId, $this->userId, 50000);

        $this->assertTrue($shift->isOpen());
        $this->assertSame(50000, $shift->opening_float);
    }

    public function test_cannot_open_duplicate_shift(): void
    {
        $this->svc->openShift($this->branchId, $this->userId, 0);

        $this->expectException(\LogicException::class);
        $this->svc->openShift($this->branchId, $this->userId, 0);
    }

    public function test_close_shift_calculates_expected_cash(): void
    {
        $shift = $this->svc->openShift($this->branchId, $this->userId, 50000);

        // Record a cash payment against this shift
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 10000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();
        $this->svc->collectPayment($invoice, 10000, 'cash', $this->userId, $shift->id);

        $this->svc->closeShift($shift, 60000);
        $shift->refresh();

        $this->assertTrue($shift->isClosed());
        $this->assertSame(60000, $shift->expected_cash); // 50000 float + 10000 cash
        $this->assertSame(0, $shift->variance);
    }

    public function test_close_shift_records_variance(): void
    {
        $shift = $this->svc->openShift($this->branchId, $this->userId, 50000);
        $this->svc->closeShift($shift, 48000); // $20 short
        $shift->refresh();

        $this->assertSame(-2000, $shift->variance);
    }

    public function test_approve_shift(): void
    {
        $shift = $this->svc->openShift($this->branchId, $this->userId, 0);
        $this->svc->closeShift($shift, 0);
        $shift->refresh();

        $this->svc->approveShift($shift, $this->userId);
        $shift->refresh();

        $this->assertTrue($shift->isApproved());
    }

    public function test_cannot_approve_open_shift(): void
    {
        $shift = $this->svc->openShift($this->branchId, $this->userId, 0);

        $this->expectException(\LogicException::class);
        $this->svc->approveShift($shift, $this->userId);
    }

    // -------------------------------------------------------------------------
    // Cancel invoice
    // -------------------------------------------------------------------------

    public function test_cancel_draft_invoice(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->cancelInvoice($invoice, $this->userId, 'Duplicate entry.');
        $invoice->refresh();

        $this->assertTrue($invoice->isCancelled());
        $this->assertSame('Duplicate entry.', $invoice->cancellation_reason);
    }

    public function test_cannot_cancel_invoice_with_payments(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 5000);
        $this->svc->finalizeInvoice($invoice, $this->userId);
        $invoice->refresh();
        $this->svc->collectPayment($invoice, 5000, 'cash', $this->userId);
        $invoice->refresh();

        $this->expectException(\LogicException::class);
        $this->svc->cancelInvoice($invoice, $this->userId, 'Reason');
    }

    // -------------------------------------------------------------------------
    // Discount
    // -------------------------------------------------------------------------

    public function test_apply_discount_reduces_total(): void
    {
        $invoice = $this->makeInvoice();
        $this->svc->addItem($invoice, null, 'Item', 1, 10000);
        $invoice->refresh();

        $this->svc->applyDiscount($invoice, 2000);
        $invoice->refresh();

        $this->assertSame(2000, $invoice->discount);
        $this->assertSame(8000, $invoice->total);
        $this->assertSame(8000, $invoice->amount_due);
    }
}
