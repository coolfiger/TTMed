<?php

namespace Tests\Feature\Phase2;

use App\Models\Tenant\StaffProfile;
use PHPUnit\Framework\TestCase;

class StaffModelTest extends TestCase
{
    public function test_full_name_with_title(): void
    {
        $staff = new StaffProfile();
        $staff->forceFill([
            'title'      => 'Dr',
            'first_name' => 'Amara',
            'last_name'  => 'Singh',
        ]);

        $this->assertSame('Dr Amara Singh', $staff->full_name);
    }

    public function test_full_name_without_title(): void
    {
        $staff = new StaffProfile();
        $staff->forceFill([
            'title'      => null,
            'first_name' => 'Kamla',
            'last_name'  => 'Ramsaran',
        ]);

        $this->assertSame('Kamla Ramsaran', $staff->full_name);
    }

    public function test_full_name_trims_extra_spaces(): void
    {
        $staff = new StaffProfile();
        $staff->forceFill([
            'title'      => '',
            'first_name' => 'John',
            'last_name'  => 'Peters',
        ]);

        $this->assertSame('John Peters', $staff->full_name);
    }
}
