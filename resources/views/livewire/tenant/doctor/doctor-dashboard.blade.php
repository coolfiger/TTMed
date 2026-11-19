<div class="flex h-full min-h-0 overflow-hidden bg-slate-100">

    {{-- ══════════════════════════════════════════════════════
         LEFT: Queue panel
    ══════════════════════════════════════════════════════ --}}
    <div class="w-64 flex-shrink-0 flex flex-col bg-white border-r border-slate-200">

        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
            <div>
                <p class="text-sm font-semibold text-slate-800">Doctor Queue</p>
                <p class="text-xs text-slate-400">{{ today()->format('d M Y') }}</p>
            </div>
            <button wire:click="loadQueue"
                    class="text-slate-400 hover:text-blue-600 transition"
                    title="Refresh queue">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">

            {{-- Active / in-consultation patients (with_doctor) --}}
            @php $activePatients = collect($queue)->where('status', 'with_doctor'); @endphp
            @if($activePatients->count() > 0)
                <div class="px-3 pt-3 pb-1">
                    <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">
                        In Consultation · {{ $activePatients->count() }}
                    </p>
                </div>
                @foreach($activePatients as $entry)
                    <button wire:click="selectPatient({{ $entry['id'] }})"
                            class="w-full text-left px-3 py-2.5 transition
                                   {{ $activeEntryId === $entry['id']
                                       ? 'bg-blue-50 border-l-4 border-blue-600'
                                       : 'hover:bg-slate-50 border-l-4 border-emerald-400' }}">
                        <div class="flex items-start justify-between gap-1.5">
                            <div class="min-w-0">
                                <p class="text-xs font-mono text-slate-400">#{{ $entry['queue_number'] }}
                                    @if($entry['is_priority'])
                                        <span class="text-red-600 font-bold ml-1">PRIORITY</span>
                                    @endif
                                </p>
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $entry['patient_name'] }}</p>
                            </div>
                            @if($activeEntryId === $entry['id'])
                                <span class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-semibold flex-shrink-0 mt-0.5">On screen</span>
                            @else
                                <span class="text-xs px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold flex-shrink-0 mt-0.5">Switch</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            @endif

            {{-- Awaiting doctor patients --}}
            @php $waitingPatients = collect($queue)->where('status', 'awaiting_doctor'); @endphp
            @if($waitingPatients->count() > 0)
                <div class="px-3 pt-3 pb-1 {{ $activePatients->count() > 0 ? 'mt-2 border-t border-slate-100' : '' }}">
                    <p class="text-xs font-semibold text-orange-600 uppercase tracking-wide">
                        Awaiting · {{ $waitingPatients->count() }}
                    </p>
                </div>
                @foreach($waitingPatients as $entry)
                    <div class="px-3 py-2.5 border-l-4 border-orange-200 hover:bg-slate-50 transition">
                        <div class="flex items-start justify-between gap-2">
                            <button wire:click="selectPatient({{ $entry['id'] }})"
                                    class="text-left min-w-0 flex-1">
                                <p class="text-xs font-mono text-slate-400">#{{ $entry['queue_number'] }}
                                    @if($entry['is_priority'])
                                        <span class="text-red-600 font-bold ml-1">PRIORITY</span>
                                    @endif
                                </p>
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $entry['patient_name'] }}</p>
                            </button>
                            <button wire:click="callPatient({{ $entry['id'] }})"
                                    wire:confirm="Call this patient in?"
                                    class="flex-shrink-0 text-xs font-semibold px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white transition mt-0.5">
                                Call In
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif

            @if(count($queue) === 0)
                <p class="px-4 py-10 text-center text-sm text-slate-400 italic">No patients in your queue.</p>
            @endif

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         No patient selected — empty state
    ══════════════════════════════════════════════════════ --}}
    @if(!$activeEntryId)
        <div class="flex-1 flex flex-col items-center justify-center text-center px-8">
            <div class="w-16 h-16 rounded-full bg-slate-200 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <p class="text-slate-600 font-medium">No patient selected</p>
            <p class="text-slate-400 text-sm mt-1">Call in a patient from the queue to begin a consultation.</p>
        </div>

    @else

    {{-- ══════════════════════════════════════════════════════
         CENTER: Consultation workspace
    ══════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col min-w-0 min-h-0">

        {{-- Patient header bar --}}
        <div class="flex-shrink-0 bg-white border-b border-slate-200 px-5 py-3 flex items-center gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-base font-semibold text-slate-800">{{ $activePatientName }}</p>
                    @if(count($allergies) > 0)
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full"
                              title="{{ collect($allergies)->pluck('allergen')->join(', ') }}">
                            ⚠ {{ count($allergies) }} {{ Str::plural('Allergy', count($allergies)) }}
                        </span>
                    @endif
                    @if(count($medications) > 0)
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">
                            💊 {{ count($medications) }} Med{{ count($medications) !== 1 ? 's' : '' }}
                        </span>
                    @endif
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                        {{ $activeEntryStatus === 'with_doctor' ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700' }}">
                        {{ $activeEntryStatus === 'with_doctor' ? 'In Consultation' : 'Awaiting Doctor' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                @if($activeEntryStatus !== 'with_doctor')
                    <button wire:click="callPatient({{ $activeEntryId }})"
                            wire:confirm="Call this patient in?"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                        Call In Patient
                    </button>
                @else
                    @if($noteIsFinalized)
                        <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 font-semibold">Note Finalized</span>
                        <button wire:click="completeEncounter"
                                wire:confirm="Complete consultation and send patient to reception for checkout?"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Complete &amp; Send to Reception
                        </button>
                    @else
                        <button wire:click="saveNote"
                                class="text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-500 text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Save Note
                        </button>
                        <button wire:click="finalizeNote"
                                wire:confirm="Finalize this consultation note? It will become read-only."
                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Finalize Note
                        </button>
                    @endif
                @endif
            </div>
        </div>

        {{-- Alerts --}}
        @if($successMessage)
            <div class="mx-5 mt-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800 flex-shrink-0"
                 x-data x-init="setTimeout(() => $el.remove(), 4000)">
                {{ $successMessage }}
            </div>
        @endif
        @if($errorMessage)
            <div class="mx-5 mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-800 flex-shrink-0">
                {{ $errorMessage }}
                <button wire:click="$set('errorMessage', '')" class="ml-2 text-red-500 hover:text-red-700 text-xs">✕</button>
            </div>
        @endif

        {{-- Scrollable consultation form --}}
        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

            {{-- Finalized banner --}}
            @if($noteIsFinalized)
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-2 text-sm text-amber-800">
                    This consultation note has been finalized and is read-only.
                </div>
            @endif

            {{-- ── Today's vitals (read-only summary from nurse) ── --}}
            @if(count($vitals) > 0)
                @php $latestVitals = $vitals[0]; @endphp
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                        Vitals from Nurse
                        <span class="font-normal normal-case text-slate-400 ml-1">
                            {{ \Carbon\Carbon::parse($latestVitals['recorded_at'])->format('H:i') }}
                        </span>
                    </p>
                    <div class="grid grid-cols-4 gap-2 text-sm">
                        @if($latestVitals['systolic_bp'] && $latestVitals['diastolic_bp'])
                            <div class="bg-slate-50 rounded px-2 py-1.5 text-center">
                                <p class="text-xs text-slate-400">BP</p>
                                <p class="font-semibold text-slate-800">{{ $latestVitals['systolic_bp'] }}/{{ $latestVitals['diastolic_bp'] }}</p>
                                <p class="text-xs text-slate-400">mmHg</p>
                            </div>
                        @endif
                        @if($latestVitals['pulse_rate'])
                            <div class="bg-slate-50 rounded px-2 py-1.5 text-center">
                                <p class="text-xs text-slate-400">Pulse</p>
                                <p class="font-semibold text-slate-800">{{ $latestVitals['pulse_rate'] }}</p>
                                <p class="text-xs text-slate-400">bpm</p>
                            </div>
                        @endif
                        @if($latestVitals['temperature'])
                            <div class="bg-slate-50 rounded px-2 py-1.5 text-center">
                                <p class="text-xs text-slate-400">Temp</p>
                                <p class="font-semibold text-slate-800">{{ $latestVitals['temperature'] }}°C</p>
                                <p class="text-xs text-slate-400">{{ $latestVitals['temperature_site'] ?? '' }}</p>
                            </div>
                        @endif
                        @if($latestVitals['oxygen_saturation'])
                            <div class="bg-slate-50 rounded px-2 py-1.5 text-center">
                                <p class="text-xs text-slate-400">SpO₂</p>
                                <p class="font-semibold text-slate-800">{{ $latestVitals['oxygen_saturation'] }}%</p>
                                <p class="text-xs text-slate-400">&nbsp;</p>
                            </div>
                        @endif
                        @if($latestVitals['weight_kg'])
                            <div class="bg-slate-50 rounded px-2 py-1.5 text-center">
                                <p class="text-xs text-slate-400">Weight</p>
                                <p class="font-semibold text-slate-800">{{ $latestVitals['weight_kg'] }} kg</p>
                                @if($latestVitals['bmi'])
                                    <p class="text-xs text-slate-400">BMI {{ $latestVitals['bmi'] }}</p>
                                @else
                                    <p class="text-xs text-slate-400">&nbsp;</p>
                                @endif
                            </div>
                        @endif
                        @if($latestVitals['pain_score'] !== null)
                            <div class="bg-slate-50 rounded px-2 py-1.5 text-center">
                                <p class="text-xs text-slate-400">Pain</p>
                                <p class="font-semibold {{ $latestVitals['pain_score'] >= 7 ? 'text-red-600' : ($latestVitals['pain_score'] >= 4 ? 'text-amber-600' : 'text-slate-800') }}">
                                    {{ $latestVitals['pain_score'] }}/10
                                </p>
                                <p class="text-xs text-slate-400">&nbsp;</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ── SOAP Note ── --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Consultation Note (SOAP)</p>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Subjective <span class="normal-case font-normal text-slate-400">— Chief complaint, history of presenting illness</span></label>
                        <textarea wire:model="subjective" rows="3"
                                  @if($noteIsFinalized) disabled @endif
                                  placeholder="What brings the patient in today..."
                                  class="w-full rounded-lg border border-slate-200 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-500 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Objective <span class="normal-case font-normal text-slate-400">— Physical examination findings</span></label>
                        <textarea wire:model="objective" rows="3"
                                  @if($noteIsFinalized) disabled @endif
                                  placeholder="Examination findings, vital signs observations..."
                                  class="w-full rounded-lg border border-slate-200 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-500 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment <span class="normal-case font-normal text-slate-400">— Clinical impression</span></label>
                        <textarea wire:model="assessment" rows="3"
                                  @if($noteIsFinalized) disabled @endif
                                  placeholder="Clinical assessment and working diagnosis..."
                                  class="w-full rounded-lg border border-slate-200 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-500 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Plan <span class="normal-case font-normal text-slate-400">— Treatment, prescriptions, instructions</span></label>
                        <textarea wire:model="plan" rows="4"
                                  @if($noteIsFinalized) disabled @endif
                                  placeholder="Medications prescribed, investigations ordered, patient instructions..."
                                  class="w-full rounded-lg border border-slate-200 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-500 resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Investigations Ordered</label>
                            <textarea wire:model="investigationsOrdered" rows="2"
                                      @if($noteIsFinalized) disabled @endif
                                      class="w-full rounded-lg border border-slate-200 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-500 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Examination Findings</label>
                            <textarea wire:model="examinationFindings" rows="2"
                                      @if($noteIsFinalized) disabled @endif
                                      class="w-full rounded-lg border border-slate-200 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-500 resize-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Diagnoses ── --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Diagnoses</p>
                    @if(!$noteIsFinalized)
                        <button wire:click="$set('showDiagnosisForm', true)"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                            + Add Diagnosis
                        </button>
                    @endif
                </div>

                @if($showDiagnosisForm)
                    <div class="p-4 border-b border-slate-100 bg-blue-50 space-y-3">
                        <div x-data="{ open: false }" class="relative">
                            <label class="block text-xs text-slate-500 mb-1">ICD-10 Search</label>
                            <input type="text" wire:model.live="diagnosisSearch"
                                   wire:keyup.debounce.300ms="searchIcd"
                                   x-on:focus="open = true"
                                   x-on:click.outside="open = false"
                                   placeholder="Search by code or description..."
                                   class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            @if(count($icdResults) > 0)
                                <div x-show="open"
                                     class="absolute z-10 w-full bg-white border border-slate-200 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
                                    @foreach($icdResults as $result)
                                        <button wire:click="selectIcd({{ $result['id'] }})"
                                                x-on:click="open = false"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 transition">
                                            <span class="font-mono text-blue-600">{{ $result['code'] }}</span>
                                            <span class="ml-2 text-slate-700">{{ $result['description'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if($selectedIcdId)
                            <p class="text-xs text-emerald-700 font-medium">Selected: {{ $selectedIcdCode }} — {{ $selectedIcdText }}</p>
                        @endif
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Type</label>
                                <select wire:model="diagnosisType"
                                        class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2">
                                    <option value="primary">Primary</option>
                                    <option value="secondary">Secondary</option>
                                    <option value="differential">Differential</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Certainty</label>
                                <select wire:model="certainty"
                                        class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2">
                                    <option value="confirmed">Confirmed</option>
                                    <option value="probable">Probable</option>
                                    <option value="suspected">Suspected</option>
                                    <option value="ruled_out">Ruled Out</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Diagnosis Text</label>
                            <input type="text" wire:model="selectedIcdText"
                                   class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Notes</label>
                            <textarea wire:model="diagnosisNotes" rows="2"
                                      class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button wire:click="$set('showDiagnosisForm', false)"
                                    class="text-sm text-slate-500 hover:text-slate-700 px-3 py-1.5">Cancel</button>
                            <button wire:click="saveDiagnosis"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                                Save Diagnosis
                            </button>
                        </div>
                    </div>
                @endif

                <div class="p-4">
                    @forelse($diagnoses as $d)
                        <div class="flex items-start justify-between gap-3 py-2 border-b border-slate-100 last:border-0">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                                    <span class="text-xs font-semibold uppercase
                                        {{ $d['diagnosis_type'] === 'primary' ? 'text-blue-700' : 'text-slate-500' }}">
                                        {{ ucfirst($d['diagnosis_type']) }}
                                    </span>
                                    <span class="text-xs px-1.5 py-0.5 rounded
                                        {{ $d['certainty'] === 'confirmed' ? 'bg-emerald-100 text-emerald-800'
                                            : ($d['certainty'] === 'ruled_out' ? 'bg-slate-100 text-slate-600'
                                            : 'bg-amber-100 text-amber-800') }}">
                                        {{ ucfirst(str_replace('_', ' ', $d['certainty'])) }}
                                    </span>
                                    @if($d['icd10_code'])
                                        <span class="text-xs font-mono text-blue-600">{{ $d['icd10_code'] }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-800">{{ $d['diagnosis_text'] }}</p>
                                @if($d['notes'])
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $d['notes'] }}</p>
                                @endif
                            </div>
                            @if(!$noteIsFinalized)
                                <button wire:click="removeDiagnosis({{ $d['id'] }})"
                                        wire:confirm="Remove this diagnosis?"
                                        class="text-red-400 hover:text-red-600 text-xs flex-shrink-0">Remove</button>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 italic text-center py-3">No diagnoses recorded yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- ── Follow-up ── --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Follow-up &amp; Instructions for Reception</p>
                </div>
                <div class="p-4 grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs text-slate-500 mb-1">Follow-up Instructions</label>
                        <textarea wire:model="followUpInstructions" rows="2"
                                  @if($noteIsFinalized) disabled @endif
                                  placeholder="Instructions for patient and reception (e.g. return in 2 weeks for BP check)..."
                                  class="w-full rounded-lg border border-slate-200 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-slate-50 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Follow-up in (days)</label>
                        <input type="number" wire:model="followUpDays" min="1" max="365"
                               @if($noteIsFinalized) disabled @endif
                               placeholder="e.g. 14"
                               class="w-full rounded-lg border border-slate-200 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-slate-50" />
                        <p class="text-xs text-slate-400 mt-1">Reception will schedule this automatically.</p>
                    </div>
                </div>
            </div>

            {{-- ── Referrals ── --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Referrals</p>
                    @if(!$noteIsFinalized)
                        <button wire:click="$set('showReferralForm', true)"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                            + Add Referral
                        </button>
                    @endif
                </div>

                @if($showReferralForm)
                    <div class="p-4 border-b border-slate-100 bg-blue-50 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs text-slate-500 mb-1">Referred To *</label>
                                <input type="text" wire:model="referredToName" placeholder="Specialist or facility name"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Specialty</label>
                                <input type="text" wire:model="referredToSpecialty"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Urgency</label>
                                <select wire:model="referralUrgency"
                                        class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2">
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-slate-500 mb-1">Reason *</label>
                                <textarea wire:model="referralReason" rows="2"
                                          class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-slate-500 mb-1">Clinical Summary</label>
                                <textarea wire:model="clinicalSummary" rows="2"
                                          class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-slate-500 mb-1">Instructions for Patient</label>
                                <textarea wire:model="patientInstructions" rows="2"
                                          class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button wire:click="$set('showReferralForm', false)"
                                    class="text-sm text-slate-500 hover:text-slate-700 px-3 py-1.5">Cancel</button>
                            <button wire:click="saveReferral"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                                Save Referral
                            </button>
                        </div>
                    </div>
                @endif

                <div class="p-4">
                    @forelse($referrals as $r)
                        <div class="flex items-start justify-between gap-3 py-2 border-b border-slate-100 last:border-0">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                    <p class="text-sm font-semibold text-slate-800">{{ $r['referred_to_name'] }}</p>
                                    @if($r['referred_to_specialty'])
                                        <span class="text-xs text-slate-500">{{ $r['referred_to_specialty'] }}</span>
                                    @endif
                                    <span class="text-xs px-1.5 py-0.5 rounded-full
                                        {{ $r['urgency'] === 'emergency' ? 'bg-red-100 text-red-800'
                                            : ($r['urgency'] === 'urgent' ? 'bg-orange-100 text-orange-800'
                                            : 'bg-slate-100 text-slate-600') }}">
                                        {{ ucfirst($r['urgency']) }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">{{ $r['reason'] }}</p>
                            </div>
                            <span class="text-xs text-slate-400 flex-shrink-0">{{ ucfirst($r['status']) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 italic text-center py-3">No referrals for this visit.</p>
                    @endforelse
                </div>
            </div>

            {{-- Bottom spacer --}}
            <div class="h-4"></div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════
         RIGHT: Patient context panel
    ══════════════════════════════════════════════════════ --}}
    <div class="w-80 flex-shrink-0 flex flex-col bg-white border-l border-slate-200 overflow-y-auto"
         x-data="{
             open: {
                 allergies: {{ count($allergies) > 0 ? 'true' : 'true' }},
                 meds: {{ count($medications) > 0 ? 'true' : 'false' }},
                 history: true,
                 labs: false,
                 imaging: {{ count($imagingReports) > 0 ? 'true' : 'false' }}
             }
         }">

        <div class="px-4 py-3 border-b border-slate-200 flex-shrink-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Patient Context</p>
        </div>

        {{-- Allergies --}}
        <div class="border-b border-slate-100">
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="open.allergies = !open.allergies">
                <span class="text-xs font-semibold text-red-700 uppercase tracking-wide flex items-center gap-1.5">
                    @if(count($allergies) > 0)
                        <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                    @endif
                    Allergies ({{ count($allergies) }})
                </span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open.allergies ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open.allergies" x-collapse class="px-4 pb-3">
                @if(count($allergies) > 0)
                    <div class="space-y-1.5">
                        @foreach($allergies as $allergy)
                            <div class="bg-red-50 border border-red-100 rounded px-2.5 py-1.5 text-xs">
                                <span class="font-semibold text-red-800">{{ $allergy['allergen'] }}</span>
                                @if($allergy['reaction'])
                                    <span class="text-red-600 ml-1">→ {{ $allergy['reaction'] }}</span>
                                @endif
                                @if($allergy['severity'])
                                    <span class="ml-1 px-1 py-0.5 rounded text-xs
                                        {{ $allergy['severity'] === 'severe' ? 'bg-red-200 text-red-800' : 'bg-orange-100 text-orange-700' }}">
                                        {{ ucfirst($allergy['severity']) }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">No known drug allergies.</p>
                @endif
            </div>
        </div>

        {{-- Current Medications --}}
        <div class="border-b border-slate-100">
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="open.meds = !open.meds">
                <span class="text-xs font-semibold text-purple-700 uppercase tracking-wide">
                    Current Medications ({{ count($medications) }})
                </span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open.meds ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open.meds" x-collapse class="px-4 pb-3">
                @if(count($medications) > 0)
                    <div class="space-y-1.5">
                        @foreach($medications as $med)
                            <div class="border border-slate-100 rounded px-2.5 py-1.5 text-xs">
                                <p class="font-semibold text-slate-800">{{ $med['medication_name'] }}</p>
                                <p class="text-slate-500">{{ $med['dosage'] ?? '' }} {{ $med['frequency'] ?? '' }} {{ $med['route'] ? '· '.$med['route'] : '' }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">No current medications.</p>
                @endif
            </div>
        </div>

        {{-- Past Visits --}}
        <div class="border-b border-slate-100">
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="open.history = !open.history">
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                    Visit History ({{ count($patientHistory) }})
                </span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open.history ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open.history" x-collapse class="px-4 pb-3">
                @forelse($patientHistory as $index => $visit)
                    <div class="mb-3 last:mb-0" x-data="{ expanded: {{ $index === 0 ? 'true' : 'false' }} }">
                        <button class="w-full text-left flex items-center justify-between py-1.5 group"
                                @click="expanded = !expanded">
                            <div>
                                <p class="text-xs font-semibold text-slate-700">{{ $visit['encounter_date'] }}</p>
                                @if($visit['chief_complaint'])
                                    <p class="text-xs text-slate-500 italic truncate max-w-[200px]">{{ $visit['chief_complaint'] }}</p>
                                @endif
                            </div>
                            <svg class="w-3 h-3 text-slate-400 transition-transform flex-shrink-0 ml-2" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="expanded" x-collapse class="pl-2 border-l-2 border-slate-200 mt-1 space-y-1.5">
                            @if(count($visit['diagnoses']) > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($visit['diagnoses'] as $dx)
                                        <span class="text-xs bg-blue-50 text-blue-700 border border-blue-100 px-1.5 py-0.5 rounded-full">{{ $dx }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if($visit['assessment'])
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase">Assessment</p>
                                    <p class="text-xs text-slate-600">{{ Str::limit($visit['assessment'], 200) }}</p>
                                </div>
                            @endif
                            @if($visit['plan'])
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase">Plan</p>
                                    <p class="text-xs text-slate-600">{{ Str::limit($visit['plan'], 200) }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No previous visits on record.</p>
                @endforelse
            </div>
        </div>

        {{-- Labs --}}
        @if(count($labOrders) > 0)
        <div class="border-b border-slate-100">
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="open.labs = !open.labs">
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                    Lab Results ({{ count($labOrders) }})
                </span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open.labs ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open.labs" x-collapse class="px-4 pb-3 space-y-2">
                @foreach($labOrders as $order)
                    <div class="border border-slate-100 rounded px-2.5 py-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-slate-700">{{ $order['order_number'] }}</span>
                            @if($order['is_current'])
                                <span class="text-xs px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">This Visit</span>
                            @endif
                        </div>
                        @foreach($order['items'] ?? [] as $item)
                            <p class="text-xs font-medium text-slate-500">{{ $item['test_name'] }}</p>
                            @foreach($item['results'] ?? [] as $result)
                                <div class="flex justify-between text-xs mt-0.5">
                                    <span class="{{ ($result['flag'] ?? '') && $result['flag'] !== 'normal' ? 'font-bold text-red-600' : 'text-slate-700' }}">
                                        {{ $result['value'] }} {{ $result['value_unit'] }}
                                        @if(($result['flag'] ?? '') && $result['flag'] !== 'normal')
                                            ({{ strtoupper($result['flag']) }})
                                        @endif
                                    </span>
                                    @if($result['reference_range'])
                                        <span class="text-slate-400">Ref: {{ $result['reference_range'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        @endforeach
                        @if(count($order['attachments'] ?? []) > 0)
                            @foreach($order['attachments'] as $att)
                                <a href="{{ route('tenant.lab.attachment.view', $att['id']) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 mt-1.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    PDF Report
                                </a>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Imaging --}}
        <div class="border-b border-slate-100">
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="open.imaging = !open.imaging">
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                    Imaging ({{ count($imagingReports) }})
                </span>
                <div class="flex items-center gap-2">
                    @if(count($compareReportIds) === 2)
                        <span class="text-xs px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 font-semibold">Compare ready</span>
                    @elseif(count($compareReportIds) === 1)
                        <span class="text-xs text-slate-400">Select 1 more</span>
                    @endif
                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open.imaging ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>
            <div x-show="open.imaging" x-collapse class="px-4 pb-3">

                @if(count($compareReportIds) === 2)
                    <div class="mb-3 flex items-center gap-2 p-2 bg-indigo-50 border border-indigo-200 rounded-lg">
                        <p class="text-xs text-indigo-700 flex-1">2 selected for comparison</p>
                        <button wire:click="openComparison"
                                class="text-xs font-semibold px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded transition">
                            Compare
                        </button>
                        <button wire:click="$set('compareReportIds', [])"
                                class="text-xs text-slate-500 hover:text-slate-700">Clear</button>
                    </div>
                @endif

                @forelse($imagingReports as $report)
                    <div class="mb-2 last:mb-0 border border-slate-100 rounded px-2.5 py-2
                        {{ $report['is_current'] ? 'border-blue-200 bg-blue-50' : 'bg-white' }}">
                        <div class="flex items-start gap-2">
                            @if($report['has_pdf'])
                                <input type="checkbox"
                                       wire:click="toggleCompare({{ $report['id'] }})"
                                       @checked(in_array($report['id'], $compareReportIds))
                                       @disabled(!in_array($report['id'], $compareReportIds) && count($compareReportIds) >= 2)
                                       class="mt-0.5 w-3.5 h-3.5 rounded text-indigo-600 cursor-pointer flex-shrink-0"
                                       title="Select for comparison" />
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-semibold text-slate-800">
                                        {{ strtoupper($report['modality'] ?? '—') }}
                                        @if($report['body_part']) — {{ $report['body_part'] }} @endif
                                    </span>
                                    @if($report['is_current'])
                                        <span class="text-xs px-1 py-0.5 rounded bg-blue-100 text-blue-700">This Visit</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-400">{{ $report['report_date'] }}</p>
                                @if($report['impression'])
                                    <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $report['impression'] }}</p>
                                @endif
                                @if($report['has_pdf'])
                                    <a href="{{ route('tenant.imaging.report.view', $report['id']) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 mt-1.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        View Report
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No imaging reports on record.</p>
                @endforelse
            </div>
        </div>

        <div class="h-4 flex-shrink-0"></div>

    </div>

    @endif {{-- end activeEntryId --}}

</div>

{{-- ══════════════════════════════════════════════════════
     Imaging comparison modal (full-screen overlay)
══════════════════════════════════════════════════════ --}}
@if($showComparisonModal && count($compareReportIds) === 2)
    <div class="fixed inset-0 z-50 flex flex-col"
         x-data
         x-on:keydown.escape.window="$wire.closeComparison()">
        <div class="absolute inset-0 bg-black/80" wire:click="closeComparison"></div>
        <div class="relative z-10 flex flex-col w-full h-full max-h-screen">
            <div class="flex items-center justify-between bg-gray-900 px-6 py-3 flex-shrink-0">
                <h2 class="text-sm font-semibold text-white">Imaging Comparison</h2>
                <button wire:click="closeComparison"
                        class="text-gray-400 hover:text-white transition text-sm font-medium px-3 py-1 rounded">
                    Close ✕
                </button>
            </div>
            <div class="flex flex-1 gap-1 bg-gray-800 overflow-hidden min-h-0">
                @foreach($compareReportIds as $rId)
                    @php $rpt = collect($imagingReports)->firstWhere('id', $rId); @endphp
                    <div class="flex-1 flex flex-col min-w-0">
                        <div class="bg-gray-700 px-3 py-1.5 text-xs text-gray-300 font-medium flex-shrink-0">
                            {{ strtoupper($rpt['modality'] ?? '—') }}
                            @if($rpt['body_part'] ?? null) — {{ $rpt['body_part'] }} @endif
                            · {{ $rpt['report_date'] ?? '' }}
                        </div>
                        <iframe src="{{ route('tenant.imaging.report.view', $rId) }}"
                                class="flex-1 w-full border-0 bg-white min-h-0"
                                title="Imaging report {{ $rId }}">
                        </iframe>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
