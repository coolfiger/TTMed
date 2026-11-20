<div class="flex flex-col h-full">

    {{-- Top bar --}}
    <div class="flex-shrink-0 h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6">
        <div class="flex items-center gap-3">
            <h1 class="text-base font-semibold text-slate-800">Reception</h1>
            <span class="text-sm text-slate-400">{{ today()->format('l, d F Y') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <select wire:change="switchBranch($event.target.value)"
                    class="text-sm rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10">
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $b->id == $branchId ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            <button wire:click="loadQueue"
                    class="text-xs text-slate-500 hover:text-slate-700 border border-slate-200 rounded-lg px-3 py-1.5 hover:bg-slate-50 transition">
                Refresh
            </button>
        </div>
    </div>

    {{-- Main workspace --}}
    <div class="flex flex-1 min-h-0">

        {{-- ══════════════════════════════════════════════════════════
             LEFT PANEL — Check-in wizard
        ══════════════════════════════════════════════════════════ --}}
        <div class="w-96 flex-shrink-0 border-r border-slate-200 bg-white flex flex-col">

            @if(!$showCheckinForm)
                {{-- Search state --}}
                <div class="flex-1 flex flex-col p-6">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-4">Check In Patient</p>

                    <div class="relative" x-data="{ open: false }">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input wire:model.live.debounce.250ms="patientSearch"
                                   @focus="open = true"
                                   @click.away="open = false"
                                   type="text"
                                   placeholder="Search name, ID or phone..."
                                   class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
                        </div>

                        @if(count($searchResults) > 0)
                            <div class="absolute z-30 mt-1 w-full bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
                                @foreach($searchResults as $result)
                                    <button wire:click="selectPatient({{ $result['id'] }})"
                                            class="w-full text-left px-4 py-3 hover:bg-blue-50 border-b border-slate-100 last:border-0 transition">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-slate-800">{{ $result['display_name'] }}</span>
                                            <span class="text-xs font-mono text-slate-500">{{ $result['patient_number'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 mt-0.5">
                                            @if($result['dob'])
                                                <span class="text-xs text-slate-400">DOB: {{ $result['dob'] }}</span>
                                            @endif
                                            @if($result['phone'])
                                                <span class="text-xs text-slate-400">{{ $result['phone'] }}</span>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @elseif(strlen($patientSearch) >= 2)
                            <div class="mt-2 text-xs text-slate-500">No patients found matching "{{ $patientSearch }}".</div>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-100">
                        @if(!$showRegisterPanel)
                            <button wire:click="openRegisterPanel"
                                    class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Register new patient
                            </button>
                        @else
                            {{-- Registration panel --}}
                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-xs font-semibold text-blue-800 uppercase tracking-wide">New Patient Registration</p>
                                    <button wire:click="closeRegisterPanel" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                                </div>

                                @if($regStatus === '')
                                    {{-- Options --}}
                                    <div class="space-y-2">
                                        <button wire:click="generateRegQr"
                                                class="w-full flex items-center gap-3 p-3 rounded-lg bg-white border border-slate-200 hover:border-blue-400 hover:bg-blue-50 transition text-left">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold text-slate-700">Patient self-service (QR)</p>
                                                <p class="text-xs text-slate-400 mt-0.5">Patient fills form on their phone</p>
                                            </div>
                                        </button>
                                        <a href="{{ route('tenant.patients.create') }}"
                                           class="w-full flex items-center gap-3 p-3 rounded-lg bg-white border border-slate-200 hover:border-blue-400 hover:bg-blue-50 transition">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold text-slate-700">Manual entry</p>
                                                <p class="text-xs text-slate-400 mt-0.5">Receptionist fills in the form</p>
                                            </div>
                                        </a>
                                    </div>

                                @elseif($regStatus === 'pending')
                                    {{-- QR shown, waiting --}}
                                    <div class="bg-white rounded-lg p-3 text-center mb-3">
                                        {!! $regQrSvg !!}
                                        <p class="text-xs text-slate-500 mt-2">Patient scans to open registration form</p>
                                        <p class="text-xs text-slate-400 mt-1">Expires {{ $regExpiresAt }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="checkRegComplete"
                                                class="flex-1 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                            Check if Complete
                                        </button>
                                        <button wire:click="generateRegQr"
                                                class="py-2 px-3 text-xs text-slate-500 border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                                            Regenerate
                                        </button>
                                    </div>

                                @elseif($regStatus === 'complete')
                                    {{-- Done --}}
                                    <div class="text-center py-3">
                                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 mb-2">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Patient registered!</p>
                                        <div class="flex gap-2 mt-3">
                                            <a href="{{ route('tenant.patients.show', $regPatientId) }}"
                                               class="flex-1 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-center">
                                                Open Patient File
                                            </a>
                                            <button wire:click="closeRegisterPanel"
                                                    class="py-2 px-3 text-xs text-slate-500 border border-slate-300 rounded-lg hover:bg-slate-50">
                                                Done
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Doctor status summary --}}
                    <div class="mt-6">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Doctors On Duty</p>
                        @forelse($doctorStatuses as $doc)
                            <div class="flex items-center justify-between py-2.5 border-b border-slate-100 last:border-0">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $doc['name'] }}</p>
                                    @if($doc['specialty'])
                                        <p class="text-xs text-slate-500">{{ $doc['specialty'] }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                    <span class="text-xs text-slate-500">{{ $doc['queueCount'] }} waiting</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $doc['status'] === 'available'    ? 'bg-emerald-100 text-emerald-700' : '' }}
                                        {{ $doc['status'] === 'busy'         ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $doc['status'] === 'on_break'     ? 'bg-orange-100 text-orange-700' : '' }}
                                        {{ in_array($doc['status'], ['off_duty', 'not_accepting']) ? 'bg-slate-100 text-slate-600' : '' }}">
                                        {{ $doc['statusLabel'] }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400 italic">No doctors assigned.</p>
                        @endforelse
                    </div>
                </div>

            @else
                {{-- Patient card --}}
                <div class="flex-1 flex flex-col overflow-y-auto">

                    {{-- Gradient identity card --}}
                    <div class="px-5 py-4 bg-gradient-to-br from-blue-600 to-blue-700 text-white flex-shrink-0">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                                @php
                                    $parts = explode(' ', $selectedPatientName);
                                    echo mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr(end($parts), 0, 1));
                                @endphp
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h2 class="text-sm font-bold leading-tight">{{ $selectedPatientName }}</h2>
                                        <p class="text-blue-200 text-xs mt-0.5">#{{ $selectedPatientNumber }}</p>
                                    </div>
                                    <button wire:click="cancelCheckin"
                                            class="text-white/60 hover:text-white p-0.5 mt-0.5 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @if($selectedPatientAllergyCount > 0)
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold bg-red-500 text-white px-2 py-0.5 rounded-full">
                                            ⚠ {{ $selectedPatientAllergyCount }} {{ Str::plural('Allergy', $selectedPatientAllergyCount) }}
                                        </span>
                                    @else
                                        <span class="text-xs bg-white/20 text-blue-100 px-2 py-0.5 rounded-full">NKDA</span>
                                    @endif
                                    @if($selectedPatientBloodType)
                                        <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full font-semibold">{{ $selectedPatientBloodType }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Quick actions --}}
                        <div class="flex gap-2 mt-3 pt-3 border-t border-blue-500">
                            <button wire:click="startQueueWizard"
                                    class="flex-1 text-xs font-semibold bg-emerald-500 hover:bg-emerald-400 text-white py-1.5 rounded-lg transition text-center">
                                + Queue
                            </button>
                            <a href="{{ route('tenant.patients.edit', $selectedPatientId) }}"
                               class="flex-1 text-xs font-semibold bg-white/20 hover:bg-white/30 text-white py-1.5 rounded-lg transition text-center">
                                Edit Full
                            </a>
                            <button wire:click="generatePatientQr"
                                    class="flex-1 text-xs font-semibold bg-indigo-500 hover:bg-indigo-400 text-white py-1.5 rounded-lg transition text-center">
                                QR Form
                            </button>
                        </div>
                    </div>

                    {{-- QR Panel --}}
                    @if($showQrPanel)
                        <div class="px-4 py-4 bg-indigo-50 border-b border-indigo-200 flex-shrink-0">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-semibold text-indigo-800">Patient Self-Service Form</p>
                                <button wire:click="dismissQr" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                            </div>
                            <div class="bg-white rounded-lg p-3 text-center">
                                {!! $qrSvg !!}
                                <p class="text-xs text-slate-500 mt-2">Scan to open the patient's form</p>
                                <div class="mt-2 bg-indigo-100 rounded-lg px-4 py-2">
                                    <p class="text-xs text-indigo-500 uppercase tracking-wide font-semibold mb-0.5">One-Time PIN</p>
                                    <p class="text-2xl font-black tracking-widest text-indigo-800">{{ $qrPin }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Queue wizard --}}
                    @if($showQueueWizard)
                        <div class="flex-1 flex flex-col" x-data="{ step: 1 }">

                            {{-- Step indicators --}}
                            <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center transition"
                                              :class="step >= 1 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500'">1</span>
                                        <span class="text-xs" :class="step >= 1 ? 'text-blue-600 font-medium' : 'text-slate-400'">Visit Type</span>
                                    </div>
                                    <div class="flex-1 h-px bg-slate-200"></div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center transition"
                                              :class="step >= 2 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-500'">2</span>
                                        <span class="text-xs" :class="step >= 2 ? 'text-blue-600 font-medium' : 'text-slate-400'">Details</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Step 1: Visit type --}}
                            <div x-show="step === 1"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-x-4"
                                 x-transition:enter-end="opacity-100 translate-x-0"
                                 class="flex-1 p-5">
                                <p class="text-sm font-medium text-slate-700 mb-4">What brings them in today?</p>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach(['walk_in' => ['Walk-in', 'M17 20h5m-9 0H3m9 0v-9m0 0a3 3 0 100-6 3 3 0 000 6z', 'blue'], 'follow_up' => ['Follow-up', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'violet'], 'emergency' => ['Emergency', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'red'], 'appointment' => ['Appointment', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'emerald']] as $type => [$label, $icon, $color])
                                        <button @click="$wire.set('visitType', '{{ $type }}'); step = 2"
                                                class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition
                                                    {{ $visitType === $type
                                                        ? ($color === 'blue' ? 'border-blue-500 bg-blue-50' : ($color === 'violet' ? 'border-violet-500 bg-violet-50' : ($color === 'red' ? 'border-red-500 bg-red-50' : 'border-emerald-500 bg-emerald-50')))
                                                        : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                                            <svg class="w-7 h-7 {{ $color === 'blue' ? 'text-blue-600' : ($color === 'violet' ? 'text-violet-600' : ($color === 'red' ? 'text-red-600' : 'text-emerald-600')) }}"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/>
                                            </svg>
                                            <span class="text-xs font-semibold text-slate-700">{{ $label }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Step 2: Details --}}
                            <div x-show="step === 2"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-x-4"
                                 x-transition:enter-end="opacity-100 translate-x-0"
                                 class="flex-1 flex flex-col p-5 gap-4">

                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Assign Doctor <span class="text-slate-400 font-normal">(optional)</span></label>
                                    <select wire:model="selectedDoctorId"
                                            class="w-full text-sm rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10">
                                        <option value="0">— Unassigned —</option>
                                        @foreach($activeDoctors as $doc)
                                            <option value="{{ $doc->id }}">{{ $doc->staffProfile?->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Chief Complaint <span class="text-slate-400 font-normal">(optional)</span></label>
                                    <input wire:model="chiefComplaint" type="text"
                                           placeholder="Reason for visit..."
                                           class="w-full text-sm rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                </div>

                                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-slate-200 hover:border-red-300 hover:bg-red-50 transition">
                                    <input wire:model.live="isPriority" type="checkbox"
                                           class="rounded border-slate-300 text-red-600 focus:ring-red-500" />
                                    <div>
                                        <span class="text-sm font-medium text-slate-700">Mark as Priority</span>
                                        <p class="text-xs text-slate-500">Moves to top of queue</p>
                                    </div>
                                </label>

                                @if($isPriority)
                                    <div x-show="true" x-transition>
                                        <input wire:model="priorityReason" type="text"
                                               placeholder="Priority reason (e.g. chest pain)..."
                                               class="w-full text-sm rounded-lg border-red-300 focus:ring-red-500 focus:border-red-500" />
                                    </div>
                                @endif

                                @error('selectedPatientId')
                                    <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="mt-auto flex gap-3">
                                    <button @click="step = 1"
                                            class="flex-1 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                                        Back
                                    </button>
                                    <button wire:click="checkIn" wire:loading.attr="disabled"
                                            class="flex-1 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition disabled:opacity-60">
                                        <span wire:loading.remove wire:target="checkIn">Check In</span>
                                        <span wire:loading wire:target="checkIn">Processing...</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    @endif

                </div>
            @endif

            {{-- ── Document Upload Panel (shown after check-in) ── --}}
            @if($showDocumentUpload)
                <div class="mt-4 mx-4 mb-4 rounded-xl border border-blue-200 bg-blue-50 overflow-hidden">
                    <div class="px-4 py-3 bg-blue-600 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-white">Attach Documents for {{ $uploadPatientName }}</p>
                            <p class="text-xs text-blue-200 mt-0.5">X-rays, blood results, referral letters, photos — optional</p>
                        </div>
                        <button wire:click="doneUploading" class="text-blue-200 hover:text-white transition text-lg leading-none">&times;</button>
                    </div>

                    <div class="p-4 space-y-4">

                        {{-- Uploaded so far --}}
                        @if(count($uploadedDocNames) > 0)
                            <div class="space-y-1">
                                @foreach($uploadedDocNames as $name)
                                    <div class="flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-1.5">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="truncate">{{ $name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Error --}}
                        @if($docUploadError)
                            <p class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $docUploadError }}</p>
                        @endif

                        {{-- File picker --}}
                        <div>
                            <label class="form-label">File <span class="text-slate-400 font-normal">(image, PDF, Word — max 20 MB)</span></label>
                            <input type="file" wire:model="docFile"
                                   accept="image/*,application/pdf,.doc,.docx"
                                   capture="environment"
                                   class="w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 cursor-pointer" />
                            <div wire:loading wire:target="docFile" class="text-xs text-slate-500 mt-1">Reading file…</div>
                        </div>

                        {{-- Optional title --}}
                        <div>
                            <label class="form-label">Title <span class="text-slate-400 font-normal">(optional — defaults to filename)</span></label>
                            <input type="text" wire:model="docTitle" placeholder="e.g. Chest X-ray Jan 2025" class="w-full" />
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button wire:click="addDocument"
                                    wire:loading.attr="disabled"
                                    wire:target="addDocument"
                                    class="btn btn-primary btn-sm flex-1">
                                <span wire:loading.remove wire:target="addDocument">+ Add File</span>
                                <span wire:loading wire:target="addDocument">Uploading…</span>
                            </button>
                            <button wire:click="doneUploading" class="btn btn-secondary btn-sm flex-1">
                                {{ count($uploadedDocNames) > 0 ? 'Done' : 'Skip' }}
                            </button>
                        </div>

                    </div>
                </div>
            @endif

        </div>

        {{-- ══════════════════════════════════════════════════════════
             RIGHT PANEL — Live queue
        ══════════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Queue header --}}
            <div class="flex-shrink-0 px-6 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-sm font-semibold text-slate-700">Today's Queue</h2>
                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($queueEntries) }}</span>
                    </div>
                    {{-- Stage legend --}}
                    <div class="flex items-center gap-4 text-xs">
                        @foreach([
                            ['waiting', 'Waiting', 'bg-amber-100 text-amber-700'],
                            ['with_nurse', 'Nurse', 'bg-violet-100 text-violet-700'],
                            ['awaiting_doctor', 'Awaiting Dr', 'bg-orange-100 text-orange-700'],
                            ['with_doctor', 'With Dr', 'bg-emerald-100 text-emerald-700'],
                        ] as [$status, $label, $cls])
                            @php $count = collect($queueEntries)->where('status', $status)->count(); @endphp
                            @if($count > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $cls }}">
                                    {{ $count }} {{ $label }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Flash success --}}
            @if(session('success'))
                <div class="mx-6 mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800"
                     x-data x-init="setTimeout(() => $el.remove(), 4000)">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Queue entries --}}
            <div class="flex-1 overflow-y-auto px-6 py-4">
                @if(count($queueEntries) === 0)
                    <div class="flex flex-col items-center justify-center h-full text-center py-20">
                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-slate-500">Queue is empty</p>
                        <p class="text-xs text-slate-400 mt-1">Check in a patient using the panel on the left.</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($queueEntries as $entry)
                            <div class="bg-white rounded-xl border border-slate-200 px-4 py-3.5 flex items-center gap-4 hover:border-slate-300 transition">

                                {{-- Queue number --}}
                                <div class="w-10 text-center flex-shrink-0">
                                    <span class="text-xl font-black {{ $entry['isPriority'] ? 'text-red-600' : 'text-slate-700' }}">{{ $entry['number'] }}</span>
                                    @if($entry['isPriority'])
                                        <p class="text-xs font-bold text-red-500 leading-tight -mt-0.5">PRIORITY</p>
                                    @endif
                                </div>

                                {{-- Pipeline position --}}
                                <div class="hidden sm:flex items-center gap-1 text-xs flex-shrink-0">
                                    @foreach([
                                        ['waiting', 'W'],
                                        ['with_nurse', 'N'],
                                        ['awaiting_doctor', 'A'],
                                        ['with_doctor', 'D'],
                                        ['awaiting_checkout', 'P'],
                                    ] as $i => [$s, $letter])
                                        @php
                                            $stages = ['waiting', 'with_nurse', 'awaiting_doctor', 'with_doctor', 'awaiting_checkout'];
                                            $currentIdx = array_search($entry['status'], $stages);
                                            $thisIdx = $i;
                                        @endphp
                                        <span class="w-6 h-6 rounded-full font-semibold flex items-center justify-center
                                            {{ $thisIdx < $currentIdx ? 'bg-slate-200 text-slate-500' : ($thisIdx === $currentIdx ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400') }}">
                                            {{ $letter }}
                                        </span>
                                        @if($i < 4)<span class="w-3 h-px bg-slate-200"></span>@endif
                                    @endforeach
                                </div>

                                {{-- Patient info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-800 truncate">{{ $entry['patientName'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $entry['doctorName'] }} &middot; {{ ucfirst(str_replace('_', ' ', $entry['visitType'])) }}</p>
                                </div>

                                {{-- Status badge --}}
                                <span class="flex-shrink-0 inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold
                                    {{ $entry['status'] === 'waiting'            ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $entry['status'] === 'with_nurse'         ? 'bg-violet-100 text-violet-800' : '' }}
                                    {{ $entry['status'] === 'awaiting_doctor'    ? 'bg-orange-100 text-orange-800' : '' }}
                                    {{ $entry['status'] === 'with_doctor'        ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $entry['status'] === 'awaiting_checkout'  ? 'bg-purple-100 text-purple-800' : '' }}">
                                    {{ $entry['statusLabel'] }}
                                </span>

                                {{-- Complete checkout button (shown only when awaiting checkout) --}}
                                @if($entry['status'] === 'awaiting_checkout')
                                <button
                                    wire:click="completeCheckout({{ $entry['id'] }})"
                                    wire:confirm="Mark {{ $entry['patientName'] }} as checked out?"
                                    class="flex-shrink-0 px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-600 text-white hover:bg-purple-700 transition"
                                    title="Complete checkout">
                                    Checkout ✓
                                </button>
                                @endif

                                {{-- Remove button --}}
                                <button
                                    wire:click="removeFromQueue({{ $entry['id'] }})"
                                    wire:confirm="Remove {{ $entry['patientName'] }} from the queue?"
                                    class="flex-shrink-0 p-1 rounded text-slate-300 hover:text-red-500 hover:bg-red-50 transition"
                                    title="Remove from queue">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Real-time Echo --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            @if(app('App\Tenancy\TenantContext')->get())
                const tenantUuid = "{{ app('App\Tenancy\TenantContext')->get()->uuid }}";
                const branchId   = {{ $branchId }};
                const channel    = `tenant.${tenantUuid}.branch.${branchId}.queue`;
                if (window.Echo) {
                    window.Echo.channel(channel).listen('.queue.updated', () => {
                        @this.loadQueue();
                        @this.loadDoctorStatuses();
                    });
                }
            @endif
        });
    </script>
</div>
