<div class="flex flex-col h-full">

    {{-- Top bar --}}
    <div class="page-topbar">
        <h1 class="text-base font-semibold">Nursing Station</h1>
        <div class="flex items-center gap-3">
            @if($successMessage)
                <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-lg"
                      x-data x-init="setTimeout(() => $el.remove(), 4000)">
                    {{ $successMessage }}
                </span>
            @endif
            @if($errorMessage)
                <span class="text-xs text-red-700 bg-red-50 border border-red-200 px-3 py-1 rounded-lg">
                    {{ $errorMessage }}
                </span>
            @endif
            <button wire:click="loadQueue" class="topbar-btn">Refresh</button>
        </div>
    </div>

    <div class="flex flex-1 min-h-0">

        {{-- ══════════════════════════════════════════════════════════
             LEFT PANEL — Patient queue
        ══════════════════════════════════════════════════════════ --}}
        <div class="w-72 flex-shrink-0 border-r border-slate-200 bg-white flex flex-col">

            <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Patients</span>
                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">{{ count($queue) }}</span>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                @forelse($queue as $entry)
                    <div class="px-4 py-3 border-l-2 transition
                               {{ $activeEntryId === $entry['id'] ? 'bg-blue-50 border-l-blue-600' : 'border-l-transparent hover:bg-slate-50' }}">
                        <div class="flex items-center justify-between gap-2">
                            <button wire:click="selectPatient({{ $entry['id'] }})" class="flex-1 text-left min-w-0">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span class="text-xs font-mono font-bold {{ $activeEntryId === $entry['id'] ? 'text-blue-600' : 'text-slate-400' }}">#{{ $entry['queue_number'] }}</span>
                                    @if($entry['is_priority'])
                                        <span class="text-xs font-bold text-red-600">PRIORITY</span>
                                    @endif
                                </div>
                                <p class="text-sm font-semibold {{ $activeEntryId === $entry['id'] ? 'text-blue-800' : 'text-slate-800' }} truncate">{{ $entry['patient_name'] }}</p>
                                <span class="text-xs
                                    @if($entry['status'] === 'with_nurse') text-violet-600
                                    @elseif($entry['status'] === 'in_observation') text-teal-600
                                    @else text-amber-600 @endif">{{ $entry['status_label'] }}</span>
                            </button>
                            @if($entry['status'] === 'waiting')
                                <button wire:click="callInPatient({{ $entry['id'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="callInPatient({{ $entry['id'] }})"
                                        class="flex-shrink-0 px-2.5 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-lg transition">
                                    Call In
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                        <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm text-slate-500 font-medium">Queue is clear</p>
                        <p class="text-xs text-slate-400 mt-1">No patients waiting.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             RIGHT PANEL — Assessment wizard
        ══════════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50">

            @if(!$activeEntryId)
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <svg class="w-14 h-14 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <p class="text-base font-semibold text-slate-500">Select a patient to begin</p>
                    <p class="text-sm text-slate-400 mt-1">Choose a patient from the queue on the left.</p>
                </div>

            @else
                {{-- Patient header --}}
                <div class="flex-shrink-0 bg-white border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500">
                                @if($activeEntryStatus === 'in_observation') In Observation @else Assessing @endif
                            </p>
                            <p class="text-lg font-bold text-slate-800">{{ $activePatientName }}</p>
                        </div>
                        @if($activeEntryStatus === 'in_observation')
                            <button wire:click="markReadyForDoctorReview"
                                    wire:confirm="Flag this patient as ready for the doctor's review?"
                                    wire:loading.attr="disabled"
                                    class="btn btn-success bg-violet-600 hover:bg-violet-700 border-violet-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span wire:loading.remove wire:target="markReadyForDoctorReview">Ready for Doctor</span>
                                <span wire:loading wire:target="markReadyForDoctorReview">Flagging...</span>
                            </button>
                        @else
                            <button wire:click="completeAndSendToDoctor"
                                    wire:confirm="Mark nursing complete and send this patient to the doctor?"
                                    wire:loading.attr="disabled"
                                    class="btn btn-success">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <span wire:loading.remove wire:target="completeAndSendToDoctor">Send to Doctor</span>
                                <span wire:loading wire:target="completeAndSendToDoctor">Sending...</span>
                            </button>
                        @endif
                    </div>

                    {{-- Step tabs --}}
                    <div class="flex gap-1 mt-4">
                        @if($activeEntryStatus === 'in_observation')
                            <button wire:click="setTab('observation')"
                                    class="px-4 py-2 text-sm font-medium rounded-lg transition
                                        {{ $activeTab === 'observation'
                                            ? 'bg-teal-600 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                Observation
                            </button>
                            <button wire:click="setTab('vitals')"
                                    class="px-4 py-2 text-sm font-medium rounded-lg transition
                                        {{ $activeTab === 'vitals'
                                            ? 'bg-blue-600 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                Vitals
                            </button>
                        @else
                            @foreach(['vitals' => '1. Vitals', 'assessment' => '2. Assessment', 'procedures' => '3. Procedures'] as $tab => $label)
                                <button wire:click="setTab('{{ $tab }}')"
                                        class="px-4 py-2 text-sm font-medium rounded-lg transition
                                            {{ $activeTab === $tab
                                                ? 'bg-blue-600 text-white shadow-sm'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Step content --}}
                <div class="flex-1 overflow-y-auto">

                    {{-- ══════ OBSERVATION ══════ --}}
                    @if($activeTab === 'observation')
                        <div class="p-6 space-y-5">

                            {{-- Observation order info --}}
                            @if($activeObservation)
                                <div class="rounded-xl bg-teal-50 border border-teal-200 p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-bold text-teal-800">Observation Order</p>
                                        <span class="text-xs text-teal-600">Started {{ $activeObservation['started_at'] }} · {{ $activeObservation['duration_min'] }} min</span>
                                    </div>
                                    @if($activeObservation['room_name'])
                                        <p class="text-xs text-teal-700 mb-1">Room: <strong>{{ $activeObservation['room_name'] }}</strong></p>
                                    @endif
                                    <p class="text-xs text-slate-700"><span class="font-semibold text-slate-600">Reason:</span> {{ $activeObservation['reason'] }}</p>
                                    @if($activeObservation['treatment_notes'])
                                        <p class="text-xs text-slate-700 mt-1"><span class="font-semibold text-slate-600">Instructions:</span> {{ $activeObservation['treatment_notes'] }}</p>
                                    @endif
                                </div>
                            @endif

                            {{-- New entry form --}}
                            <div class="rounded-xl bg-white border border-slate-200 p-4">
                                <p class="text-sm font-semibold text-slate-700 mb-4">Record Observation Entry</p>
                                <div class="grid grid-cols-3 gap-3 mb-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">BP Systolic</label>
                                        <input type="number" wire:model="obsBpSystolic" placeholder="120" min="50" max="300"
                                               class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">BP Diastolic</label>
                                        <input type="number" wire:model="obsBpDiastolic" placeholder="80" min="20" max="200"
                                               class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Pulse</label>
                                        <input type="number" wire:model="obsPulse" placeholder="72" min="20" max="300"
                                               class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">Temp (°C)</label>
                                        <input type="number" wire:model="obsTemp" step="0.1" placeholder="37.0" min="30" max="45"
                                               class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600 block mb-1">SpO₂ (%)</label>
                                        <input type="number" wire:model="obsSpo2" placeholder="98" min="50" max="100"
                                               class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="text-xs font-semibold text-slate-600 block mb-1">Notes</label>
                                    <textarea wire:model="obsNotes" rows="2"
                                              placeholder="e.g. IV running well, patient more comfortable, still nauseated…"
                                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none resize-none"></textarea>
                                </div>
                                <button wire:click="saveObservationEntry"
                                        wire:loading.attr="disabled"
                                        class="w-full py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition">
                                    Record Entry
                                </button>
                            </div>

                            {{-- Previous entries --}}
                            @if($activeObservation && count($activeObservation['entries']) > 0)
                                <div class="rounded-xl bg-white border border-slate-200 overflow-hidden">
                                    <div class="px-4 py-3 border-b border-slate-100">
                                        <p class="text-sm font-semibold text-slate-700">Previous Entries</p>
                                    </div>
                                    <div class="divide-y divide-slate-100">
                                        @foreach(array_reverse($activeObservation['entries']) as $oe)
                                            <div class="px-4 py-3">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-semibold text-slate-500">{{ $oe['time'] }} · {{ $oe['recorded_by'] }}</span>
                                                </div>
                                                <div class="flex flex-wrap gap-3 text-xs text-slate-700">
                                                    <span>BP <strong>{{ $oe['bp'] }}</strong></span>
                                                    @if($oe['pulse']) <span>P <strong>{{ $oe['pulse'] }}</strong></span> @endif
                                                    @if($oe['temp']) <span>Temp <strong>{{ $oe['temp'] }}°C</strong></span> @endif
                                                    @if($oe['spo2']) <span>SpO₂ <strong>{{ $oe['spo2'] }}%</strong></span> @endif
                                                </div>
                                                @if($oe['notes'])
                                                    <p class="text-xs text-slate-500 mt-1 italic">{{ $oe['notes'] }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Flag for review --}}
                            <div class="rounded-xl bg-violet-50 border border-violet-200 p-4 text-center">
                                <p class="text-sm text-violet-800 font-semibold mb-1">Ready for the doctor?</p>
                                <p class="text-xs text-violet-600 mb-3">When vitals are stable and treatment administered, flag this patient for the doctor's review.</p>
                                <button wire:click="markReadyForDoctorReview"
                                        wire:confirm="Flag this patient as ready for the doctor's review?"
                                        class="px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold transition">
                                    Flag for Doctor Review
                                </button>
                            </div>
                        </div>

                    {{-- ══════ VITALS ══════ --}}
                    @elseif($activeTab === 'vitals')
                        <form wire:submit="saveVitals" class="p-6 space-y-5">

                            {{-- Section: Blood Pressure & Pulse --}}
                            <div class="bg-white rounded-xl border border-slate-200 p-5">
                                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-4">Cardiovascular</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Systolic <span class="text-slate-400 font-normal">mmHg</span></label>
                                        <input wire:model="systolic" wire:change="autoSaveVitals" type="number" min="50" max="300"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        @error('systolic') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Diastolic <span class="text-slate-400 font-normal">mmHg</span></label>
                                        <input wire:model="diastolic" wire:change="autoSaveVitals" type="number" min="20" max="200"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        @error('diastolic') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Pulse <span class="text-slate-400 font-normal">bpm</span></label>
                                        <input wire:model="pulse" wire:change="autoSaveVitals" type="number" min="20" max="300"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        @error('pulse') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Resp. Rate <span class="text-slate-400 font-normal">br/min</span></label>
                                        <input wire:model="respiratoryRate" wire:change="autoSaveVitals" type="number" min="1" max="60"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        @error('respiratoryRate') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Section: SpO2, Temp, Pain --}}
                            <div class="bg-white rounded-xl border border-slate-200 p-5">
                                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-4">Respiratory & Temperature</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">SpO₂ <span class="text-slate-400 font-normal">%</span></label>
                                        <input wire:model="oxygenSat" wire:change="autoSaveVitals" type="number" step="0.1" min="50" max="100"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Temp <span class="text-slate-400 font-normal">°C</span></label>
                                        <input wire:model="temperature" wire:change="autoSaveVitals" type="number" step="0.1" min="30" max="45"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Site</label>
                                        <select wire:model="tempSite" wire:change="autoSaveVitals"
                                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10">
                                            <option value="oral">Oral</option>
                                            <option value="axillary">Axillary</option>
                                            <option value="rectal">Rectal</option>
                                            <option value="tympanic">Tympanic</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Pain Score <span class="text-slate-400 font-normal">0–10</span></label>
                                        <input wire:model="painScore" wire:change="autoSaveVitals" type="number" min="0" max="10"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                    </div>
                                </div>
                            </div>

                            {{-- Section: Anthropometrics --}}
                            <div class="bg-white rounded-xl border border-slate-200 p-5">
                                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-4">Anthropometrics</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Weight <span class="text-slate-400 font-normal">kg</span></label>
                                        <input wire:model.live="weightKg" wire:change="autoSaveVitals" type="number" step="0.1" min="0.5" max="500"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Height <span class="text-slate-400 font-normal">cm</span></label>
                                        <input wire:model.live="heightCm" wire:change="autoSaveVitals" type="number" step="0.1" min="20" max="250"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">BMI <span class="text-slate-400 font-normal">(auto)</span></label>
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 h-[38px] flex items-center">
                                            {{ $bmi ?: '—' }}
                                        </div>
                                    </div>
                                    <div></div>
                                </div>
                            </div>

                            {{-- Section: Glucose --}}
                            <div class="bg-white rounded-xl border border-slate-200 p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Blood Glucose</h3>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full
                                        {{ $bloodGlucoseUnit === 'mg_dl' ? 'bg-orange-100 text-orange-700 border border-orange-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                        {{ $bloodGlucoseUnit === 'mg_dl' ? 'mg/dL' : 'mmol/L' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">
                                            Blood Glucose
                                            <span class="text-slate-400 font-normal">
                                                {{ $bloodGlucoseUnit === 'mg_dl' ? '(mg/dL — normal 70–140)' : '(mmol/L — normal 3.9–7.8)' }}
                                            </span>
                                        </label>
                                        @if($bloodGlucoseUnit === 'mg_dl')
                                            <input wire:model="bloodGlucose" wire:change="autoSaveVitals" type="number" step="1" min="10" max="720"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        @else
                                            <input wire:model="bloodGlucose" wire:change="autoSaveVitals" type="number" step="0.1" min="0.5" max="40"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        @endif
                                        @error('bloodGlucose') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Timing</label>
                                        <select wire:model="glucoseTiming" wire:change="autoSaveVitals"
                                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10">
                                            <option value="fasting">Fasting</option>
                                            <option value="random">Random</option>
                                            <option value="post_prandial">Post-prandial</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl border border-slate-200 p-5">
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Nursing Notes</label>
                                <textarea wire:model="vitalsNotes" wire:change="autoSaveVitals" rows="2"
                                          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10 resize-none"
                                          placeholder="Any observations..."></textarea>
                            </div>

                            <div class="flex items-center justify-between">
                                <p class="text-xs text-slate-400">All fields optional — record what's relevant.</p>
                                <div class="flex gap-3">
                                    <button type="submit"
                                            wire:loading.attr="disabled"
                                            class="btn btn-primary">
                                        <span wire:loading.remove wire:target="saveVitals">Save Vitals</span>
                                        <span wire:loading wire:target="saveVitals">Saving...</span>
                                    </button>
                                    <button type="button" wire:click="setTab('assessment')"
                                            class="btn btn-secondary">
                                        Next: Assessment
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- ══════ ASSESSMENT ══════ --}}
                    @if($activeTab === 'assessment')
                        <form wire:submit="saveAssessment" class="p-6 space-y-5">

                            {{-- ── ALLERGIES ── --}}
                            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Known Allergies</h3>
                                    <button type="button" wire:click="$set('showAddAllergy', true)"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add
                                    </button>
                                </div>

                                @if(count($patientAllergies) > 0)
                                    <div class="space-y-1.5">
                                        @foreach($patientAllergies as $a)
                                            <div class="flex items-center gap-3 rounded-lg bg-red-50 border border-red-100 px-3 py-2 text-sm">
                                                <span class="font-medium text-red-800">{{ $a['allergen'] }}</span>
                                                @if($a['reaction'])
                                                    <span class="text-red-600 text-xs">→ {{ $a['reaction'] }}</span>
                                                @endif
                                                <span class="ml-auto text-xs font-semibold
                                                    {{ $a['severity'] === 'Life-threatening' ? 'text-red-700' :
                                                       ($a['severity'] === 'Severe' ? 'text-orange-700' : 'text-amber-700') }}">
                                                    {{ $a['severity'] }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400 italic">No known allergies on record.</p>
                                @endif

                                @if($showAddAllergy)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-2 mt-2">
                                        <p class="text-xs font-semibold text-slate-600">Add Allergy</p>
                                        @error('newAllergen') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="col-span-2">
                                                <input wire:model="newAllergen" type="text" placeholder="Allergen *"
                                                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                            </div>
                                            <input wire:model="newAllergyReaction" type="text" placeholder="Reaction"
                                                   class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                            <select wire:model="newAllergySeverity"
                                                    class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10">
                                                <option value="mild">Mild</option>
                                                <option value="moderate">Moderate</option>
                                                <option value="severe">Severe</option>
                                                <option value="life_threatening">Life-threatening</option>
                                            </select>
                                        </div>
                                        <div class="flex gap-2 pt-1">
                                            <button type="button" wire:click="addAllergy"
                                                    class="btn btn-primary btn-sm">Save</button>
                                            <button type="button" wire:click="$set('showAddAllergy', false)"
                                                    class="btn btn-secondary btn-sm">Cancel</button>
                                        </div>
                                    </div>
                                @endif

                                <label class="flex items-center gap-4 p-3 rounded-lg cursor-pointer transition mt-2
                                              {{ $allergiesReviewed ? 'bg-emerald-50 border border-emerald-200' : 'bg-slate-50 border border-slate-200 hover:border-slate-300' }}">
                                    <input wire:model.live="allergiesReviewed" type="checkbox"
                                           class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">Allergies reviewed with patient</p>
                                        <p class="text-xs text-slate-500">Tick to confirm allergy list is accurate</p>
                                    </div>
                                    @if($allergiesReviewed)
                                        <svg class="w-5 h-5 text-emerald-600 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </label>
                            </div>

                            {{-- ── MEDICATIONS ── --}}
                            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Current Medications</h3>
                                    <button type="button" wire:click="$set('showAddMedication', true)"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add
                                    </button>
                                </div>

                                @if(count($patientMedications) > 0)
                                    <div class="space-y-1.5">
                                        @foreach($patientMedications as $m)
                                            <div class="flex items-center gap-3 rounded-lg bg-blue-50 border border-blue-100 px-3 py-2 text-sm">
                                                <span class="font-medium text-blue-800">{{ $m['name'] }}</span>
                                                @if($m['dosage'])
                                                    <span class="text-blue-600 text-xs">{{ $m['dosage'] }}</span>
                                                @endif
                                                @if($m['frequency'])
                                                    <span class="ml-auto text-xs text-slate-500">{{ $m['frequency'] }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400 italic">No current medications on record.</p>
                                @endif

                                @if($showAddMedication)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-2 mt-2">
                                        <p class="text-xs font-semibold text-slate-600">Add Medication</p>
                                        @error('newMedName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="col-span-2">
                                                <input wire:model="newMedName" type="text" placeholder="Medication name *"
                                                       class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                            </div>
                                            <input wire:model="newMedDosage" type="text" placeholder="Dosage (e.g. 10mg)"
                                                   class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                            <input wire:model="newMedFrequency" type="text" placeholder="Frequency (e.g. twice daily)"
                                                   class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        </div>
                                        <div class="flex gap-2 pt-1">
                                            <button type="button" wire:click="addMedication"
                                                    class="btn btn-primary btn-sm">Save</button>
                                            <button type="button" wire:click="$set('showAddMedication', false)"
                                                    class="btn btn-secondary btn-sm">Cancel</button>
                                        </div>
                                    </div>
                                @endif

                                <label class="flex items-center gap-4 p-3 rounded-lg cursor-pointer transition mt-2
                                              {{ $medicationsReviewed ? 'bg-emerald-50 border border-emerald-200' : 'bg-slate-50 border border-slate-200 hover:border-slate-300' }}">
                                    <input wire:model.live="medicationsReviewed" type="checkbox"
                                           class="w-5 h-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">Medications reviewed with patient</p>
                                        <p class="text-xs text-slate-500">Tick to confirm medication list is up to date</p>
                                    </div>
                                    @if($medicationsReviewed)
                                        <svg class="w-5 h-5 text-emerald-600 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </label>
                            </div>

                            <div class="bg-white rounded-xl border border-slate-200 p-5">
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Assessment Notes</label>
                                <textarea wire:model="assessmentNotes" rows="4"
                                          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10 resize-none"
                                          placeholder="Nursing observations, patient concerns, additional notes..."></textarea>
                            </div>

                            @if(!$allergiesReviewed || !$medicationsReviewed)
                                <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                                    Both checklist items must be ticked before you can send the patient to the doctor.
                                </div>
                            @endif

                            <div class="flex items-center justify-between">
                                <button type="button" wire:click="setTab('vitals')"
                                        class="btn btn-ghost">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    Back to Vitals
                                </button>
                                <div class="flex gap-3">
                                    <button type="submit"
                                            wire:loading.attr="disabled"
                                            class="btn btn-primary">
                                        <span wire:loading.remove wire:target="saveAssessment">Save Assessment</span>
                                        <span wire:loading wire:target="saveAssessment">Saving...</span>
                                    </button>
                                    <button type="button" wire:click="completeAndSendToDoctor"
                                            wire:confirm="Send this patient to the doctor now?"
                                            @disabled(!$allergiesReviewed || !$medicationsReviewed)
                                            class="btn btn-success">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                        Send to Doctor
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- ══════ PROCEDURES ══════ --}}
                    @if($activeTab === 'procedures')
                        <div class="p-6 space-y-4">

                            @if(!$showProcedureForm)
                                <div class="flex justify-end">
                                    <button wire:click="$set('showProcedureForm', true)"
                                            class="btn btn-primary">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add Procedure
                                    </button>
                                </div>
                            @else
                                <form wire:submit="saveProcedure"
                                      class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                                    <h3 class="text-sm font-semibold text-slate-700">Record Procedure</h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Type</label>
                                            <select wire:model="procedureType"
                                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10">
                                                <option value="">Select type...</option>
                                                @foreach(\App\Models\Tenant\NursingProcedure::TYPES as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('procedureType') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Name / Description</label>
                                            <input wire:model="procedureName" type="text" maxlength="200"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                            @error('procedureName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Site</label>
                                            <input wire:model="procedureSite" type="text"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Outcome</label>
                                            <input wire:model="procedureOutcome" type="text"
                                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Details</label>
                                        <textarea wire:model="procedureDetails" rows="2"
                                                  class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/10 resize-none"></textarea>
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" wire:click="$set('showProcedureForm', false)"
                                                class="btn btn-secondary">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                                class="btn btn-primary">
                                            Save Procedure
                                        </button>
                                    </div>
                                </form>
                            @endif

                            {{-- Procedure history --}}
                            @if($activeEncounterId)
                                @php
                                    $procedures = \App\Models\Tenant\NursingProcedure::on('tenant')
                                        ->where('encounter_id', $activeEncounterId)
                                        ->orderByDesc('performed_at')
                                        ->get();
                                @endphp
                                @if($procedures->isNotEmpty())
                                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-slate-50 border-b border-slate-200">
                                                <tr>
                                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Procedure</th>
                                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Site</th>
                                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Outcome</th>
                                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Time</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($procedures as $proc)
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="px-4 py-3">
                                                            <p class="font-medium text-slate-800">{{ $proc->type_label }}</p>
                                                            @if($proc->procedure_name !== $proc->type_label)
                                                                <p class="text-xs text-slate-500">{{ $proc->procedure_name }}</p>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-slate-600">{{ $proc->site ?? '—' }}</td>
                                                        <td class="px-4 py-3 text-slate-600">{{ $proc->outcome ?? '—' }}</td>
                                                        <td class="px-4 py-3 text-xs text-slate-400">{{ $proc->performed_at?->format('H:i') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-sm text-slate-400 text-center py-8">No procedures recorded yet.</p>
                                @endif
                            @endif
                        </div>
                    @endif

                </div>
            @endif
        </div>
    </div>
</div>
