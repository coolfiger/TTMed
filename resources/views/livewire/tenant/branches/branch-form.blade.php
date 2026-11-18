<div class="max-w-2xl space-y-6">
    <form wire:submit="save" class="space-y-5 bg-white rounded-lg border border-slate-200 p-6">
        <div class="grid grid-cols-2 gap-5">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Branch Name</label>
                <input wire:model="name" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Address</label>
                <textarea wire:model="address" rows="2"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">City</label>
                <input wire:model="city" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Phone</label>
                <input wire:model="phone" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input wire:model="email" type="email"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-2">
                <input wire:model="isActive" id="isActive" type="checkbox" class="rounded border-slate-300 text-blue-600">
                <label for="isActive" class="text-sm text-slate-700">Branch active</label>
            </div>
            @if(!$branch)
            <div class="flex items-center gap-2">
                <input wire:model="isMain" id="isMain" type="checkbox" class="rounded border-slate-300 text-blue-600">
                <label for="isMain" class="text-sm text-slate-700">Set as main branch</label>
            </div>
            @endif
        </div>

        <div class="border-t border-slate-100 pt-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Operating Hours</h3>
            <div class="space-y-2">
                @foreach($dayNames as $day => $dayName)
                <div class="flex items-center gap-4">
                    <span class="w-28 text-sm text-slate-600">{{ $dayName }}</span>
                    <label class="flex items-center gap-2">
                        <input wire:model="hours.{{ $day }}.is_closed" type="checkbox"
                            class="rounded border-slate-300 text-slate-600">
                        <span class="text-xs text-slate-500">Closed</span>
                    </label>
                    @if(!($hours[$day]['is_closed'] ?? false))
                    <input wire:model="hours.{{ $day }}.open_time" type="time"
                        class="rounded-md border border-slate-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none">
                    <span class="text-sm text-slate-400">to</span>
                    <input wire:model="hours.{{ $day }}.close_time" type="time"
                        class="rounded-md border border-slate-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none">
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                {{ $branch ? 'Update Branch' : 'Create Branch' }}
            </button>
            <a href="{{ route('tenant.branches.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
    </form>
</div>
