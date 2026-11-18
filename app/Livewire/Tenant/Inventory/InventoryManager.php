<?php

namespace App\Livewire\Tenant\Inventory;

use App\Models\Tenant\InventoryCategory;
use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\InventoryTransaction;
use App\Services\InventoryService;
use Livewire\Component;

class InventoryManager extends Component
{
    private InventoryService $inventoryService;

    // Filters
    public string $search        = '';
    public string $filterCategory = '';
    public bool   $filterLowStock = false;

    // Selected item for movement panel
    public ?int   $activeItemId  = null;

    // Movement form
    public string $moveType      = 'receive';  // receive|dispense|adjust|write_off|return
    public int    $moveQuantity  = 1;
    public int    $adjustTarget  = 0;          // used for adjust type
    public string $moveReference = '';
    public string $moveNotes     = '';

    // Create/edit item form
    public bool   $showItemForm  = false;
    public ?int   $editItemId    = null;
    public string $itemName      = '';
    public string $itemCode      = '';
    public int    $itemCategoryId = 0;
    public string $itemUnit      = 'unit';
    public int    $itemReorderLevel   = 10;
    public int    $itemReorderQty     = 50;
    public int    $itemUnitCost       = 0;
    public string $itemSupplier       = '';

    public string $successMessage = '';
    public string $errorMessage   = '';

    public function boot(InventoryService $inventoryService): void
    {
        $this->inventoryService = $inventoryService;
    }

    public function mount(): void
    {
        $this->checkPermission('inventory.view');
    }

    // -------------------------------------------------------------------------
    // Item CRUD
    // -------------------------------------------------------------------------

    public function openCreateItem(): void
    {
        $this->checkPermission('inventory.manage');
        $this->resetItemForm();
        $this->showItemForm = true;
    }

    public function editItem(int $itemId): void
    {
        $this->checkPermission('inventory.manage');

        $item = InventoryItem::on('tenant')->findOrFail($itemId);

        $this->editItemId       = $item->id;
        $this->itemName         = $item->name;
        $this->itemCode         = $item->code;
        $this->itemCategoryId   = $item->category_id ?? 0;
        $this->itemUnit         = $item->unit;
        $this->itemReorderLevel = $item->reorder_level;
        $this->itemReorderQty   = $item->reorder_quantity;
        $this->itemUnitCost     = $item->unit_cost;
        $this->itemSupplier     = $item->supplier ?? '';
        $this->showItemForm     = true;
    }

    public function saveItem(): void
    {
        $this->checkPermission('inventory.manage');

        $this->validate([
            'itemName'          => 'required|string|max:200',
            'itemCode'          => 'required|string|max:50',
            'itemUnit'          => 'required|string|max:30',
            'itemReorderLevel'  => 'required|integer|min:0',
            'itemReorderQty'    => 'required|integer|min:1',
            'itemUnitCost'      => 'required|integer|min:0',
        ]);

        $data = [
            'name'             => $this->itemName,
            'code'             => $this->itemCode,
            'category_id'      => $this->itemCategoryId ?: null,
            'unit'             => $this->itemUnit,
            'reorder_level'    => $this->itemReorderLevel,
            'reorder_quantity' => $this->itemReorderQty,
            'unit_cost'        => $this->itemUnitCost,
            'supplier'         => $this->itemSupplier ?: null,
        ];

        if ($this->editItemId) {
            InventoryItem::on('tenant')->findOrFail($this->editItemId)->update($data);
            $this->successMessage = 'Item updated.';
        } else {
            InventoryItem::on('tenant')->create(array_merge($data, ['is_active' => true]));
            $this->successMessage = 'Item created.';
        }

        $this->showItemForm = false;
        $this->resetItemForm();
    }

    // -------------------------------------------------------------------------
    // Stock movements
    // -------------------------------------------------------------------------

    public function selectItem(int $itemId): void
    {
        $this->activeItemId  = $itemId;
        $this->moveType      = 'receive';
        $this->moveQuantity  = 1;
        $this->adjustTarget  = 0;
        $this->moveReference = '';
        $this->moveNotes     = '';
    }

    public function applyMovement(): void
    {
        $this->checkPermission('inventory.manage');

        $user = auth()->guard('tenant')->user();

        $this->validate([
            'moveType'     => 'required|in:receive,dispense,adjust,write_off,return',
            'moveQuantity' => 'required|integer|min:1',
        ]);

        try {
            match ($this->moveType) {
                'receive'   => $this->inventoryService->receiveStock(
                    $this->activeItemId, $user->branch_id ?? 1, $this->moveQuantity,
                    $user->id, $this->moveReference ?: null, $this->moveNotes ?: null,
                ),
                'dispense'  => $this->inventoryService->dispenseStock(
                    $this->activeItemId, $user->branch_id ?? 1, $this->moveQuantity,
                    $user->id, $this->moveReference ?: null, $this->moveNotes ?: null,
                ),
                'adjust'    => $this->inventoryService->adjustStock(
                    $this->activeItemId, $user->branch_id ?? 1, $this->adjustTarget,
                    $user->id, $this->moveNotes ?: null,
                ),
                'write_off' => $this->inventoryService->writeOff(
                    $this->activeItemId, $user->branch_id ?? 1, $this->moveQuantity,
                    $user->id, $this->moveNotes ?: 'Write-off',
                ),
                'return'    => $this->inventoryService->returnStock(
                    $this->activeItemId, $user->branch_id ?? 1, $this->moveQuantity,
                    $user->id, $this->moveReference ?: null, $this->moveNotes ?: null,
                ),
            };

            $this->successMessage = 'Stock movement recorded.';
            $this->activeItemId   = null;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resetItemForm(): void
    {
        $this->editItemId       = null;
        $this->itemName         = '';
        $this->itemCode         = '';
        $this->itemCategoryId   = 0;
        $this->itemUnit         = 'unit';
        $this->itemReorderLevel = 10;
        $this->itemReorderQty   = 50;
        $this->itemUnitCost     = 0;
        $this->itemSupplier     = '';
    }

    private function checkPermission(string $permission): void
    {
        if (!auth()->guard('tenant')->user()?->hasPermission($permission)) {
            abort(403);
        }
    }

    public function render()
    {
        $query = InventoryItem::on('tenant')
            ->with('category')
            ->where('is_active', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterLowStock) {
            $query->lowStock();
        }

        $activeItem = $this->activeItemId
            ? InventoryItem::on('tenant')->find($this->activeItemId)
            : null;

        $recentTransactions = $this->activeItemId
            ? InventoryTransaction::on('tenant')
                ->where('inventory_item_id', $this->activeItemId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
            : collect();

        $categories = InventoryCategory::on('tenant')->where('is_active', true)->get();

        return view('livewire.tenant.inventory.inventory-manager', [
            'items'              => $query->orderBy('name')->paginate(25),
            'activeItem'         => $activeItem,
            'recentTransactions' => $recentTransactions,
            'categories'         => $categories,
            'lowStockCount'      => InventoryItem::on('tenant')->active()->lowStock()->count(),
        ]);
    }
}
