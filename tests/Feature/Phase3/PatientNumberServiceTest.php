<?php

namespace Tests\Feature\Phase3;

use App\Services\PatientNumberService;
use App\Services\CentreSettingsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientNumberServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS patient_number_sequences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            prefix VARCHAR(20) NOT NULL DEFAULT "PT",
            last_number INTEGER NOT NULL DEFAULT 0,
            pad_length INTEGER NOT NULL DEFAULT 6,
            created_at TIMESTAMP,
            updated_at TIMESTAMP
        )');

        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS centre_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            branch_id INTEGER NULL,
            key VARCHAR(100) NOT NULL,
            value TEXT NULL,
            type VARCHAR(20) NOT NULL DEFAULT "string",
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(branch_id, key)
        )');
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS patient_number_sequences');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS centre_settings');
        parent::tearDown();
    }

    public function test_generates_first_number_with_default_format(): void
    {
        $svc = app(PatientNumberService::class);
        $number = $svc->generate();

        $this->assertMatchesRegularExpression('/^PT-\d{6}$/', $number);
        $this->assertSame('PT-000001', $number);
    }

    public function test_numbers_are_sequential(): void
    {
        $svc = app(PatientNumberService::class);

        $first  = $svc->generate();
        $second = $svc->generate();
        $third  = $svc->generate();

        $this->assertSame('PT-000001', $first);
        $this->assertSame('PT-000002', $second);
        $this->assertSame('PT-000003', $third);
    }

    public function test_numbers_never_repeat(): void
    {
        $svc = app(PatientNumberService::class);
        $generated = [];

        for ($i = 0; $i < 10; $i++) {
            $generated[] = $svc->generate();
        }

        $this->assertCount(10, array_unique($generated));
    }

    public function test_configure_changes_prefix(): void
    {
        $svc = app(PatientNumberService::class);
        $svc->configure('MC', 5);

        $number = $svc->generate();

        $this->assertMatchesRegularExpression('/^MC-\d{5}$/', $number);
        $this->assertSame('MC-00001', $number);
    }
}
