<?php

namespace App\Services;

use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\InventoryTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class InventoryService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    // =========================================================================
    // Stock movements
    // =========================================================================

    public function receiveStock(
        int     $itemId,
        int     $branchId,
        int     $quantity,
        int     $performedBy,
        ?string $reference = null,
        ?string $notes     = null,
    ): InventoryTransaction {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Receive quantity must be at least 1.');
        }

        return $this->applyTransaction($itemId, $branchId, 'receive', $quantity, $performedBy, $reference, $notes);
    }

    public function dispenseStock(
        int     $itemId,
        int     $branchId,
        int     $quantity,
        int     $performedBy,
        ?string $reference = null,
        ?string $notes     = null,
    ): InventoryTransaction {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Dispense quantity must be at least 1.');
        }

        return $this->applyTransaction($itemId, $branchId, 'dispense', -$quantity, $performedBy, $reference, $notes);
    }

    public function adjustStock(
        int     $itemId,
        int     $branchId,
        int     $newQuantity,
        int     $performedBy,
        ?string $notes = null,
    ): InventoryTransaction {
        if ($newQuantity < 0) {
            throw new InvalidArgumentException('Adjusted quantity cannot be negative.');
        }

        return DB::connection('tenant')->transaction(function () use (
            $itemId, $branchId, $newQuantity, $performedBy, $notes
        ) {
            $item = InventoryItem::on('tenant')->lockForUpdate()->findOrFail($itemId);

            $delta = $newQuantity - $item->quantity_on_hand;

            $txn = InventoryTransaction::on('tenant')->create([
                'inventory_item_id' => $item->id,
                'branch_id'         => $branchId,
                'transaction_type'  => 'adjust',
                'quantity'          => $delta,
                'quantity_before'   => $item->quantity_on_hand,
                'quantity_after'    => $newQuantity,
                'notes'             => $notes,
                'performed_by'      => $performedBy,
            ]);

            $item->quantity_on_hand = $newQuantity;
            $item->save();

            $this->auditService->logTenant(
                'inventory.adjusted',
                'inventory_item',
                $item->id,
                null,
                ['quantity' => $newQuantity],
                "Inventory adjusted for {$item->name}: {$item->quantity_on_hand} → {$newQuantity}",
            );

            return $txn;
        });
    }

    public function writeOff(
        int     $itemId,
        int     $branchId,
        int     $quantity,
        int     $performedBy,
        string  $reason,
    ): InventoryTransaction {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Write-off quantity must be at least 1.');
        }

        return $this->applyTransaction($itemId, $branchId, 'write_off', -$quantity, $performedBy, null, $reason);
    }

    public function returnStock(
        int     $itemId,
        int     $branchId,
        int     $quantity,
        int     $performedBy,
        ?string $reference = null,
        ?string $notes     = null,
    ): InventoryTransaction {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Return quantity must be at least 1.');
        }

        return $this->applyTransaction($itemId, $branchId, 'return', $quantity, $performedBy, $reference, $notes);
    }

    // =========================================================================
    // Queries
    // =========================================================================

    public function getLowStockItems(int $branchId): Collection
    {
        return InventoryItem::on('tenant')
            ->active()
            ->lowStock()
            ->orderBy('quantity_on_hand')
            ->get();
    }

    public function getTransactionHistory(int $itemId, int $limit = 50): Collection
    {
        return InventoryTransaction::on('tenant')
            ->where('inventory_item_id', $itemId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // =========================================================================
    // Private
    // =========================================================================

    private function applyTransaction(
        int     $itemId,
        int     $branchId,
        string  $type,
        int     $delta,          // positive = in, negative = out
        int     $performedBy,
        ?string $reference,
        ?string $notes,
    ): InventoryTransaction {
        return DB::connection('tenant')->transaction(function () use (
            $itemId, $branchId, $type, $delta, $performedBy, $reference, $notes
        ) {
            $item = InventoryItem::on('tenant')->lockForUpdate()->findOrFail($itemId);

            $newQty = $item->quantity_on_hand + $delta;

            if ($newQty < 0) {
                throw new LogicException(
                    "Insufficient stock for {$item->name}. Available: {$item->quantity_on_hand}, requested: " . abs($delta) . '.'
                );
            }

            $txn = InventoryTransaction::on('tenant')->create([
                'inventory_item_id' => $item->id,
                'branch_id'         => $branchId,
                'transaction_type'  => $type,
                'quantity'          => $delta,
                'quantity_before'   => $item->quantity_on_hand,
                'quantity_after'    => $newQty,
                'reference'         => $reference,
                'notes'             => $notes,
                'performed_by'      => $performedBy,
            ]);

            $item->quantity_on_hand = $newQty;
            $item->save();

            return $txn;
        });
    }
}
