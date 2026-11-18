<?php

namespace Tests\Feature\Phase3;

use App\Models\Tenant\Patient;
use App\Services\PatientDuplicateDetector;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientDuplicateDetectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS patients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_number VARCHAR(30) NOT NULL,
            title VARCHAR(10),
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
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
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patients');
        parent::tearDown();
    }

    private function createPatient(array $attrs): Patient
    {
        $defaults = [
            'patient_number' => 'PT-000001',
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        $id = DB::connection('tenant')
            ->table('patients')
            ->insertGetId(array_merge($defaults, $attrs));

        return Patient::on('tenant')->find($id);
    }

    public function test_finds_duplicate_by_last_name_and_dob(): void
    {
        $this->createPatient([
            'patient_number' => 'PT-000001',
            'first_name'     => 'Mary',
            'last_name'      => 'Joseph',
            'date_of_birth'  => '1985-03-15',
        ]);

        $detector = new PatientDuplicateDetector();
        $found = $detector->findPotentialDuplicates('Maria', 'Joseph', '1985-03-15');

        $this->assertTrue($found->isNotEmpty());
    }

    public function test_finds_duplicate_by_phone(): void
    {
        $this->createPatient([
            'patient_number' => 'PT-000001',
            'first_name'     => 'Peter',
            'last_name'      => 'Brown',
            'phone_primary'  => '868-555-1234',
        ]);

        $detector = new PatientDuplicateDetector();
        $found = $detector->findPotentialDuplicates('Peter', 'Brown', null, '868-555-1234');

        $this->assertTrue($found->isNotEmpty());
    }

    public function test_no_duplicate_when_different_name_and_dob(): void
    {
        $this->createPatient([
            'patient_number' => 'PT-000001',
            'first_name'     => 'Alice',
            'last_name'      => 'Smith',
            'date_of_birth'  => '1990-01-01',
        ]);

        $detector = new PatientDuplicateDetector();
        $found = $detector->findPotentialDuplicates('Bob', 'Jones', '1985-05-20');

        $this->assertTrue($found->isEmpty());
    }

    public function test_excludes_self_on_edit(): void
    {
        $patient = $this->createPatient([
            'patient_number' => 'PT-000001',
            'first_name'     => 'Grace',
            'last_name'      => 'Ali',
            'date_of_birth'  => '1975-08-10',
        ]);

        $detector = new PatientDuplicateDetector();
        $found = $detector->findPotentialDuplicates('Grace', 'Ali', '1975-08-10', null, $patient->id);

        $this->assertTrue($found->isEmpty());
    }
}
