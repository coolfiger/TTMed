<?php

namespace Tests\Feature\Phase11;

use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    private ReportService $svc;
    private Carbon $from;
    private Carbon $to;

    protected function setUp(): void
    {
        parent::setUp();

        $this->svc  = new ReportService();
        $this->from = now()->subDays(30)->startOfDay();
        $this->to   = now()->endOfDay();

        $this->createTables();
        $this->seedBaseData();
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_transactions');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_items');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_categories');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS payments');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS payment_reference_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoice_items');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoices');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS invoice_number_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS appointments');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS encounters');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patients');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS staff');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS branches');
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Table setup
    // -------------------------------------------------------------------------

    private function createTables(): void
    {
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS branches (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                is_active INTEGER DEFAULT 1,
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
            CREATE TABLE IF NOT EXISTS staff (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT, last_name TEXT, role TEXT,
                created_at DATETIME, updated_at DATETIME, deleted_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS encounters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER,
                branch_id INTEGER,
                encounter_date DATE,
                encounter_type TEXT DEFAULT "consultation",
                status TEXT DEFAULT "open",
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS appointments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER,
                doctor_id INTEGER,
                branch_id INTEGER,
                appointment_date DATE,
                start_time TIME,
                duration_minutes INTEGER DEFAULT 15,
                status TEXT DEFAULT "scheduled",
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
                finalized_by INTEGER, finalized_at DATETIME,
                cancelled_by INTEGER, cancelled_at DATETIME,
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
            CREATE TABLE IF NOT EXISTS payment_reference_sequences (
                date_key TEXT PRIMARY KEY,
                last_number INTEGER DEFAULT 0
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
                voided_by INTEGER, voided_at DATETIME,
                void_reason TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT, code TEXT UNIQUE,
                is_active INTEGER DEFAULT 1,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER,
                name TEXT, code TEXT UNIQUE, description TEXT,
                unit TEXT DEFAULT "unit",
                quantity_on_hand INTEGER DEFAULT 0,
                reorder_level INTEGER DEFAULT 10,
                reorder_quantity INTEGER DEFAULT 50,
                unit_cost INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                track_expiry INTEGER DEFAULT 0,
                expiry_date DATE, supplier TEXT,
                created_at DATETIME, updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                inventory_item_id INTEGER,
                branch_id INTEGER,
                transaction_type TEXT,
                quantity INTEGER,
                quantity_before INTEGER,
                quantity_after INTEGER,
                reference TEXT, notes TEXT,
                performed_by INTEGER,
                created_at DATETIME, updated_at DATETIME
            )
        ');
    }

    private function seedBaseData(): void
    {
        DB::connection('tenant')->table('branches')->insert([
            'id' => 1, 'name' => 'Main', 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('staff')->insert([
            'id' => 1, 'first_name' => 'Dr. Alice', 'last_name' => 'Smith',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Financial Summary
    // -------------------------------------------------------------------------

    public function test_financial_summary_aggregates_invoices_in_period(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            'id' => 1, 'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('invoices')->insert([
            ['id' => 1, 'invoice_number' => 'INV-001', 'patient_id' => 1, 'branch_id' => 1,
             'status' => 'paid', 'total' => 50000, 'amount_paid' => 50000, 'amount_due' => 0,
             'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'invoice_number' => 'INV-002', 'patient_id' => 1, 'branch_id' => 1,
             'status' => 'finalized', 'total' => 20000, 'amount_paid' => 0, 'amount_due' => 20000,
             'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::connection('tenant')->table('payments')->insert([
            'payment_reference' => 'PAY-001', 'invoice_id' => 1, 'patient_id' => 1,
            'branch_id' => 1, 'payment_method' => 'cash', 'amount' => 50000,
            'voided' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = $this->svc->financialSummary($this->from, $this->to);

        $this->assertEquals(70000, $result['total_invoiced']);
        $this->assertEquals(50000, $result['total_collected']);
        $this->assertEquals(20000, $result['total_outstanding']);
        $this->assertEquals(2, $result['invoice_count']);
        $this->assertEquals(1, $result['paid_count']);
        $this->assertCount(1, $result['by_method']);
        $this->assertEquals('cash', $result['by_method'][0]->payment_method);
    }

    public function test_financial_summary_excludes_void_invoices(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            'id' => 2, 'first_name' => 'Bob', 'last_name' => 'Jones',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('invoices')->insert([
            'id' => 10, 'invoice_number' => 'INV-010', 'patient_id' => 2, 'branch_id' => 1,
            'status' => 'void', 'total' => 99999, 'amount_paid' => 0, 'amount_due' => 99999,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = $this->svc->financialSummary($this->from, $this->to);

        $this->assertEquals(0, $result['invoice_count']);
        $this->assertEquals(0, $result['total_invoiced']);
    }

    public function test_financial_summary_excludes_voided_payments(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            'id' => 3, 'first_name' => 'Carol', 'last_name' => 'Lee',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('invoices')->insert([
            'id' => 20, 'invoice_number' => 'INV-020', 'patient_id' => 3, 'branch_id' => 1,
            'status' => 'paid', 'total' => 10000, 'amount_paid' => 10000, 'amount_due' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('payments')->insert([
            'payment_reference' => 'PAY-020', 'invoice_id' => 20, 'patient_id' => 3,
            'branch_id' => 1, 'payment_method' => 'card', 'amount' => 10000,
            'voided' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $result = $this->svc->financialSummary($this->from, $this->to);

        $this->assertCount(0, $result['by_method']);
    }

    // -------------------------------------------------------------------------
    // Invoice Aging
    // -------------------------------------------------------------------------

    public function test_invoice_aging_buckets_correctly(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            'id' => 4, 'first_name' => 'David', 'last_name' => 'Brown',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('invoices')->insert([
            ['id' => 30, 'invoice_number' => 'INV-030', 'patient_id' => 4, 'branch_id' => 1,
             'status' => 'finalized', 'total' => 5000, 'amount_paid' => 0, 'amount_due' => 5000,
             'created_at' => now()->subDays(10), 'updated_at' => now()],
            ['id' => 31, 'invoice_number' => 'INV-031', 'patient_id' => 4, 'branch_id' => 1,
             'status' => 'finalized', 'total' => 8000, 'amount_paid' => 0, 'amount_due' => 8000,
             'created_at' => now()->subDays(45), 'updated_at' => now()],
            ['id' => 32, 'invoice_number' => 'INV-032', 'patient_id' => 4, 'branch_id' => 1,
             'status' => 'finalized', 'total' => 3000, 'amount_paid' => 0, 'amount_due' => 3000,
             'created_at' => now()->subDays(100), 'updated_at' => now()],
        ]);

        $result = $this->svc->invoiceAging();

        $this->assertEquals(1, $result['buckets']['0-30']['count']);
        $this->assertEquals(5000, $result['buckets']['0-30']['total']);
        $this->assertEquals(1, $result['buckets']['31-60']['count']);
        $this->assertEquals(1, $result['buckets']['90+']['count']);
        $this->assertCount(3, $result['rows']);
    }

    public function test_invoice_aging_excludes_zero_balance(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            'id' => 5, 'first_name' => 'Eve', 'last_name' => 'White',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('invoices')->insert([
            'id' => 40, 'invoice_number' => 'INV-040', 'patient_id' => 5, 'branch_id' => 1,
            'status' => 'paid', 'total' => 5000, 'amount_paid' => 5000, 'amount_due' => 0,
            'created_at' => now()->subDays(20), 'updated_at' => now(),
        ]);

        $result = $this->svc->invoiceAging();

        $this->assertCount(0, $result['rows']);
    }

    // -------------------------------------------------------------------------
    // Appointment Volume
    // -------------------------------------------------------------------------

    public function test_appointment_volume_counts_by_status(): void
    {
        DB::connection('tenant')->table('appointments')->insert([
            ['patient_id' => 1, 'doctor_id' => 1, 'branch_id' => 1,
             'appointment_date' => now()->toDateString(), 'status' => 'completed',
             'created_at' => now(), 'updated_at' => now()],
            ['patient_id' => 1, 'doctor_id' => 1, 'branch_id' => 1,
             'appointment_date' => now()->toDateString(), 'status' => 'no_show',
             'created_at' => now(), 'updated_at' => now()],
            ['patient_id' => 1, 'doctor_id' => 1, 'branch_id' => 1,
             'appointment_date' => now()->toDateString(), 'status' => 'completed',
             'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->svc->appointmentVolume($this->from, $this->to);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['completed']);
        $this->assertEquals(1, $result['no_show']);
        $this->assertEqualsWithDelta(33.3, $result['no_show_rate'], 0.1);
    }

    public function test_appointment_volume_groups_by_doctor(): void
    {
        DB::connection('tenant')->table('staff')->insert([
            'id' => 2, 'first_name' => 'Dr. Bob', 'last_name' => 'Jones',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('appointments')->insert([
            ['patient_id' => 1, 'doctor_id' => 1, 'branch_id' => 1,
             'appointment_date' => now()->toDateString(), 'status' => 'completed',
             'created_at' => now(), 'updated_at' => now()],
            ['patient_id' => 1, 'doctor_id' => 2, 'branch_id' => 1,
             'appointment_date' => now()->toDateString(), 'status' => 'completed',
             'created_at' => now(), 'updated_at' => now()],
            ['patient_id' => 1, 'doctor_id' => 1, 'branch_id' => 1,
             'appointment_date' => now()->toDateString(), 'status' => 'no_show',
             'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->svc->appointmentVolume($this->from, $this->to);

        $this->assertCount(2, $result['by_doctor']);
        $doctors = $result['by_doctor']->keyBy('last_name');
        $this->assertEquals(2, $doctors['Smith']->total);
        $this->assertEquals(1, $doctors['Jones']->total);
    }

    // -------------------------------------------------------------------------
    // Inventory Status
    // -------------------------------------------------------------------------

    public function test_inventory_status_counts_low_stock(): void
    {
        DB::connection('tenant')->table('inventory_items')->insert([
            ['name' => 'A', 'code' => 'A1', 'unit' => 'unit', 'quantity_on_hand' => 20,
             'reorder_level' => 5, 'reorder_quantity' => 50, 'unit_cost' => 100,
             'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'B', 'code' => 'B1', 'unit' => 'unit', 'quantity_on_hand' => 3,
             'reorder_level' => 10, 'reorder_quantity' => 50, 'unit_cost' => 200,
             'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'C', 'code' => 'C1', 'unit' => 'unit', 'quantity_on_hand' => 0,
             'reorder_level' => 10, 'reorder_quantity' => 50, 'unit_cost' => 300,
             'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->svc->inventoryStatus($this->from, $this->to);

        $this->assertEquals(3, $result['total_items']);
        $this->assertEquals(2, $result['low_stock_count']);
        $this->assertEquals(1, $result['out_of_stock_count']);
        $this->assertEquals(2600, $result['stock_value_cents']); // 20*100 + 3*200 + 0*300
    }

    public function test_inventory_status_top_dispensed(): void
    {
        $itemId = DB::connection('tenant')->table('inventory_items')->insertGetId([
            'name' => 'Paracetamol', 'code' => 'PARA-01', 'unit' => 'tablet',
            'quantity_on_hand' => 100, 'reorder_level' => 10, 'reorder_quantity' => 50,
            'unit_cost' => 10, 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('inventory_transactions')->insert([
            ['inventory_item_id' => $itemId, 'branch_id' => 1,
             'transaction_type' => 'dispense', 'quantity' => -15,
             'quantity_before' => 100, 'quantity_after' => 85, 'performed_by' => 1,
             'created_at' => now(), 'updated_at' => now()],
            ['inventory_item_id' => $itemId, 'branch_id' => 1,
             'transaction_type' => 'dispense', 'quantity' => -10,
             'quantity_before' => 85, 'quantity_after' => 75, 'performed_by' => 1,
             'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->svc->inventoryStatus($this->from, $this->to);

        $this->assertCount(1, $result['top_dispensed']);
        $this->assertEquals(25, $result['top_dispensed'][0]->total_dispensed);
    }

    // -------------------------------------------------------------------------
    // Patient Activity
    // -------------------------------------------------------------------------

    public function test_patient_activity_counts_new_registrations(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            ['first_name' => 'P1', 'last_name' => 'X', 'created_at' => now(), 'updated_at' => now()],
            ['first_name' => 'P2', 'last_name' => 'X', 'created_at' => now(), 'updated_at' => now()],
            ['first_name' => 'P3', 'last_name' => 'X', 'created_at' => now()->subDays(60), 'updated_at' => now()],
        ]);

        $result = $this->svc->patientActivity($this->from, $this->to);

        $this->assertEquals(2, $result['new_patients']);
    }

    public function test_patient_activity_counts_encounters_by_type(): void
    {
        DB::connection('tenant')->table('encounters')->insert([
            ['patient_id' => 1, 'branch_id' => 1, 'encounter_date' => now()->toDateString(),
             'encounter_type' => 'consultation',
             'created_at' => now(), 'updated_at' => now()],
            ['patient_id' => 1, 'branch_id' => 1, 'encounter_date' => now()->toDateString(),
             'encounter_type' => 'consultation',
             'created_at' => now(), 'updated_at' => now()],
            ['patient_id' => 1, 'branch_id' => 1, 'encounter_date' => now()->toDateString(),
             'encounter_type' => 'walk_in',
             'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->svc->patientActivity($this->from, $this->to);

        $this->assertEquals(3, $result['total_encounters']);
        $types = $result['encounter_by_type']->keyBy('encounter_type');
        $this->assertEquals(2, $types['consultation']->count);
        $this->assertEquals(1, $types['walk_in']->count);
    }

    // -------------------------------------------------------------------------
    // CSV export
    // -------------------------------------------------------------------------

    public function test_financial_summary_csv_format(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            'id' => 99, 'first_name' => 'T', 'last_name' => 'U',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('invoices')->insert([
            'id' => 99, 'invoice_number' => 'INV-CSV', 'patient_id' => 99, 'branch_id' => 1,
            'status' => 'paid', 'total' => 10000, 'amount_paid' => 10000, 'amount_due' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('payments')->insert([
            'payment_reference' => 'PAY-CSV', 'invoice_id' => 99, 'patient_id' => 99,
            'branch_id' => 1, 'payment_method' => 'linx', 'amount' => 10000,
            'voided' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $data = $this->svc->financialSummary($this->from, $this->to);
        $csv  = $this->svc->financialSummaryCsv($data);

        $this->assertStringContainsString('Payment Method', $csv);
        $this->assertStringContainsString('linx', $csv);
        $this->assertStringContainsString('100.00', $csv);
    }

    public function test_invoice_aging_csv_format(): void
    {
        DB::connection('tenant')->table('patients')->insert([
            'id' => 98, 'first_name' => 'Aging', 'last_name' => 'Test',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('invoices')->insert([
            'id' => 98, 'invoice_number' => 'INV-AGE', 'patient_id' => 98, 'branch_id' => 1,
            'status' => 'finalized', 'total' => 7500, 'amount_paid' => 0, 'amount_due' => 7500,
            'created_at' => now()->subDays(5), 'updated_at' => now(),
        ]);

        $data = $this->svc->invoiceAging();
        $csv  = $this->svc->invoiceAgingCsv($data['rows']);

        $this->assertStringContainsString('INV-AGE', $csv);
        $this->assertStringContainsString('Aging Test', $csv);
        $this->assertStringContainsString('75.00', $csv);
    }
}
