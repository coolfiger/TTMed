<div class="max-w-xl">
    <form wire:submit="save" class="space-y-5 bg-white rounded-lg border border-slate-200 p-6">
        <div class="grid grid-cols-2 gap-5">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Service Name</label>
                <input wire:model="name" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Code</label>
                <input wire:model="code" type="text" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm font-mono uppercase focus:border-blue-500 focus:outline-none @error('code') border-red-500 @enderror">
                @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Category</label>
                <select wire:model="categoryId"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                    <option value="">No category</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Description</label>
                <textarea wire:model="description" rows="2"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Price ($)</label>
                <input wire:model="priceInput" type="number" step="0.01" min="0"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('priceInput') border-red-500 @enderror">
                @error('priceInput') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Cost ($)</label>
                <input wire:model="costInput" type="number" step="0.01" min="0"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div class="flex items-center gap-2">
                <input wire:model="taxable" id="taxable" type="checkbox" class="rounded border-slate-300 text-blue-600">
                <label for="taxable" class="text-sm text-slate-700">Taxable</label>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tax Rate (%)</label>
                <input wire:model="taxRate" type="number" step="0.01" min="0" max="100"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('taxRate') border-red-500 @enderror">
                @error('taxRate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-2">
                <input wire:model="isActive" id="isActive" type="checkbox" class="rounded border-slate-300 text-blue-600">
                <label for="isActive" class="text-sm text-slate-700">Active</label>
            </div>
            <div class="flex items-center gap-2">
                <input wire:model="isDefault" id="isDefault" type="checkbox" class="rounded border-slate-300 text-blue-600">
                <label for="isDefault" class="text-sm text-slate-700">Default service</label>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                {{ $service ? 'Update Service' : 'Create Service' }}
            </button>
            <a href="{{ route('tenant.services.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
    </form>
</div>
