<?php

namespace Tests\Feature\Phase4;

use App\Services\QueueNumberService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueNumberServiceTest extends TestCase
{
    private const BRANCH_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS queue_number_sequences (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            branch_id INTEGER NOT NULL,
            queue_date DATE NOT NULL,
            last_number INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMP,
            updated_at TIMESTAMP,
            UNIQUE(branch_id, queue_date)
        )');
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS queue_number_sequences');
        parent::tearDown();
    }

    public function test_first_number_for_branch_is_one(): void
    {
        $svc = app(QueueNumberService::class);
        $n   = $svc->next(self::BRANCH_ID, '2025-01-15');

        $this->assertSame(1, $n);
    }

    public function test_numbers_are_sequential_for_same_branch_and_date(): void
    {
        $svc = app(QueueNumberService::class);

        $a = $svc->next(self::BRANCH_ID, '2025-01-15');
        $b = $svc->next(self::BRANCH_ID, '2025-01-15');
        $c = $svc->next(self::BRANCH_ID, '2025-01-15');

        $this->assertSame(1, $a);
        $this->assertSame(2, $b);
        $this->assertSame(3, $c);
    }

    public function test_different_dates_start_at_one(): void
    {
        $svc = app(QueueNumberService::class);

        $day1 = $svc->next(self::BRANCH_ID, '2025-01-15');
        $day2 = $svc->next(self::BRANCH_ID, '2025-01-16');

        $this->assertSame(1, $day1);
        $this->assertSame(1, $day2);
    }

    public function test_different_branches_have_independent_sequences(): void
    {
        $svc = app(QueueNumberService::class);

        $b1 = $svc->next(1, '2025-01-15');
        $b2 = $svc->next(2, '2025-01-15');
        $b1b = $svc->next(1, '2025-01-15');

        $this->assertSame(1, $b1);
        $this->assertSame(1, $b2);
        $this->assertSame(2, $b1b);
    }

    public function test_current_max_returns_zero_before_any_check_in(): void
    {
        $svc = app(QueueNumberService::class);
        $this->assertSame(0, $svc->currentMax(self::BRANCH_ID, '2025-01-15'));
    }

    public function test_current_max_returns_last_issued(): void
    {
        $svc = app(QueueNumberService::class);
        $svc->next(self::BRANCH_ID, '2025-01-15');
        $svc->next(self::BRANCH_ID, '2025-01-15');
        $svc->next(self::BRANCH_ID, '2025-01-15');

        $this->assertSame(3, $svc->currentMax(self::BRANCH_ID, '2025-01-15'));
    }

    public function test_numbers_are_unique_in_bulk(): void
    {
        $svc       = app(QueueNumberService::class);
        $generated = [];

        for ($i = 0; $i < 20; $i++) {
            $generated[] = $svc->next(self::BRANCH_ID, '2025-01-15');
        }

        $this->assertCount(20, array_unique($generated));
    }
}
