<?php

namespace Tests\Feature\Phase6;

use App\Models\Tenant\ConsultationNote;
use App\Models\Tenant\Diagnosis;
use App\Models\Tenant\Icd10Code;
use App\Services\AuditService;
use App\Services\ConsultationService;
use App\Services\QueueService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultationServiceTest extends TestCase
{
    private ConsultationService $svc;

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
            CREATE TABLE IF NOT EXISTS consultation_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                encounter_id INTEGER UNIQUE NOT NULL,
                patient_id INTEGER NOT NULL,
                doctor_id INTEGER NOT NULL,
                subjective TEXT,
                objective TEXT,
                assessment TEXT,
                plan TEXT,
                examination_findings TEXT,
                investigations_ordered TEXT,
                follow_up_instructions TEXT,
                follow_up_days INTEGER,
                is_finalized INTEGER DEFAULT 0,
                finalized_by INTEGER,
                finalized_at DATETIME,
                content_hash TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS icd10_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                description TEXT,
                category_code TEXT,
                category_description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS diagnoses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                encounter_id INTEGER NOT NULL,
                patient_id INTEGER NOT NULL,
                icd10_code_id INTEGER,
                icd10_code TEXT,
                diagnosis_text TEXT NOT NULL,
                diagnosis_type TEXT DEFAULT "primary",
                certainty TEXT DEFAULT "confirmed",
                notes TEXT,
                diagnosed_by INTEGER,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT,
                resource_type TEXT,
                resource_id INTEGER,
                old_values TEXT,
                new_values TEXT,
                description TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        $auditService = $this->createMock(AuditService::class);
        $queueService = $this->createMock(QueueService::class);

        $this->svc = new ConsultationService($queueService, $auditService);
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS audit_logs');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS diagnoses');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS icd10_codes');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS consultation_notes');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS encounters');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patients');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS users');

        parent::tearDown();
    }

    private function seedEncounter(): object
    {
        $patientId = DB::connection('tenant')->table('patients')->insertGetId([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $encounterId = DB::connection('tenant')->table('encounters')->insertGetId([
            'patient_id' => $patientId,
            'status'     => 'open',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object) ['encounter_id' => $encounterId, 'patient_id' => $patientId];
    }

    // -------------------------------------------------------------------------
    // Note creation
    // -------------------------------------------------------------------------

    public function test_get_or_create_note_creates_new(): void
    {
        $data = $this->seedEncounter();
        $note = $this->svc->getOrCreateNote($data->encounter_id, 1);

        $this->assertInstanceOf(ConsultationNote::class, $note);
        $this->assertEquals($data->encounter_id, $note->encounter_id);
        $this->assertEquals($data->patient_id, $note->patient_id);
        $this->assertEquals(1, $note->doctor_id);
        $this->assertFalse($note->isFinalized());
    }

    public function test_get_or_create_note_returns_existing(): void
    {
        $data  = $this->seedEncounter();
        $note1 = $this->svc->getOrCreateNote($data->encounter_id, 1);
        $note2 = $this->svc->getOrCreateNote($data->encounter_id, 2); // different doctor id

        $this->assertEquals($note1->id, $note2->id);
    }

    // -------------------------------------------------------------------------
    // Update note
    // -------------------------------------------------------------------------

    public function test_update_note_saves_soap_fields(): void
    {
        $data = $this->seedEncounter();
        $note = $this->svc->getOrCreateNote($data->encounter_id, 1);

        $updated = $this->svc->updateNote($note, [
            'subjective' => 'Patient complains of headache',
            'objective'  => 'BP 140/90',
            'assessment' => 'Hypertension',
            'plan'       => 'Start amlodipine 5mg',
        ]);

        $this->assertEquals('Patient complains of headache', $updated->subjective);
        $this->assertEquals('Hypertension', $updated->assessment);
    }

    public function test_update_note_blocked_when_finalized(): void
    {
        $data = $this->seedEncounter();
        $note = $this->svc->getOrCreateNote($data->encounter_id, 1);
        $this->svc->updateNote($note, ['subjective' => 'Test', 'plan' => 'Rest']);
        $this->svc->finalizeNote($note->fresh(), 1);

        $this->expectException(\LogicException::class);
        $this->svc->updateNote($note->fresh(), ['subjective' => 'Changed']);
    }

    // -------------------------------------------------------------------------
    // Finalization
    // -------------------------------------------------------------------------

    public function test_finalize_note_sets_hash_and_lock(): void
    {
        $data = $this->seedEncounter();
        $note = $this->svc->getOrCreateNote($data->encounter_id, 1);
        $this->svc->updateNote($note, ['subjective' => 'Headache', 'plan' => 'Paracetamol']);
        $note = $note->fresh();

        $finalized = $this->svc->finalizeNote($note, 1);

        $this->assertTrue($finalized->isFinalized());
        $this->assertNotNull($finalized->content_hash);
        $this->assertEquals(64, strlen($finalized->content_hash)); // sha256 hex
        $this->assertEquals(1, $finalized->finalized_by);
    }

    public function test_finalize_stores_correct_hash(): void
    {
        $data = $this->seedEncounter();
        $note = $this->svc->getOrCreateNote($data->encounter_id, 1);
        $this->svc->updateNote($note, ['subjective' => 'Cough', 'plan' => 'Antibiotics']);
        $note = $note->fresh();

        $expected = $note->computeContentHash();
        $finalized = $this->svc->finalizeNote($note, 1);

        $this->assertEquals($expected, $finalized->content_hash);
    }

    public function test_finalize_blocked_when_already_finalized(): void
    {
        $data = $this->seedEncounter();
        $note = $this->svc->getOrCreateNote($data->encounter_id, 1);
        $this->svc->updateNote($note, ['subjective' => 'Test', 'plan' => 'Rest']);
        $this->svc->finalizeNote($note->fresh(), 1);

        $this->expectException(\LogicException::class);
        $this->svc->finalizeNote($note->fresh(), 1);
    }

    public function test_finalize_blocked_when_note_empty(): void
    {
        $data = $this->seedEncounter();
        $note = $this->svc->getOrCreateNote($data->encounter_id, 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->svc->finalizeNote($note, 1);
    }

    // -------------------------------------------------------------------------
    // Hash integrity
    // -------------------------------------------------------------------------

    public function test_content_hash_changes_when_content_changes(): void
    {
        $data  = $this->seedEncounter();
        $note  = $this->svc->getOrCreateNote($data->encounter_id, 1);
        $this->svc->updateNote($note, ['subjective' => 'Version 1']);
        $hash1 = $note->fresh()->computeContentHash();

        $this->svc->updateNote($note->fresh(), ['subjective' => 'Version 2']);
        $hash2 = $note->fresh()->computeContentHash();

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_content_hash_same_when_content_unchanged(): void
    {
        $data = $this->seedEncounter();
        $note = $this->svc->getOrCreateNote($data->encounter_id, 1);
        $this->svc->updateNote($note, ['subjective' => 'Same content', 'plan' => 'Same plan']);
        $note = $note->fresh();

        $h1 = $note->computeContentHash();
        $h2 = $note->computeContentHash();

        $this->assertEquals($h1, $h2);
    }

    // -------------------------------------------------------------------------
    // Diagnoses
    // -------------------------------------------------------------------------

    public function test_add_diagnosis_creates_record(): void
    {
        $data = $this->seedEncounter();

        $diagnosis = $this->svc->addDiagnosis(
            encounterId:   $data->encounter_id,
            patientId:     $data->patient_id,
            diagnosedBy:   1,
            diagnosisType: 'primary',
            certainty:     'confirmed',
            diagnosisText: 'Essential hypertension',
            icd10Code:     'I10',
        );

        $this->assertInstanceOf(Diagnosis::class, $diagnosis);
        $this->assertEquals('I10', $diagnosis->icd10_code);
        $this->assertEquals('primary', $diagnosis->diagnosis_type);
        $this->assertEquals('confirmed', $diagnosis->certainty);
    }

    public function test_remove_diagnosis_deletes_record(): void
    {
        $data = $this->seedEncounter();

        $diagnosis = $this->svc->addDiagnosis(
            encounterId:   $data->encounter_id,
            patientId:     $data->patient_id,
            diagnosedBy:   1,
            diagnosisType: 'secondary',
            certainty:     'probable',
            diagnosisText: 'Type 2 Diabetes',
        );

        $this->svc->removeDiagnosis($diagnosis);

        $this->assertNull(Diagnosis::on('tenant')->find($diagnosis->id));
    }

    public function test_add_diagnosis_resolves_icd_code_from_id(): void
    {
        $data = $this->seedEncounter();

        $icdId = DB::connection('tenant')->table('icd10_codes')->insertGetId([
            'code'        => 'E11.9',
            'description' => 'Type 2 diabetes mellitus without complications',
            'is_active'   => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $diagnosis = $this->svc->addDiagnosis(
            encounterId:   $data->encounter_id,
            patientId:     $data->patient_id,
            diagnosedBy:   1,
            diagnosisType: 'primary',
            certainty:     'confirmed',
            diagnosisText: 'Type 2 diabetes mellitus without complications',
            icd10CodeId:   $icdId,
        );

        $this->assertEquals('E11.9', $diagnosis->icd10_code);
        $this->assertEquals($icdId, $diagnosis->icd10_code_id);
    }
}
