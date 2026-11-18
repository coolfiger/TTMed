<?php

namespace Tests\Feature\Phase7;

use App\Models\Tenant\Encounter;
use App\Models\Tenant\Prescription;
use App\Models\Tenant\PrescriptionItem;
use App\Services\AuditService;
use App\Services\PrescriptionService;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrescriptionServiceTest extends TestCase
{
    private PrescriptionService $svc;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                patient_number TEXT,
                date_of_birth DATE,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS encounters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                branch_id INTEGER,
                doctor_id INTEGER,
                status TEXT DEFAULT "open",
                started_at DATETIME,
                ended_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS prescriptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                prescription_number TEXT UNIQUE,
                encounter_id INTEGER,
                patient_id INTEGER,
                doctor_id INTEGER,
                branch_id INTEGER,
                status TEXT DEFAULT "draft",
                allergy_checked INTEGER DEFAULT 0,
                allergy_check_notes TEXT,
                pdf_path TEXT,
                issued_by INTEGER,
                issued_at DATETIME,
                cancelled_by INTEGER,
                cancelled_at DATETIME,
                cancellation_reason TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS prescription_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                prescription_id INTEGER NOT NULL,
                medication_id INTEGER,
                medication_name TEXT,
                strength TEXT,
                form TEXT,
                route TEXT,
                dose TEXT,
                frequency TEXT,
                duration INTEGER,
                duration_unit TEXT DEFAULT "days",
                quantity TEXT,
                instructions TEXT,
                refills_allowed INTEGER DEFAULT 0,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS prescription_number_sequences (
                date_key TEXT PRIMARY KEY,
                last_number INTEGER DEFAULT 0
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT,
                entity_type TEXT,
                entity_id INTEGER,
                old_values TEXT,
                new_values TEXT,
                description TEXT,
                created_at DATETIME
            )
        ');

        $audit = $this->createMock(AuditService::class);
        $audit->method('logTenant')->willReturnCallback(fn () => null);

        $context = $this->createMock(TenantContext::class);
        $context->method('storagePath')->willReturnCallback(fn ($sub) => '/tmp/ttmed-test/' . $sub);

        $this->svc = new PrescriptionService($context, $audit);
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS prescription_number_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS prescription_items');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS prescriptions');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS encounters');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patients');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS users');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS audit_logs');

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createEncounter(): Encounter
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'John', 'last_name' => 'Doe',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $id = DB::connection('tenant')->table('encounters')->insertGetId([
            'patient_id' => $patientId,
            'branch_id'  => 1,
            'doctor_id'  => 1,
            'status'     => 'open',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Encounter::on('tenant')->find($id);
    }

    private function addItem(Prescription $prescription, string $name = 'Amoxicillin'): PrescriptionItem
    {
        return $this->svc->addItem($prescription, [
            'medication_name' => $name,
            'strength'        => '500mg',
            'form'            => 'capsule',
            'route'           => 'oral',
            'dose'            => 'One capsule',
            'frequency'       => 'Three times daily',
            'duration'        => 7,
            'duration_unit'   => 'days',
            'quantity'        => '21',
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_creates_draft_prescription(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);

        $this->assertTrue($prescription->isDraft());
        $this->assertStringStartsWith('RX-', $prescription->prescription_number);
        $this->assertSame($encounter->patient_id, $prescription->patient_id);
    }

    public function test_get_or_create_is_idempotent(): void
    {
        $encounter = $this->createEncounter();
        $p1 = $this->svc->getOrCreateDraft($encounter->id, 1);
        $p2 = $this->svc->getOrCreateDraft($encounter->id, 1);

        $this->assertSame($p1->id, $p2->id);
    }

    public function test_prescription_number_format(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);

        $this->assertMatchesRegularExpression('/^RX-\d{8}-\d{4}$/', $prescription->prescription_number);
    }

    public function test_prescription_numbers_are_sequential(): void
    {
        $e1 = $this->createEncounter();
        $e2 = $this->createEncounter();

        $p1 = $this->svc->getOrCreateDraft($e1->id, 1);
        $p2 = $this->svc->getOrCreateDraft($e2->id, 1);

        $n1 = (int) substr($p1->prescription_number, -4);
        $n2 = (int) substr($p2->prescription_number, -4);

        $this->assertSame($n1 + 1, $n2);
    }

    public function test_adds_item_to_draft(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);
        $item         = $this->addItem($prescription);

        $this->assertSame('Amoxicillin', $item->medication_name);
        $this->assertSame($prescription->id, $item->prescription_id);
    }

    public function test_cannot_add_item_to_non_draft(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);

        $prescription->update(['status' => Prescription::STATUS_ISSUED]);
        $prescription->refresh();

        $this->expectException(\LogicException::class);
        $this->addItem($prescription);
    }

    public function test_removes_item_from_draft(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);
        $item         = $this->addItem($prescription);

        $this->svc->removeItem($item);

        $this->assertNull(PrescriptionItem::on('tenant')->find($item->id));
    }

    public function test_cannot_remove_item_from_non_draft(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);
        $item         = $this->addItem($prescription);

        $prescription->update(['status' => Prescription::STATUS_ISSUED]);

        $this->expectException(\LogicException::class);
        $item->load('prescription');
        $this->svc->removeItem($item);
    }

    public function test_cannot_issue_prescription_with_no_items(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->svc->issuePrescription($prescription, 1);
    }

    public function test_allergy_check_detects_conflict(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);
        $prescription->load('items');

        $this->addItem($prescription, 'Amoxicillin');
        $prescription->load('items');

        $warnings = $this->svc->checkAllergies($prescription, ['penicillin', 'amoxicillin']);

        $this->assertContains('Amoxicillin', $warnings);
    }

    public function test_allergy_check_returns_empty_on_no_conflict(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);
        $this->addItem($prescription, 'Paracetamol');
        $prescription->load('items');

        $warnings = $this->svc->checkAllergies($prescription, ['penicillin']);

        $this->assertEmpty($warnings);
    }

    public function test_cancel_prescription(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);

        $this->svc->cancelPrescription($prescription, 'Patient declined.', 1);
        $prescription->refresh();

        $this->assertTrue($prescription->isCancelled());
        $this->assertSame('Patient declined.', $prescription->cancellation_reason);
    }

    public function test_cannot_cancel_already_cancelled(): void
    {
        $encounter    = $this->createEncounter();
        $prescription = $this->svc->getOrCreateDraft($encounter->id, 1);

        $this->svc->cancelPrescription($prescription, 'First reason.', 1);
        $prescription->refresh();

        $this->expectException(\LogicException::class);
        $this->svc->cancelPrescription($prescription, 'Second reason.', 1);
    }
}
