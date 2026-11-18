<div class="p-6 space-y-6">

    @if($successMessage)
        <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm">{{ $errorMessage }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h2 class="font-semibold text-gray-800">Inventory</h2>
            @if($lowStockCount > 0)
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    {{ $lowStockCount }} low stock
                </span>
            @endif
        </div>
        <button wire:click="openCreateItem"
            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
            + Add Item
        </button>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3 items-center">
        <input type="text" wire:model.live.debounce.300ms="search"
            placeholder="Search name or code..."
            class="border-gray-300 rounded-md shadow-sm text-sm flex-1 max-w-xs" />
        <select wire:model.live="filterCategory" class="border-gray-300 rounded-md shadow-sm text-sm">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model.live="filterLowStock" class="rounded border-gray-300" />
            Low stock only
        </label>
    </div>

    <div class="flex gap-6">
        {{-- Items table --}}
        <div class="flex-1 min-w-0">
            <div class="bg-white border rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-medium">Item</th>
                            <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-medium">Category</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase font-medium">On Hand</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase font-medium">Reorder At</th>
                            <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase font-medium">Unit Cost</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($items as $item)
                            <tr class="{{ $item->isLowStock() ? 'bg-orange-50' : '' }} hover:bg-gray-50 cursor-pointer"
                                wire:click="selectItem({{ $item->id }})">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                    <div class="text-gray-400 text-xs">{{ $item->code }}</div>
                                </td>
                                <td class="px-4 py-2 text-gray-600">{{ $item->category?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-right">
                                    <span class="{{ $item->isOutOfStock() ? 'text-red-700 font-semibold' : ($item->isLowStock() ? 'text-orange-700 font-medium' : 'text-gray-800') }}">
                                        {{ $item->quantity_on_hand }}
                                    </span>
                                    <span class="text-gray-400 text-xs ml-1">{{ $item->unit }}</span>
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600">{{ $item->reorder_level }}</td>
                                <td class="px-4 py-2 text-right text-gray-600">{{ $item->unit_cost_formatted }}</td>
                                <td class="px-4 py-2 text-right">
                                    <button wire:click.stop="editItem({{ $item->id }})"
                                        class="text-gray-400 hover:text-blue-600 text-xs">Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400 italic text-sm">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->links() }}</div>
        </div>

        {{-- Movement panel --}}
        @if($activeItem)
            <div class="w-80 shrink-0 space-y-4">
                <div class="bg-white border rounded-lg p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ $activeItem->name }}</h3>
                        <button wire:click="$set('activeItemId', null)" class="text-gray-400 hover:text-gray-600 text-xs">✕</button>
                    </div>
                    <div class="text-xs text-gray-500 space-y-1">
                        <div>On hand: <span class="font-semibold text-gray-900">{{ $activeItem->quantity_on_hand }} {{ $activeItem->unit }}</span></div>
                        <div>Reorder at: {{ $activeItem->reorder_level }} | Reorder qty: {{ $activeItem->reorder_quantity }}</div>
                        @if($activeItem->supplier)
                            <div>Supplier: {{ $activeItem->supplier }}</div>
                        @endif
                    </div>

                    <div class="border-t pt-3 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Movement Type</label>
                            <select wire:model.live="moveType" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="receive">Receive Stock</option>
                                <option value="dispense">Dispense</option>
                                <option value="adjust">Adjust to Quantity</option>
                                <option value="write_off">Write Off</option>
                                <option value="return">Return</option>
                            </select>
                        </div>

                        @if($moveType === 'adjust')
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">New Quantity</label>
                                <input type="number" wire:model="adjustTarget" min="0"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Quantity</label>
                                <input type="number" wire:model="moveQuantity" min="1"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                            </div>
                        @endif

                        @if(!in_array($moveType, ['write_off']))
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Reference</label>
                                <input type="text" wire:model="moveReference"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                {{ $moveType === 'write_off' ? 'Reason *' : 'Notes' }}
                            </label>
                            <input type="text" wire:model="moveNotes"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        </div>

                        <button wire:click="applyMovement"
                            class="w-full py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
                            Apply Movement
                        </button>
                    </div>
                </div>

                {{-- Recent transactions --}}
                @if($recentTransactions->isNotEmpty())
                    <div class="bg-white border rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Recent Transactions</h4>
                        <div class="space-y-1">
                            @foreach($recentTransactions as $txn)
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-700">{{ $txn->type_label }}</span>
                                    <span class="{{ $txn->quantity > 0 ? 'text-green-700' : 'text-red-700' }} font-medium">
                                        {{ $txn->quantity > 0 ? '+' : '' }}{{ $txn->quantity }}
                                    </span>
                                    <span class="text-gray-400">{{ $txn->quantity_after }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Item create/edit modal --}}
    @if($showItemForm)
        <div class="fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ $editItemId ? 'Edit Item' : 'New Item' }}</h3>
                    <button wire:click="$set('showItemForm', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" wire:model="itemName"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        @error('itemName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                        <input type="text" wire:model="itemCode"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        @error('itemCode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select wire:model="itemCategoryId" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="0">— None —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit *</label>
                        <input type="text" wire:model="itemUnit"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level *</label>
                        <input type="number" wire:model="itemReorderLevel" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Qty *</label>
                        <input type="number" wire:model="itemReorderQty" min="1"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost (cents)</label>
                        <input type="number" wire:model="itemUnitCost" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                        <input type="text" wire:model="itemSupplier"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button wire:click="saveItem"
                        class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
                        Save
                    </button>
                    <button wire:click="$set('showItemForm', false)"
                        class="px-5 py-2 bg-gray-100 text-gray-700 text-sm rounded hover:bg-gray-200">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
