<div class="flex flex-col h-full min-h-0 bg-slate-100">

    {{-- Top bar --}}
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-lg font-bold text-slate-800">Patients</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ $total }} {{ Str::plural('patient', $total) }} registered</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <input wire:model.live.debounce.300ms="search" type="search"
                       placeholder="Name, number, phone, ID…"
                       class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm shadow-sm w-64 focus:border-blue-500 focus:bg-white focus:outline-none transition" />
                <input wire:model.live="dobFilter" type="date"
                       class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:outline-none transition" />
                <select wire:model.live="branchFilter"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:outline-none transition">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @if(auth()->guard('tenant')->user()?->hasPermission('patients.create'))
                    <button wire:click="openRegistrationModal"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition whitespace-nowrap shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Register Patient
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="flex-1 overflow-y-auto px-6 py-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-10"></th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Patient</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">ID #</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">DOB / Age</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Phone</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Doctor</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Branch</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($patients as $patient)
                        <tr onclick="window.location='{{ route('tenant.patients.show', $patient->id) }}'"
                            class="hover:bg-blue-50 cursor-pointer transition group">
                            <td class="px-5 py-3">
                                <div class="w-9 h-9 rounded-full bg-blue-100 group-hover:bg-blue-200 flex items-center justify-center transition">
                                    <span class="text-xs font-bold text-blue-700">
                                        {{ mb_strtoupper(mb_substr($patient->first_name, 0, 1) . mb_substr($patient->last_name, 0, 1)) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="text-sm font-semibold text-slate-800 group-hover:text-blue-700 transition">
                                    {{ $patient->display_name }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if($patient->allergies->isNotEmpty())
                                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-xs bg-red-100 text-red-700 font-medium">
                                            ⚠ {{ $patient->allergies->count() }}
                                        </span>
                                    @endif
                                    @if($patient->blood_type)
                                        <span class="text-xs text-slate-400">{{ $patient->blood_type }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-sm font-mono text-slate-500">{{ $patient->patient_number }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">
                                {{ $patient->date_of_birth?->format('d M Y') ?? '—' }}
                                @if($patient->age !== null)
                                    <span class="text-slate-400 text-xs ml-1">{{ $patient->age }}y</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $patient->phone_primary ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $patient->primaryDoctor?->staffProfile?->full_name ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $patient->branch?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="text-slate-300 text-4xl mb-3">👤</div>
                                <p class="text-sm font-medium text-slate-500">
                                    {{ $search || $dobFilter ? 'No patients match your search.' : 'No patients registered yet.' }}
                                </p>
                                @if(!$search && !$dobFilter && auth()->guard('tenant')->user()?->hasPermission('patients.create'))
                                    <button wire:click="openRegistrationModal"
                                            class="mt-3 inline-block text-sm font-semibold text-blue-600 hover:text-blue-800">
                                        Register the first patient →
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($patients->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50">
                    {{ $patients->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         Registration Modal
    ══════════════════════════════════════════════ --}}
    @if($showRegistrationModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60"
             x-data x-on:keydown.escape.window="$wire.closeRegistrationModal()">

            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                {{-- Modal header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h2 class="text-base font-bold text-slate-800">Register New Patient</h2>
                    <button wire:click="closeRegistrationModal" class="text-slate-400 hover:text-slate-700 text-xl leading-none">✕</button>
                </div>

                {{-- Options --}}
                @if($registrationStatus === '')
                    <div class="p-6 space-y-4">
                        <p class="text-sm text-slate-500">Choose how to register the patient.</p>

                        <button wire:click="generateRegistrationQr"
                                class="w-full flex items-start gap-4 p-4 rounded-xl border-2 border-blue-200 hover:border-blue-500 bg-blue-50 hover:bg-blue-100 transition text-left group">
                            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 3.5V15m-4 1v3m-8-9V4m4 0h4M4 8h4m0 4v3m12-3h.01"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 group-hover:text-blue-700">Patient Self-Service (QR Code)</p>
                                <p class="text-xs text-slate-500 mt-0.5">Generate a QR code the patient scans with their phone or a tablet to fill in their own information.</p>
                            </div>
                        </button>

                        <a href="{{ route('tenant.patients.create') }}"
                           class="w-full flex items-start gap-4 p-4 rounded-xl border-2 border-slate-200 hover:border-slate-400 bg-white hover:bg-slate-50 transition text-left group">
                            <div class="w-10 h-10 rounded-xl bg-slate-200 group-hover:bg-slate-300 flex items-center justify-center flex-shrink-0 mt-0.5 transition">
                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 group-hover:text-slate-900">Manual Entry by Receptionist</p>
                                <p class="text-xs text-slate-500 mt-0.5">Open the full registration form for direct data entry. Best for older patients or when a device is unavailable.</p>
                            </div>
                        </a>
                    </div>

                @elseif($registrationStatus === 'pending')
                    <div class="p-6">
                        {{-- QR display --}}
                        <div class="text-center mb-5">
                            <div class="inline-block p-3 bg-white border-2 border-slate-200 rounded-2xl shadow-inner">
                                {!! $registrationQrSvg !!}
                            </div>
                            <p class="text-xs text-slate-400 mt-2">Expires {{ $registrationExpiresAt }}</p>
                        </div>

                        <div class="bg-slate-50 rounded-xl p-4 mb-5 text-sm text-slate-600 space-y-1.5">
                            <p class="font-semibold text-slate-800">Instructions</p>
                            <p>1. Show this QR code to the patient.</p>
                            <p>2. Patient scans it with their phone camera or the provided tablet.</p>
                            <p>3. They fill in their details and tap <strong>Submit</strong>.</p>
                            <p>4. Click <strong>Check Status</strong> below — when complete you can open their file.</p>
                        </div>

                        <div class="flex gap-3">
                            <button wire:click="checkRegistrationComplete"
                                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2.5 rounded-xl transition shadow-sm">
                                Check Status
                            </button>
                            <button wire:click="generateRegistrationQr"
                                    class="text-sm text-slate-400 hover:text-slate-700 px-3 py-2.5 rounded-xl border border-slate-200 hover:border-slate-400 transition">
                                Regenerate
                            </button>
                        </div>
                    </div>

                @elseif($registrationStatus === 'complete')
                    <div class="p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">Patient registered!</h3>
                        <p class="text-sm text-slate-500 mb-6">The patient has completed the form and their record has been created.</p>
                        <div class="flex flex-col gap-3">
                            <a href="{{ route('tenant.patients.show', $registrationPatientId ?? 0) }}"
                               class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-3 rounded-xl transition shadow-sm">
                                Open Patient File →
                            </a>
                            <button wire:click="closeRegistrationModal"
                                    class="text-sm text-slate-500 hover:text-slate-700 py-2">
                                Close
                            </button>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif

</div>
