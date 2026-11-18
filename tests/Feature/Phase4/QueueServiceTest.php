<?php

namespace Tests\Feature\Phase4;

use App\Models\Tenant\DoctorAvailability;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\Patient;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\QueueEvent;
use App\Services\QueueNumberService;
use App\Services\QueueService;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Patients table
        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS patients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_number VARCHAR(30) NOT NULL,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            title VARCHAR(10),
            middle_name VARCHAR(80),
            preferred_name VARCHAR(80),
            gender VARCHAR(1),
            date_of_birth DATE,
            national_id VARCHAR(50),
            passport_number VARCHAR(50),
            phone_primary VARCHAR(30),
            phone_secondary VARCHAR(30),
            email VARCHAR(150),
            address TEXT,
            city VARCHAR(80),
            country VARCHAR(80),
            occupation VARCHAR(100),
            employer VARCHAR(150),
            marital_status VARCHAR(20),
            blood_type VARCHAR(5),
            insurance_provider_id INTEGER,
            insurance_policy_number VARCHAR(100),
            insurance_group_number VARCHAR(100),
            registered_by INTEGER,
            primary_doctor_id INTEGER,
            branch_id INTEGER,
            is_active INTEGER DEFAULT 1,
            is_deceased INTEGER DEFAULT 0,
            deceased_date DATE,
            notes TEXT,
            created_at TIMESTAMP,
            updated_at TIMESTAMP,
            deleted_at TIMESTAMP
        )');

        // Encounters
        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS encounters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            branch_id INTEGER,
            doctor_id INTEGER,
            queue_entry_id INTEGER,
            visit_type VARCHAR(50) DEFAULT "walk_in",
            status VARCHAR(30) DEFAULT "checked_in",
            encounter_date DATE NOT NULL,
            checked_in_at TIMESTAMP,
            seen_by_nurse_at TIMESTAMP,
            seen_by_doctor_at TIMESTAMP,
            completed_at TIMESTAMP,
            checked_in_by INTEGER,
            chief_complaint TEXT,
            notes TEXT,
            created_at TIMESTAMP,
            updated_at TIMESTAMP
        )');

        // Queue entries
        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS queue_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            encounter_id INTEGER UNIQUE,
            patient_id INTEGER NOT NULL,
            branch_id INTEGER NOT NULL,
            doctor_id INTEGER,
            queue_number INTEGER NOT NULL,
            queue_date DATE NOT NULL,
            visit_type VARCHAR(50) DEFAULT "walk_in",
            status VARCHAR(30) DEFAULT "waiting",
            is_priority INTEGER DEFAULT 0,
            priority_reason VARCHAR(200),
            checked_in_by INTEGER,
            called_at TIMESTAMP,
            created_at TIMESTAMP,
            updated_at TIMESTAMP
        )');

        // Queue events
        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS queue_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            queue_entry_id INTEGER NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            from_status VARCHAR(50),
            to_status VARCHAR(50),
            performed_by INTEGER,
            notes TEXT,
            occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');

        // Queue number sequences
        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS queue_number_sequences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            branch_id INTEGER NOT NULL,
            queue_date DATE NOT NULL,
            last_number INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMP,
            updated_at TIMESTAMP,
            UNIQUE(branch_id, queue_date)
        )');

        // Doctor availability
        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS doctor_availability (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            doctor_id INTEGER NOT NULL,
            branch_id INTEGER NOT NULL,
            status VARCHAR(30) DEFAULT "available",
            status_note VARCHAR(200),
            updated_by INTEGER,
            created_at TIMESTAMP,
            updated_at TIMESTAMP,
            UNIQUE(doctor_id, branch_id)
        )');
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS doctor_availability');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS queue_number_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS queue_events');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS queue_entries');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS encounters');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patients');
        parent::tearDown();
    }

    private function makePatient(): Patient
    {
        $id = DB::connection('tenant')->table('patients')->insertGetId([
            'patient_number' => 'PT-000001',
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return Patient::on('tenant')->find($id);
    }

    private function makeQueueService(): QueueService
    {
        return new QueueService(
            new QueueNumberService(),
            app(TenantContext::class),
        );
    }

    public function test_check_in_creates_encounter_and_queue_entry(): void
    {
        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();

        $entry = $svc->checkIn($patient, 1, 1);

        $this->assertSame(1, $entry->queue_number);
        $this->assertSame('waiting', $entry->status);
        $this->assertSame($patient->id, $entry->patient_id);

        $this->assertDatabaseHas('encounters', ['patient_id' => $patient->id], 'tenant');
    }

    public function test_check_in_assigns_sequential_queue_numbers(): void
    {
        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();

        $e1 = $svc->checkIn($patient, 1, 1);
        $e2 = $svc->checkIn($patient, 1, 1);
        $e3 = $svc->checkIn($patient, 1, 1);

        $this->assertSame(1, $e1->queue_number);
        $this->assertSame(2, $e2->queue_number);
        $this->assertSame(3, $e3->queue_number);
    }

    public function test_check_in_records_event(): void
    {
        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();
        $entry   = $svc->checkIn($patient, 1, 1);

        $events = QueueEvent::on('tenant')->where('queue_entry_id', $entry->id)->get();
        $this->assertCount(1, $events);
        $this->assertSame('checked_in', $events->first()->event_type);
    }

    public function test_transition_changes_status(): void
    {
        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();
        $entry   = $svc->checkIn($patient, 1, 1);

        $entry = $svc->transition($entry, 'awaiting_doctor', 1);

        $this->assertSame('awaiting_doctor', $entry->status);
        $this->assertSame('awaiting_doctor', QueueEntry::on('tenant')->find($entry->id)->status);
    }

    public function test_transition_records_event(): void
    {
        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();
        $entry   = $svc->checkIn($patient, 1, 1);
        $svc->transition($entry, 'awaiting_doctor', 1);

        $events = QueueEvent::on('tenant')->where('queue_entry_id', $entry->id)->get();
        $this->assertCount(2, $events);

        $transition = $events->last();
        $this->assertSame('status_changed', $transition->event_type);
        $this->assertSame('waiting', $transition->from_status);
        $this->assertSame('awaiting_doctor', $transition->to_status);
    }

    public function test_transition_throws_on_invalid_state_change(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();
        $entry   = $svc->checkIn($patient, 1, 1);

        $svc->transition($entry, 'completed', 1);
    }

    public function test_set_priority_flag(): void
    {
        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();
        $entry   = $svc->checkIn($patient, 1, 1);

        $this->assertFalse($entry->is_priority);

        $entry = $svc->setPriority($entry, true, 'Chest pain', 1);

        $this->assertTrue($entry->is_priority);
        $this->assertSame('Chest pain', $entry->priority_reason);
    }

    public function test_remove_priority_clears_reason(): void
    {
        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();
        $entry   = $svc->checkIn($patient, 1, 1, isPriority: true, priorityReason: 'Emergency');

        $entry = $svc->setPriority($entry, false, null, 1);

        $this->assertFalse($entry->is_priority);
        $this->assertNull($entry->priority_reason);
    }

    public function test_update_doctor_availability(): void
    {
        $svc = $this->makeQueueService();
        $avail = $svc->updateDoctorAvailability(1, 1, 'on_break', 1, 'Back at 2pm');

        $this->assertSame('on_break', $avail->status);
        $this->assertSame('Back at 2pm', $avail->status_note);
    }

    public function test_update_doctor_availability_upserts(): void
    {
        $svc = $this->makeQueueService();
        $svc->updateDoctorAvailability(1, 1, 'available', 1);
        $svc->updateDoctorAvailability(1, 1, 'busy', 1, 'With patient');

        $count = DoctorAvailability::on('tenant')->where('doctor_id', 1)->where('branch_id', 1)->count();
        $this->assertSame(1, $count);

        $status = DoctorAvailability::on('tenant')->where('doctor_id', 1)->where('branch_id', 1)->value('status');
        $this->assertSame('busy', $status);
    }

    public function test_two_branches_have_independent_queues(): void
    {
        $patient = $this->makePatient();
        $svc     = $this->makeQueueService();

        $b1 = $svc->checkIn($patient, 1, 1);
        $b2 = $svc->checkIn($patient, 2, 1);
        $b1b = $svc->checkIn($patient, 1, 1);

        $this->assertSame(1, $b1->queue_number);
        $this->assertSame(1, $b2->queue_number);
        $this->assertSame(2, $b1b->queue_number);
    }
}
