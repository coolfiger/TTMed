<?php

namespace Tests\Feature\Phase5;

use App\Services\Hl7MessageService;
use App\Services\LabOrderService;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LabOrderServiceTest extends TestCase
{
    private LabOrderService $svc;

    protected function setUp(): void
    {
        parent::setUp();

        // Create all tables LabOrderService needs
        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_number TEXT,
                first_name TEXT,
                last_name TEXT,
                date_of_birth DATE,
                gender TEXT,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS lab_tests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                code TEXT,
                loinc_code TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS lab_panels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                code TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS lab_orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_number TEXT UNIQUE,
                patient_id INTEGER,
                encounter_id INTEGER,
                ordered_by INTEGER,
                branch_id INTEGER,
                status TEXT DEFAULT "pending",
                order_type TEXT DEFAULT "routine",
                clinical_notes TEXT,
                integration_type TEXT,
                lis_placer_order_number TEXT,
                lis_filler_order_number TEXT,
                ordered_at DATETIME,
                sent_to_lab_at DATETIME,
                collected_at DATETIME,
                collected_by INTEGER,
                results_received_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS lab_order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lab_order_id INTEGER,
                lab_test_id INTEGER,
                lab_panel_id INTEGER,
                test_name TEXT,
                test_code TEXT,
                loinc_code TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS lab_results (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lab_order_id INTEGER,
                lab_order_item_id INTEGER,
                patient_id INTEGER,
                lab_test_id INTEGER,
                test_name TEXT,
                test_code TEXT,
                loinc_code TEXT,
                value TEXT,
                value_unit TEXT,
                reference_range TEXT,
                flag TEXT DEFAULT "normal",
                is_critical INTEGER DEFAULT 0,
                performing_lab TEXT,
                result_datetime DATETIME,
                source TEXT DEFAULT "manual",
                raw_message TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS lab_order_number_sequences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_date DATE UNIQUE,
                last_number INTEGER DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS integration_configs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT,
                provider TEXT,
                is_active INTEGER DEFAULT 0,
                send_orders INTEGER DEFAULT 0,
                receive_results INTEGER DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        $this->svc = new LabOrderService(
            new Hl7MessageService(),
            app(TenantContext::class),
        );
    }

    protected function tearDown(): void
    {
        foreach (['lab_results', 'lab_order_items', 'lab_orders', 'lab_order_number_sequences', 'lab_panels', 'lab_tests', 'patients', 'integration_configs'] as $table) {
            DB::connection('tenant')->statement("DROP TABLE IF EXISTS {$table}");
        }
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Order creation
    // -------------------------------------------------------------------------

    public function test_create_order_generates_order_number(): void
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $patient = \App\Models\Tenant\Patient::on('tenant')->find($patientId);

        $order = $this->svc->createOrder($patient, 1, [], []);

        $this->assertStringStartsWith('LAB-', $order->order_number);
        $this->assertEquals('pending', $order->status);
    }

    public function test_order_number_increments_sequentially(): void
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $patient = \App\Models\Tenant\Patient::on('tenant')->find($patientId);

        $order1 = $this->svc->createOrder($patient, 1, []);
        $order2 = $this->svc->createOrder($patient, 1, []);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    public function test_create_order_with_test_ids_creates_items(): void
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $patient = \App\Models\Tenant\Patient::on('tenant')->find($patientId);

        $testId = DB::connection('tenant')->table('lab_tests')->insertGetId([
            'name' => 'Complete Blood Count', 'code' => 'CBC', 'loinc_code' => '58410-2',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $order = $this->svc->createOrder($patient, 1, [$testId]);

        $items = DB::connection('tenant')->table('lab_order_items')
            ->where('lab_order_id', $order->id)
            ->get();

        $this->assertCount(1, $items);
        $this->assertEquals('CBC', $items[0]->test_code);
        $this->assertEquals('58410-2', $items[0]->loinc_code);
    }

    // -------------------------------------------------------------------------
    // HL7 result ingestion
    // -------------------------------------------------------------------------

    public function test_receive_hl7_result_stores_results(): void
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $patient = \App\Models\Tenant\Patient::on('tenant')->find($patientId);

        $order = $this->svc->createOrder($patient, 1, []);

        // Update order with placer number so HL7 can match it
        DB::connection('tenant')->table('lab_orders')
            ->where('id', $order->id)
            ->update(['lis_placer_order_number' => 'TTM-' . $order->id]);

        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101120000||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane\r"
             . "OBR|1|TTM-{$order->id}|LIS-999|CBC^CBC^L\r"
             . "OBX|1|NM|WBC^WBC^L||5.5|10^9/L|4.0-11.0|N|||F\r";

        $ok = $this->svc->receiveHl7Result($raw, 'test-tenant-uuid');

        $this->assertTrue($ok);

        $results = DB::connection('tenant')->table('lab_results')
            ->where('lab_order_id', $order->id)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('WBC', $results[0]->test_code);
        $this->assertEquals('5.5', $results[0]->value);
        $this->assertEquals('hl7_v2', $results[0]->source);
    }

    public function test_receive_hl7_result_for_unknown_order_returns_false(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane\r"
             . "OBR|1|UNKNOWN-9999999||CBC^CBC^L\r"
             . "OBX|1|NM|WBC^WBC^L||5.5|10^9/L|4.0-11.0|N|||F\r";

        $ok = $this->svc->receiveHl7Result($raw, 'test-tenant-uuid');

        $this->assertFalse($ok);
    }

    public function test_order_status_updates_to_results_received(): void
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $patient = \App\Models\Tenant\Patient::on('tenant')->find($patientId);

        // Create order with one item
        $testId = DB::connection('tenant')->table('lab_tests')->insertGetId([
            'name' => 'WBC', 'code' => 'WBC', 'loinc_code' => '6690-2',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $order = $this->svc->createOrder($patient, 1, [$testId]);
        DB::connection('tenant')->table('lab_orders')
            ->where('id', $order->id)
            ->update(['lis_placer_order_number' => 'TTM-' . $order->id]);

        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101120000||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane\r"
             . "OBR|1|TTM-{$order->id}||WBC^WBC^L\r"
             . "OBX|1|NM|WBC^WBC Count^L||5.5|10^9/L|4.0-11.0|N|||F\r";

        $this->svc->receiveHl7Result($raw, 'uuid');

        $updatedOrder = DB::connection('tenant')->table('lab_orders')->where('id', $order->id)->first();
        $this->assertContains($updatedOrder->status, ['results_received', 'partial_results']);
    }

    public function test_cancel_order_works_for_pending(): void
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $patient = \App\Models\Tenant\Patient::on('tenant')->find($patientId);
        $order   = $this->svc->createOrder($patient, 1, []);

        $cancelled = $this->svc->cancelOrder($order, 1);

        $this->assertEquals('cancelled', $cancelled->status);
    }

    public function test_cancel_order_throws_if_results_received(): void
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $patient = \App\Models\Tenant\Patient::on('tenant')->find($patientId);
        $order   = $this->svc->createOrder($patient, 1, []);

        DB::connection('tenant')->table('lab_orders')
            ->where('id', $order->id)
            ->update(['status' => 'results_received']);

        $order->refresh();

        $this->expectException(\InvalidArgumentException::class);
        $this->svc->cancelOrder($order, 1);
    }
}
