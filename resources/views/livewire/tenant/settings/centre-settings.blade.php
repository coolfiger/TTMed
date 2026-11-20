<div class="max-w-3xl">
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    {{-- Tab navigation --}}
    <div class="flex border-b border-slate-200 mb-6 gap-1">
        @foreach(['general' => 'General', 'email' => 'Email', 'branding' => 'Branding', 'backups' => 'Backups'] as $tab => $label)
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

    {{-- Branding --}}
    @if($activeTab === 'branding')
    <div class="space-y-6">

        @if(session('branding_saved'))
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 text-sm text-emerald-700">
                {{ session('branding_saved') }}
            </div>
        @endif

        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <h3 class="text-sm font-semibold text-slate-800 mb-1">White Label Branding</h3>
            <p class="text-xs text-slate-400 mb-5">Logos and colours appear on the login screen and sidebar for your subdomain. Changes are live immediately.</p>

            <div class="space-y-5">

                {{-- Primary colour --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Brand Colour</label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model="brandingPrimaryColor"
                               class="h-10 w-14 rounded-lg border border-slate-200 cursor-pointer p-0.5" />
                        <input type="text" wire:model="brandingPrimaryColor" placeholder="#1e40af"
                               class="w-28 border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none transition" />
                        <span class="text-xs text-slate-400">Used on buttons and accents (future documents)</span>
                    </div>
                </div>

                {{-- Login logo --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Login Screen Logo</label>
                    <p class="text-xs text-slate-400 mb-2">Shown at the top of the sign-in page. Max 2MB, PNG/JPG/SVG. Recommended: transparent background, max height 80px.</p>
                    <div class="flex items-center gap-4">
                        <input type="file" wire:model="loginLogoFile" accept="image/*"
                               class="text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                        @if($hasLoginLogo)
                            <span class="text-xs text-emerald-600 font-semibold">✓ Logo set</span>
                            <button wire:click="removeLogo('login')" wire:confirm="Remove login logo?"
                                    class="text-xs text-red-500 hover:text-red-700">Remove</button>
                        @endif
                    </div>
                    @error('loginLogoFile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Sidebar logo --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Sidebar Logo</label>
                    <p class="text-xs text-slate-400 mb-2">Replaces the practice name text in the left sidebar. Max 2MB. Recommended: landscape, transparent background, max height 32px.</p>
                    <div class="flex items-center gap-4">
                        <input type="file" wire:model="sidebarLogoFile" accept="image/*"
                               class="text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                        @if($hasSidebarLogo)
                            <span class="text-xs text-emerald-600 font-semibold">✓ Logo set</span>
                            <button wire:click="removeLogo('sidebar')" wire:confirm="Remove sidebar logo?"
                                    class="text-xs text-red-500 hover:text-red-700">Remove</button>
                        @endif
                    </div>
                    @error('sidebarLogoFile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="pt-2">
                    <button wire:click="saveBranding"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-xl shadow-sm transition">
                        Save Branding
                    </button>
                </div>

            </div>
        </div>

    </div>
    @endif

    {{-- Backups --}}
    @if($activeTab === 'backups')
    <div class="space-y-6">

        {{-- Schedule --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Automatic Backup Schedule</h3>
            <form wire:submit="saveBackupSchedule" class="space-y-4">
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Frequency</label>
                        <select wire:model.live="backupFrequency"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                    @if($backupFrequency !== 'disabled')
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Hour (server time)</label>
                        <select wire:model="backupHour"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                            @for($h = 0; $h < 24; $h++)
                            <option value="{{ $h }}" @selected($backupHour === $h)>{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00</option>
                            @endfor
                        </select>
                    </div>
                    @if($backupFrequency === 'weekly')
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Day of week</label>
                        <select wire:model="backupDay"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                            @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $day)
                            <option value="{{ $i }}" @selected($backupDay === $i)>{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Keep last N backups</label>
                        <input wire:model="backupRetain" type="number" min="1" max="30"
                            class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                    </div>
                    @endif
                </div>
                <button type="submit"
                    class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                    Save Schedule
                </button>
            </form>
        </div>

        {{-- Manual backup --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-800">Back Up Now</h3>
                    @if($backupLastRun)
                        <p class="text-xs text-slate-500 mt-0.5">Last backup: {{ $backupLastRun->diffForHumans() }}</p>
                    @else
                        <p class="text-xs text-slate-500 mt-0.5">No backup has been run yet.</p>
                    @endif
                </div>
                <button wire:click="triggerBackup"
                    wire:loading.attr="disabled"
                    wire:target="triggerBackup"
                    wire:loading.class="opacity-60 cursor-not-allowed"
                    class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                    <span wire:loading.remove wire:target="triggerBackup">Start Backup</span>
                    <span wire:loading wire:target="triggerBackup">Queuing…</span>
                </button>
            </div>
            @if($backupStatus === 'queued')
                <p class="mt-3 text-sm text-green-700 bg-green-50 rounded-md px-3 py-2">
                    Backup has been queued and will run shortly.
                </p>
            @endif
        </div>

        {{-- Backup file list --}}
        @if(count($backups) > 0)
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-800">Existing Backups</h3>
            </div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">File</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($backups as $backup)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 font-mono text-xs text-slate-700">{{ $backup['name'] }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $backup['size'] }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $backup['created_at'] }}</td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('tenant.settings.backup.download', $backup['name']) }}"
                               class="text-blue-600 hover:underline text-xs font-medium">
                                Download
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @elseif($activeTab === 'backups')
        <div class="bg-white rounded-lg border border-slate-200 p-6 text-sm text-slate-500">
            No backup files found yet.
        </div>
        @endif

    </div>
    @endif
</div>
