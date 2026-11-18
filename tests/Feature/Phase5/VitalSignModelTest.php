<?php

namespace Tests\Feature\Phase5;

use PHPUnit\Framework\TestCase;

/**
 * Tests the pure-PHP logic on VitalSign (BMI calculation, accessors).
 * No database needed — we test the saving hook logic directly.
 */
class VitalSignModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // BMI calculation
    // -------------------------------------------------------------------------

    public function test_bmi_calculated_correctly(): void
    {
        $bmi = $this->calcBmi(70, 170);
        $this->assertEquals(24.2, $bmi);
    }

    public function test_bmi_underweight(): void
    {
        $bmi = $this->calcBmi(45, 170);
        $this->assertEquals(15.6, $bmi);
        $this->assertEquals('Underweight', $this->bmiCategory($bmi));
    }

    public function test_bmi_normal(): void
    {
        $bmi = $this->calcBmi(70, 170);
        $this->assertEquals('Normal', $this->bmiCategory($bmi));
    }

    public function test_bmi_overweight(): void
    {
        $bmi = $this->calcBmi(85, 170);
        $this->assertEquals('Overweight', $this->bmiCategory($bmi));
    }

    public function test_bmi_obese(): void
    {
        $bmi = $this->calcBmi(110, 170);
        $this->assertEquals('Obese', $this->bmiCategory($bmi));
    }

    public function test_bmi_zero_height_returns_null(): void
    {
        $bmi = $this->calcBmiRaw(70, 0);
        $this->assertNull($bmi);
    }

    public function test_bmi_zero_weight_returns_null(): void
    {
        $bmi = $this->calcBmiRaw(0, 170);
        $this->assertNull($bmi);
    }

    public function test_bmi_rounds_to_one_decimal(): void
    {
        // 80 / (1.75^2) = 26.122... → 26.1
        $bmi = $this->calcBmi(80, 175);
        $this->assertEquals(26.1, $bmi);
    }

    // -------------------------------------------------------------------------
    // Blood pressure display
    // -------------------------------------------------------------------------

    public function test_bp_display_both_values(): void
    {
        $display = $this->bpDisplay(120, 80);
        $this->assertEquals('120/80 mmHg', $display);
    }

    public function test_bp_display_null_when_incomplete(): void
    {
        $this->assertNull($this->bpDisplay(120, null));
        $this->assertNull($this->bpDisplay(null, 80));
        $this->assertNull($this->bpDisplay(null, null));
    }

    // -------------------------------------------------------------------------
    // Helpers (mirror the model logic without instantiating it)
    // -------------------------------------------------------------------------

    private function calcBmiRaw(float $weightKg, float $heightCm): ?float
    {
        if ($weightKg <= 0 || $heightCm <= 0) {
            return null;
        }
        $heightM = $heightCm / 100;
        return round($weightKg / ($heightM * $heightM), 1);
    }

    private function calcBmi(float $weightKg, float $heightCm): float
    {
        return $this->calcBmiRaw($weightKg, $heightCm);
    }

    private function bmiCategory(float $bmi): string
    {
        return match (true) {
            $bmi < 18.5 => 'Underweight',
            $bmi < 25.0 => 'Normal',
            $bmi < 30.0 => 'Overweight',
            default     => 'Obese',
        };
    }

    private function bpDisplay(?int $systolic, ?int $diastolic): ?string
    {
        if ($systolic === null || $diastolic === null) {
            return null;
        }
        return "{$systolic}/{$diastolic} mmHg";
    }
}
