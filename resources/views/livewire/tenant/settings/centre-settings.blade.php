<div class="max-w-3xl">
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    {{-- Tab navigation --}}
    <div class="flex border-b border-slate-200 mb-6 gap-1">
        @foreach(['general' => 'General', 'email' => 'Email'] as $tab => $label)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="px-4 py-2 text-sm font-medium rounded-t-md -mb-px
                {{ $activeTab === $tab
                    ? 'bg-white border border-b-white border-slate-200 text-blue-700'
                    : 'text-slate-500 hover:text-slate-800' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- General settings --}}
    @if($activeTab === 'general')
    <form wire:submit="saveGeneral" class="space-y-5 bg-white rounded-lg border border-slate-200 p-6">
        <div class="grid grid-cols-2 gap-5">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Medical Centre Name</label>
                <input wire:model="centreName" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('centreName') border-red-500 @enderror">
                @error('centreName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Address</label>
                <textarea wire:model="centreAddress" rows="2"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Phone</label>
                <input wire:model="centrePhone" type="text"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input wire:model="centreEmail" type="email"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('centreEmail') border-red-500 @enderror">
                @error('centreEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-slate-700">Website</label>
                <input wire:model="centreWebsite" type="url"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Timezone</label>
                <select wire:model="timezone"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                    @foreach($timezones as $tz)
                    <option value="{{ $tz }}" @selected($timezone === $tz)>{{ $tz }}</option>
                    @endforeach
                </select>
                @error('timezone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Currency</label>
                <select wire:model="currency"
                    class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                    <option value="TTD">TTD — Trinidad and Tobago Dollar</option>
                    <option value="USD">USD — US Dollar</option>
                    <option value="BBD">BBD — Barbados Dollar</option>
                </select>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Tax Settings</h3>
            <div class="grid grid-cols-2 gap-5">
                <div class="flex items-center gap-2 col-span-2">
                    <input wire:model="taxEnabled" id="taxEnabled" type="checkbox" class="rounded border-slate-300 text-blue-600">
                    <label for="taxEnabled" class="text-sm text-slate-700">Enable tax on services</label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tax Label</label>
                    <input wire:model="taxLabel" type="text" placeholder="VAT"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Default Tax Rate (%)</label>
                    <input wire:model="taxRate" type="number" step="0.01" min="0" max="100"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('taxRate') border-red-500 @enderror">
                    @error('taxRate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <button type="submit"
            class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
            Save General Settings
        </button>
    </form>
    @endif

    {{-- Email settings --}}
    @if($activeTab === 'email')
    <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-5">
        <form wire:submit="saveEmail" class="space-y-5">
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700">SMTP Host</label>
                    <input wire:model="smtpHost" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('smtpHost') border-red-500 @enderror">
                    @error('smtpHost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Port</label>
                    <input wire:model="smtpPort" type="number"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">SMTP Username</label>
                    <input wire:model="smtpUsername" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('smtpUsername') border-red-500 @enderror">
                    @error('smtpUsername') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        SMTP Password
                        @if($smtpUsername) <span class="text-xs text-slate-400">(leave blank to keep current)</span> @endif
                    </label>
                    <input wire:model="smtpPassword" type="password" autocomplete="new-password"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">From Email</label>
                    <input wire:model="smtpFromAddress" type="email"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('smtpFromAddress') border-red-500 @enderror">
                    @error('smtpFromAddress') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">From Name</label>
                    <input wire:model="smtpFromName" type="text"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none @error('smtpFromName') border-red-500 @enderror">
                    @error('smtpFromName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit"
                class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                Save Email Settings
            </button>
        </form>

        <div class="border-t border-slate-100 pt-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Send Test Email</h3>
            <div class="flex gap-3 items-start">
                <input wire:model="testEmailTo" type="email" placeholder="recipient@example.com"
                    class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none w-72">
                <button wire:click="sendTestEmail"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Send Test
                </button>
            </div>
            @error('testEmailTo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @if($testEmailStatus === 'success')
                <p class="mt-2 text-sm text-green-700">Test email sent successfully.</p>
            @elseif($testEmailStatus && str_starts_with($testEmailStatus, 'error:'))
                <p class="mt-2 text-sm text-red-600">{{ substr($testEmailStatus, 6) }}</p>
            @endif
        </div>
    </div>
    @endif
</div>
