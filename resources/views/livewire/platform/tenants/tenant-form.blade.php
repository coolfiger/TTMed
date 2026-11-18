<div class="max-w-2xl">
    <form wire:submit="save" class="space-y-6 bg-white rounded-lg border border-slate-200 p-6">

        <div class="grid grid-cols-2 gap-5">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Medical Centre Name</label>
                <input wire:model="name" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('name') border-red-500 @enderror">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Subdomain Code</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input wire:model="code" type="text" {{ $tenant ? 'disabled' : '' }}
                        class="block w-full rounded-l-md border border-slate-300 px-3 py-2 text-sm font-mono focus:border-slate-500 focus:outline-none @error('code') border-red-500 @enderror">
                    <span class="inline-flex items-center rounded-r-md border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">.ttmed.app</span>
                </div>
                @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Status</label>
                <select wire:model="status"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
                    <option value="trial">Trial</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Custom Domain (optional)</label>
                <input wire:model="customDomain" type="text" placeholder="app.yourclinic.com"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
                @error('customDomain') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        @if(!$tenant)
        <div class="border-t border-slate-100 pt-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Initial Admin Account</h3>
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Admin Name</label>
                    <input wire:model="adminName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('adminName') border-red-500 @enderror">
                    @error('adminName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Admin Username</label>
                    <input wire:model="adminUsername" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm font-mono shadow-sm focus:border-slate-500 focus:outline-none @error('adminUsername') border-red-500 @enderror">
                    @error('adminUsername') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Admin Email</label>
                    <input wire:model="adminEmail" type="email"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('adminEmail') border-red-500 @enderror">
                    @error('adminEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Admin Password</label>
                    <input wire:model="adminPassword" type="password"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('adminPassword') border-red-500 @enderror">
                    @error('adminPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Default Branch Name</label>
                    <input wire:model="branchName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('branchName') border-red-500 @enderror">
                    @error('branchName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Subscription Plan</label>
                    <select wire:model="planId"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none @error('planId') border-red-500 @enderror">
                        <option value="">— Select plan —</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                    @error('planId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
        @endif

        @if(session('provisioning_error'))
        <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
            {{ session('provisioning_error') }}
        </div>
        @endif

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="rounded-md bg-slate-800 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                wire:loading.attr="disabled" wire:loading.class="opacity-75">
                <span wire:loading.remove>{{ $tenant ? 'Update' : 'Create Medical Centre' }}</span>
                <span wire:loading>{{ $tenant ? 'Updating…' : 'Provisioning…' }}</span>
            </button>
            <a href="{{ route('platform.tenants.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
        </div>
    </form>
</div>
