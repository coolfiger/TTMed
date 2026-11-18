<?php

namespace Tests\Feature\Phase2;

use App\Models\Tenant\Service;
use App\Models\Tenant\ServiceCategory;
use PHPUnit\Framework\TestCase;

class ServiceModelTest extends TestCase
{
    public function test_price_formatted_converts_cents_to_dollars(): void
    {
        $service = new Service();
        $service->forceFill(['price' => 15000]);

        $this->assertSame('150.00', $service->price_formatted);
    }

    public function test_price_formatted_handles_zero(): void
    {
        $service = new Service();
        $service->forceFill(['price' => 0]);

        $this->assertSame('0.00', $service->price_formatted);
    }

    public function test_price_with_tax_applies_rate(): void
    {
        $service = new Service();
        $service->forceFill(['price' => 10000, 'tax_rate' => 12.5, 'taxable' => true]);

        $this->assertSame(11250, $service->price_with_tax);
    }

    public function test_price_with_tax_returns_base_when_not_taxable(): void
    {
        $service = new Service();
        $service->forceFill(['price' => 10000, 'tax_rate' => 12.5, 'taxable' => false]);

        $this->assertSame(10000, $service->price_with_tax);
    }

    public function test_price_cents_cast_as_integer(): void
    {
        $service = new Service();
        $service->forceFill(['price' => '34900', 'cost' => '10000']);

        $this->assertIsInt($service->price);
        $this->assertIsInt($service->cost);
    }
}
