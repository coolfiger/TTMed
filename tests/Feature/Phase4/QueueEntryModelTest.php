<?php

namespace Tests\Feature\Phase4;

use App\Models\Tenant\QueueEntry;
use PHPUnit\Framework\TestCase;

class QueueEntryModelTest extends TestCase
{
    private function makeEntry(string $status): QueueEntry
    {
        $entry = new QueueEntry();
        $entry->forceFill(['status' => $status, 'is_priority' => false]);
        return $entry;
    }

    public function test_status_label_waiting(): void
    {
        $entry = $this->makeEntry('waiting');
        $this->assertSame('Waiting', $entry->status_label);
    }

    public function test_status_label_with_doctor(): void
    {
        $entry = $this->makeEntry('with_doctor');
        $this->assertSame('With Doctor', $entry->status_label);
    }

    public function test_status_label_no_show(): void
    {
        $entry = $this->makeEntry('no_show');
        $this->assertSame('No Show', $entry->status_label);
    }

    public function test_valid_transition_waiting_to_with_nurse(): void
    {
        $entry = $this->makeEntry('waiting');
        $this->assertTrue($entry->canTransitionTo('with_nurse'));
    }

    public function test_valid_transition_waiting_to_awaiting_doctor(): void
    {
        $entry = $this->makeEntry('waiting');
        $this->assertTrue($entry->canTransitionTo('awaiting_doctor'));
    }

    public function test_valid_transition_waiting_to_cancelled(): void
    {
        $entry = $this->makeEntry('waiting');
        $this->assertTrue($entry->canTransitionTo('cancelled'));
    }

    public function test_valid_transition_awaiting_doctor_to_with_doctor(): void
    {
        $entry = $this->makeEntry('awaiting_doctor');
        $this->assertTrue($entry->canTransitionTo('with_doctor'));
    }

    public function test_valid_transition_with_doctor_to_completed(): void
    {
        $entry = $this->makeEntry('with_doctor');
        $this->assertTrue($entry->canTransitionTo('completed'));
    }

    public function test_invalid_transition_completed_to_waiting(): void
    {
        $entry = $this->makeEntry('completed');
        $this->assertFalse($entry->canTransitionTo('waiting'));
    }

    public function test_invalid_transition_cancelled_to_waiting(): void
    {
        $entry = $this->makeEntry('cancelled');
        $this->assertFalse($entry->canTransitionTo('waiting'));
    }

    public function test_invalid_transition_no_show_to_with_doctor(): void
    {
        $entry = $this->makeEntry('no_show');
        $this->assertFalse($entry->canTransitionTo('with_doctor'));
    }

    public function test_invalid_transition_waiting_to_completed(): void
    {
        $entry = $this->makeEntry('waiting');
        $this->assertFalse($entry->canTransitionTo('completed'));
    }

    public function test_status_color_waiting_is_yellow(): void
    {
        $entry = $this->makeEntry('waiting');
        $this->assertSame('yellow', $entry->status_color);
    }

    public function test_status_color_completed_is_gray(): void
    {
        $entry = $this->makeEntry('completed');
        $this->assertSame('gray', $entry->status_color);
    }

    public function test_status_color_cancelled_is_red(): void
    {
        $entry = $this->makeEntry('cancelled');
        $this->assertSame('red', $entry->status_color);
    }
}
