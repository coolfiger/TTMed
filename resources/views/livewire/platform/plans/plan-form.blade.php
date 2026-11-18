<div class="max-w-lg">
    <form wire:submit="save" class="space-y-5 bg-white rounded-lg border border-slate-200 p-6">
        <div>
            <label class="block text-sm font-medium text-slate-700">Plan Name</label>
            <input wire:model="name" type="text"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('name') border-red-500 @enderror">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Slug</label>
            <input wire:model="slug" type="text"
                class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-slate-500 focus:outline-none @error('slug') border-red-500 @enderror">
            @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-slate-700">User Limit</label>
                <input wire:model="userLimit" type="number" min="1"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Storage (GB)</label>
                <input wire:model="storageLimitGb" type="number" min="1"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Monthly Price (cents TTD)</label>
                <input wire:model="monthlyPrice" type="number" min="0"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Annual Price (cents TTD)</label>
                <input wire:model="annualPrice" type="number" min="0"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
            </div>
        </div>
        <div class="flex items-center gap-2">
            <input wire:model="isActive" id="isActive" type="checkbox"
                class="rounded border-slate-300 text-slate-700">
            <label for="isActive" class="text-sm text-slate-700">Active (visible to new signups)</label>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="rounded-md bg-slate-800 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                Save Plan
            </button>
            <a href="{{ route('platform.plans.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
    </form>
</div>
