<div class="max-w-xl">
    <form wire:submit="save" class="space-y-5 bg-white rounded-lg border border-slate-200 p-6">
        <div class="grid grid-cols-2 gap-5">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Provider Name</label>
                <input wire:model="name" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Code</label>
                <input wire:model="code" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm font-mono uppercase focus:border-blue-500 focus:outline-none @error('code') border-red-500 @enderror">
                @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Contact Name</label>
                <input wire:model="contactName" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Phone</label>
                <input wire:model="phone" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input wire:model="email" type="email"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Address</label>
                <textarea wire:model="address" rows="2"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"></textarea>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Notes</label>
                <textarea wire:model="notes" rows="2"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input wire:model="isActive" id="isActive" type="checkbox" class="rounded border-slate-300 text-blue-600">
                <label for="isActive" class="text-sm text-slate-700">Active</label>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                {{ $provider ? 'Update Provider' : 'Create Provider' }}
            </button>
            <a href="{{ route('tenant.insurance.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
    </form>
</div>
