<?php

namespace Tests\Feature\Phase3;

use App\Models\Tenant\Patient;
use PHPUnit\Framework\TestCase;

class PatientModelTest extends TestCase
{
    public function test_full_name_with_all_parts(): void
    {
        $p = new Patient();
        $p->forceFill(['title' => 'Mrs', 'first_name' => 'Grace', 'middle_name' => 'Anne', 'last_name' => 'Williams']);

        $this->assertSame('Mrs Grace Anne Williams', $p->full_name);
    }

    public function test_full_name_without_title_or_middle(): void
    {
        $p = new Patient();
        $p->forceFill(['title' => null, 'first_name' => 'John', 'middle_name' => null, 'last_name' => 'Doe']);

        $this->assertSame('John Doe', $p->full_name);
    }

    public function test_display_name_excludes_title_and_middle(): void
    {
        $p = new Patient();
        $p->forceFill(['title' => 'Dr', 'first_name' => 'Ravi', 'middle_name' => 'Kumar', 'last_name' => 'Singh']);

        $this->assertSame('Ravi Singh', $p->display_name);
    }

    public function test_age_returns_null_when_no_dob(): void
    {
        $p = new Patient();
        $p->forceFill(['date_of_birth' => null]);

        $this->assertNull($p->age);
    }

    public function test_age_calculated_from_dob(): void
    {
        $p = new Patient();
        $p->forceFill(['date_of_birth' => now()->subYears(35)->toDateString()]);

        $this->assertSame(35, $p->age);
    }

    public function test_casts_dates_correctly(): void
    {
        $p = new Patient();
        $p->forceFill([
            'date_of_birth' => '1990-05-15',
            'deceased_date' => null,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $p->date_of_birth);
        $this->assertNull($p->deceased_date);
    }

    public function test_boolean_casts(): void
    {
        $p = new Patient();
        $p->forceFill(['is_active' => 1, 'is_deceased' => 0]);

        $this->assertTrue($p->is_active);
        $this->assertFalse($p->is_deceased);
    }
}
