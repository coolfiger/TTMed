<?php

namespace Tests\Feature\Phase10;

use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\InventoryTransaction;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use LogicException;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    private InventoryService $svc;
    private int $branchId    = 1;
    private int $userId      = 1;

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                code TEXT UNIQUE,
                is_active INTEGER DEFAULT 1,
                sort_order INTEGER DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER,
                name TEXT,
                code TEXT UNIQUE,
                description TEXT,
                unit TEXT DEFAULT "unit",
                quantity_on_hand INTEGER DEFAULT 0,
                reorder_level INTEGER DEFAULT 10,
                reorder_quantity INTEGER DEFAULT 50,
                unit_cost INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                track_expiry INTEGER DEFAULT 0,
                expiry_date DATE,
                supplier TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        DB::connection('tenant')->statement('
            CREATE TABLE IF NOT EXISTS inventory_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                inventory_item_id INTEGER,
                branch_id INTEGER,
                transaction_type TEXT,
                quantity INTEGER,
                quantity_before INTEGER,
                quantity_after INTEGER,
                reference TEXT,
                notes TEXT,
                performed_by INTEGER,
                created_at DATETIME,
                updated_at DATETIME
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

        $audit = $this->createMock(AuditService::class);
        $audit->method('logTenant')->willReturnCallback(fn () => null);

        $this->svc = new InventoryService($audit);
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_transactions');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_items');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS inventory_categories');
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS audit_logs');
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeItem(int $quantity = 20, int $reorderLevel = 5): InventoryItem
    {
        $id = DB::connection('tenant')->table('inventory_items')->insertGetId([
            'name'             => 'Paracetamol 500mg',
            'code'             => 'MED-' . uniqid(),
            'unit'             => 'tablet',
            'quantity_on_hand' => $quantity,
            'reorder_level'    => $reorderLevel,
            'reorder_quantity' => 100,
            'unit_cost'        => 50,
            'is_active'        => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return InventoryItem::on('tenant')->findOrFail($id);
    }

    // -------------------------------------------------------------------------
    // receiveStock
    // -------------------------------------------------------------------------

    public function test_receive_stock_increases_quantity(): void
    {
        $item = $this->makeItem(20);

        $txn = $this->svc->receiveStock($item->id, $this->branchId, 30, $this->userId, 'PO-001', 'Monthly restock');

        $this->assertInstanceOf(InventoryTransaction::class, $txn);
        $this->assertEquals('receive', $txn->transaction_type);
        $this->assertEquals(30, $txn->quantity);
        $this->assertEquals(20, $txn->quantity_before);
        $this->assertEquals(50, $txn->quantity_after);

        $item->refresh();
        $this->assertEquals(50, $item->quantity_on_hand);
    }

    public function test_receive_stock_rejects_zero_quantity(): void
    {
        $item = $this->makeItem(10);

        $this->expectException(InvalidArgumentException::class);
        $this->svc->receiveStock($item->id, $this->branchId, 0, $this->userId);
    }

    // -------------------------------------------------------------------------
    // dispenseStock
    // -------------------------------------------------------------------------

    public function test_dispense_stock_decreases_quantity(): void
    {
        $item = $this->makeItem(50);

        $txn = $this->svc->dispenseStock($item->id, $this->branchId, 10, $this->userId, 'RX-001');

        $this->assertEquals('dispense', $txn->transaction_type);
        $this->assertEquals(-10, $txn->quantity);
        $this->assertEquals(50, $txn->quantity_before);
        $this->assertEquals(40, $txn->quantity_after);

        $item->refresh();
        $this->assertEquals(40, $item->quantity_on_hand);
    }

    public function test_dispense_cannot_go_below_zero(): void
    {
        $item = $this->makeItem(5);

        $this->expectException(LogicException::class);
        $this->svc->dispenseStock($item->id, $this->branchId, 10, $this->userId);
    }

    public function test_dispense_exact_stock_to_zero(): void
    {
        $item = $this->makeItem(5);

        $txn = $this->svc->dispenseStock($item->id, $this->branchId, 5, $this->userId);

        $this->assertEquals(0, $txn->quantity_after);
        $item->refresh();
        $this->assertEquals(0, $item->quantity_on_hand);
    }

    // -------------------------------------------------------------------------
    // adjustStock
    // -------------------------------------------------------------------------

    public function test_adjust_stock_up(): void
    {
        $item = $this->makeItem(10);

        $txn = $this->svc->adjustStock($item->id, $this->branchId, 25, $this->userId, 'Physical count');

        $this->assertEquals('adjust', $txn->transaction_type);
        $this->assertEquals(15, $txn->quantity);  // delta
        $this->assertEquals(10, $txn->quantity_before);
        $this->assertEquals(25, $txn->quantity_after);

        $item->refresh();
        $this->assertEquals(25, $item->quantity_on_hand);
    }

    public function test_adjust_stock_down(): void
    {
        $item = $this->makeItem(30);

        $txn = $this->svc->adjustStock($item->id, $this->branchId, 20, $this->userId);

        $this->assertEquals(-10, $txn->quantity);
        $this->assertEquals(20, $txn->quantity_after);

        $item->refresh();
        $this->assertEquals(20, $item->quantity_on_hand);
    }

    public function test_adjust_to_zero_allowed(): void
    {
        $item = $this->makeItem(15);

        $txn = $this->svc->adjustStock($item->id, $this->branchId, 0, $this->userId);

        $this->assertEquals(0, $txn->quantity_after);
    }

    public function test_adjust_to_negative_rejected(): void
    {
        $item = $this->makeItem(10);

        $this->expectException(InvalidArgumentException::class);
        $this->svc->adjustStock($item->id, $this->branchId, -1, $this->userId);
    }

    // -------------------------------------------------------------------------
    // writeOff
    // -------------------------------------------------------------------------

    public function test_write_off_reduces_stock(): void
    {
        $item = $this->makeItem(20);

        $txn = $this->svc->writeOff($item->id, $this->branchId, 3, $this->userId, 'Expired stock');

        $this->assertEquals('write_off', $txn->transaction_type);
        $this->assertEquals(-3, $txn->quantity);
        $this->assertEquals(17, $txn->quantity_after);

        $item->refresh();
        $this->assertEquals(17, $item->quantity_on_hand);
    }

    public function test_write_off_cannot_exceed_stock(): void
    {
        $item = $this->makeItem(5);

        $this->expectException(LogicException::class);
        $this->svc->writeOff($item->id, $this->branchId, 10, $this->userId, 'Damaged');
    }

    // -------------------------------------------------------------------------
    // returnStock
    // -------------------------------------------------------------------------

    public function test_return_increases_stock(): void
    {
        $item = $this->makeItem(10);

        $txn = $this->svc->returnStock($item->id, $this->branchId, 2, $this->userId, 'RX-002');

        $this->assertEquals('return', $txn->transaction_type);
        $this->assertEquals(2, $txn->quantity);
        $this->assertEquals(12, $txn->quantity_after);

        $item->refresh();
        $this->assertEquals(12, $item->quantity_on_hand);
    }

    // -------------------------------------------------------------------------
    // getLowStockItems
    // -------------------------------------------------------------------------

    public function test_get_low_stock_items_returns_items_at_or_below_reorder_level(): void
    {
        $this->makeItem(20, 5);    // not low: 20 > 5
        $this->makeItem(5, 5);     // low: 5 <= 5
        $this->makeItem(3, 10);    // low: 3 <= 10
        $this->makeItem(0, 10);    // out of stock: 0 <= 10

        $low = $this->svc->getLowStockItems($this->branchId);

        $this->assertCount(3, $low);
    }

    // -------------------------------------------------------------------------
    // getTransactionHistory
    // -------------------------------------------------------------------------

    public function test_get_transaction_history_returns_latest_first(): void
    {
        $item = $this->makeItem(100);

        $this->svc->receiveStock($item->id, $this->branchId, 10, $this->userId);
        $this->svc->dispenseStock($item->id, $this->branchId, 5, $this->userId);
        $this->svc->receiveStock($item->id, $this->branchId, 20, $this->userId);

        $history = $this->svc->getTransactionHistory($item->id);

        $this->assertCount(3, $history);
        // Most recent first — last action was receive
        $this->assertEquals('receive', $history->first()->transaction_type);
    }

    public function test_get_transaction_history_respects_limit(): void
    {
        $item = $this->makeItem(1000);

        for ($i = 0; $i < 10; $i++) {
            $this->svc->receiveStock($item->id, $this->branchId, 1, $this->userId);
        }

        $history = $this->svc->getTransactionHistory($item->id, 5);
        $this->assertCount(5, $history);
    }

    // -------------------------------------------------------------------------
    // InventoryItem model helpers
    // -------------------------------------------------------------------------

    public function test_is_low_stock(): void
    {
        $item = $this->makeItem(5, 10);  // 5 <= 10 → low
        $this->assertTrue($item->isLowStock());

        $item2 = $this->makeItem(15, 10);  // 15 > 10 → not low
        $this->assertFalse($item2->isLowStock());
    }

    public function test_is_out_of_stock(): void
    {
        $item = $this->makeItem(0);
        $this->assertTrue($item->isOutOfStock());

        $item2 = $this->makeItem(1);
        $this->assertFalse($item2->isOutOfStock());
    }

    // -------------------------------------------------------------------------
    // Concurrency: lockForUpdate guards quantity
    // -------------------------------------------------------------------------

    public function test_concurrent_dispense_does_not_produce_negative_stock(): void
    {
        $item = $this->makeItem(10);

        // Both try to dispense 8 from stock of 10 — second should fail
        $this->svc->dispenseStock($item->id, $this->branchId, 8, $this->userId);

        $item->refresh();
        $this->assertEquals(2, $item->quantity_on_hand);

        $this->expectException(LogicException::class);
        $this->svc->dispenseStock($item->id, $this->branchId, 8, $this->userId);
    }
}
