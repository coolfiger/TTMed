<?php

namespace Tests\Feature\Phase8;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\DoctorSchedule;
use App\Services\AppointmentService;
use App\Services\AuditService;
use App\Services\QueueService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppointmentServiceTest extends TestCase
{
    private AppointmentService $svc;
    private int $doctorId   = 1;
    private int $branchId   = 1;
    private int $patientId  = 1;

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
            CREATE TABLE IF NOT EXISTS doctor_schedules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                doctor_id INTEGER NOT NULL,
                branch_id INTEGER NOT NULL,
                day_of_week INTEGER NOT NULL,
                is_working_day INTEGER DEFAULT 1,
                start_time TEXT,
                end_time TEXT,
                slot_duration_minutes INTEGER DEFAULT 15,
                max_patients_per_slot INTEGER DEFAULT 1,
                max_patients_per_day INTEGER,
                created_at DATETIME,
                updated_at DATETIME,
                UNIQUE(doctor_id, branch_id, day_of_week)
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS doctor_unavailability (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                doctor_id INTEGER NOT NULL,
                branch_id INTEGER NOT NULL,
                unavailable_date DATE NOT NULL,
                from_time TEXT,
                to_time TEXT,
                reason TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS appointments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                appointment_number TEXT UNIQUE,
                patient_id INTEGER,
                doctor_id INTEGER,
                branch_id INTEGER,
                encounter_id INTEGER,
                appointment_date DATE,
                appointment_time TEXT,
                duration_minutes INTEGER DEFAULT 15,
                appointment_type TEXT DEFAULT "scheduled",
                status TEXT DEFAULT "scheduled",
                reason TEXT,
                notes TEXT,
                booked_by INTEGER,
                cancelled_by INTEGER,
                cancellation_reason TEXT,
                cancelled_at DATETIME,
                arrived_at DATETIME,
                reminder_sent INTEGER DEFAULT 0,
                reminder_sent_at DATETIME,
                rescheduled_to INTEGER,
                rescheduled_from INTEGER,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS appointment_number_sequences (
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

        $audit    = $this->createMock(AuditService::class);
        $audit->method('logTenant')->willReturnCallback(fn () => null);
        $queue    = $this->createMock(QueueService::class);

        $this->svc = new AppointmentService($audit, $queue);
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS appointment_number_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS appointments');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS doctor_unavailability');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS doctor_schedules');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS audit_logs');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patients');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS users');
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeSchedule(array $overrides = []): void
    {
        // Monday = day_of_week 1; use a known Monday
        DoctorSchedule::on('tenant')->create(array_merge([
            'doctor_id'             => $this->doctorId,
            'branch_id'             => $this->branchId,
            'day_of_week'           => 1,
            'is_working_day'        => true,
            'start_time'            => '08:00',
            'end_time'              => '12:00',
            'slot_duration_minutes' => 30,
            'max_patients_per_slot' => 1,
            'max_patients_per_day'  => null,
        ], $overrides));
    }

    /** Returns next Monday from today */
    private function nextMonday(): string
    {
        $d = now();
        while ($d->dayOfWeek !== 1) {
            $d = $d->addDay();
        }
        return $d->toDateString();
    }

    // -------------------------------------------------------------------------
    // Schedule management
    // -------------------------------------------------------------------------

    public function test_save_schedule_creates_rows(): void
    {
        $this->svc->saveSchedule($this->doctorId, $this->branchId, [
            1 => ['is_working_day' => true, 'start_time' => '09:00', 'end_time' => '17:00',
                  'slot_duration_minutes' => 20, 'max_patients_per_slot' => 1, 'max_patients_per_day' => null],
        ]);

        $schedule = DoctorSchedule::on('tenant')
            ->where('doctor_id', $this->doctorId)
            ->where('day_of_week', 1)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertSame('09:00', $schedule->start_time);
        $this->assertSame(20, $schedule->slot_duration_minutes);
    }

    // -------------------------------------------------------------------------
    // Available slots
    // -------------------------------------------------------------------------

    public function test_returns_slots_for_working_day(): void
    {
        $this->makeSchedule();
        $monday = $this->nextMonday();
        $slots  = $this->svc->getAvailableSlots($this->doctorId, $this->branchId, $monday);

        // 08:00–12:00 at 30 min = 8 slots
        $this->assertCount(8, $slots);
        $this->assertTrue($slots[0]['available']);
    }

    public function test_returns_no_slots_for_non_working_day(): void
    {
        $this->makeSchedule(['is_working_day' => false]);
        $slots = $this->svc->getAvailableSlots($this->doctorId, $this->branchId, $this->nextMonday());

        $this->assertEmpty($slots);
    }

    public function test_slot_becomes_unavailable_when_booked(): void
    {
        $this->makeSchedule();
        $monday = $this->nextMonday();

        // Book the first slot manually
        Appointment::on('tenant')->create([
            'appointment_number' => 'APT-TEST-0001',
            'patient_id'    => $this->patientId,
            'doctor_id'     => $this->doctorId,
            'branch_id'     => $this->branchId,
            'appointment_date' => $monday,
            'appointment_time' => '08:00',
            'status'        => 'scheduled',
        ]);

        $slots = $this->svc->getAvailableSlots($this->doctorId, $this->branchId, $monday);

        $first = collect($slots)->firstWhere('time', '08:00');
        $this->assertFalse($first['available']);
        $this->assertSame(1, $first['booked']);
    }

    // -------------------------------------------------------------------------
    // Booking
    // -------------------------------------------------------------------------

    public function test_books_appointment(): void
    {
        $this->makeSchedule();
        $monday = $this->nextMonday();

        $appt = $this->svc->book(
            patientId: $this->patientId,
            doctorId:  $this->doctorId,
            branchId:  $this->branchId,
            date:      $monday,
            time:      '08:00',
        );

        $this->assertSame('scheduled', $appt->status);
        $this->assertStringStartsWith('APT-', $appt->appointment_number);
    }

    public function test_appointment_numbers_are_sequential(): void
    {
        $this->makeSchedule(['max_patients_per_slot' => 2]);
        $monday = $this->nextMonday();

        $a1 = $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $monday, '08:00');
        $a2 = $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $monday, '08:30');

        $n1 = (int) substr($a1->appointment_number, -4);
        $n2 = (int) substr($a2->appointment_number, -4);
        $this->assertSame($n1 + 1, $n2);
    }

    public function test_prevents_double_booking(): void
    {
        $this->makeSchedule(['max_patients_per_slot' => 1]);
        $monday = $this->nextMonday();

        $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $monday, '08:00');

        $this->expectException(\InvalidArgumentException::class);
        $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $monday, '08:00');
    }

    public function test_prevents_booking_on_non_working_day(): void
    {
        $this->makeSchedule(['is_working_day' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $this->nextMonday(), '08:00');
    }

    public function test_per_day_cap_enforced(): void
    {
        $this->makeSchedule(['max_patients_per_slot' => 2, 'max_patients_per_day' => 1]);
        $monday = $this->nextMonday();

        $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $monday, '08:00');

        $this->expectException(\InvalidArgumentException::class);
        $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $monday, '08:30');
    }

    // -------------------------------------------------------------------------
    // Workflows
    // -------------------------------------------------------------------------

    public function test_cancel_appointment(): void
    {
        $this->makeSchedule();
        $appt = $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $this->nextMonday(), '08:00');

        $this->svc->cancel($appt, 'Patient requested.', 1);
        $appt->refresh();

        $this->assertTrue($appt->isCancelled());
        $this->assertSame('Patient requested.', $appt->cancellation_reason);
    }

    public function test_cannot_cancel_already_cancelled(): void
    {
        $this->makeSchedule();
        $appt = $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $this->nextMonday(), '08:00');
        $this->svc->cancel($appt, 'Reason.', 1);
        $appt->refresh();

        $this->expectException(\LogicException::class);
        $this->svc->cancel($appt, 'Again.', 1);
    }

    public function test_mark_no_show(): void
    {
        $this->makeSchedule();
        $appt = $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $this->nextMonday(), '08:00');

        $this->svc->markNoShow($appt);
        $appt->refresh();

        $this->assertTrue($appt->isNoShow());
    }

    public function test_reschedule_creates_new_appointment(): void
    {
        $this->makeSchedule(['max_patients_per_slot' => 2]);
        $monday = $this->nextMonday();

        $old = $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $monday, '08:00');
        $new = $this->svc->reschedule($old, $monday, '08:30', 1);

        $old->refresh();

        $this->assertSame('rescheduled', $old->status);
        $this->assertSame('scheduled',   $new->status);
        $this->assertSame($new->id, $old->rescheduled_to);
        $this->assertSame($old->id, $new->rescheduled_from);
    }

    public function test_mark_arrived(): void
    {
        $this->makeSchedule();
        $appt = $this->svc->book($this->patientId, $this->doctorId, $this->branchId, $this->nextMonday(), '08:00');
        $this->svc->markArrived($appt);
        $appt->refresh();

        $this->assertTrue($appt->isArrived());
        $this->assertNotNull($appt->arrived_at);
    }
}
