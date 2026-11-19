<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Doctor Consultation</h1>
        <button wire:click="loadQueue"
                class="text-sm text-blue-600 hover:underline dark:text-blue-400">
            Refresh Queue
        </button>
    </div>

    {{-- Alerts --}}
    @if($successMessage)
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300"
             x-data x-init="setTimeout(() => $el.remove(), 4000)">
            {{ $successMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ═══════════════════════════════════════════════════════════════════
             Queue panel
        ════════════════════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-semibold text-gray-700 dark:text-gray-300">Doctor Queue</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700 overflow-y-auto max-h-[calc(100vh-220px)]">
                @forelse($queue as $entry)
                    <div class="px-4 py-3 {{ $activeEntryId === $entry['id'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        <div class="flex items-start gap-2">
                            <button wire:click="selectPatient({{ $entry['id'] }})"
                                    class="flex-1 text-left min-w-0">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <span class="text-xs font-mono font-bold text-gray-500">#{{ $entry['queue_number'] }}</span>
                                    @if($entry['is_priority'])
                                        <span class="text-xs font-semibold text-red-600">PRIORITY</span>
                                    @endif
                                    @if($entry['status'] === 'with_doctor')
                                        <span class="text-xs px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 font-medium">In Consultation</span>
                                    @elseif($entry['status'] === 'awaiting_doctor')
                                        <span class="text-xs px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-medium">Waiting</span>
                                    @endif
                                </div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $entry['patient_name'] }}</p>
                            </button>
                            @if($entry['status'] === 'awaiting_doctor')
                                <button wire:click="callPatient({{ $entry['id'] }})"
                                        wire:confirm="Call this patient in?"
                                        title="Call patient in"
                                        class="flex-shrink-0 mt-0.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-green-600 hover:bg-green-700 text-white transition">
                                    Call In
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">No patients waiting.</p>
                @endforelse
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════
             Main panel
        ════════════════════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">

            @if(!$activeEntryId)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-6 py-16 text-center">
                    <p class="text-gray-400 dark:text-gray-500">Select a patient from the queue to begin.</p>
                </div>

            @else

                {{-- ── Patient header ── --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">
                                {{ $activeEntryStatus === 'with_doctor' ? 'In Consultation' : 'Awaiting Doctor' }}
                            </p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $activePatientName }}</p>
                        </div>

                        {{-- Allergy / Medication quick badges --}}
                        <div class="flex flex-wrap gap-2 items-start">
                            @if(count($allergies) > 0)
                                <div class="flex items-center gap-1 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 px-2 py-1 rounded-lg dark:bg-red-900/20 dark:text-red-400 dark:border-red-700"
                                     title="{{ collect($allergies)->pluck('allergen')->join(', ') }}">
                                    ⚠ {{ count($allergies) }} {{ Str::plural('Allergy', count($allergies)) }}
                                </div>
                            @endif
                            @if(count($medications) > 0)
                                <div class="flex items-center gap-1 text-xs font-semibold text-purple-700 bg-purple-50 border border-purple-200 px-2 py-1 rounded-lg dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-700">
                                    💊 {{ count($medications) }} Med{{ count($medications) !== 1 ? 's' : '' }}
                                </div>
                            @endif
                        </div>

                        <div class="flex gap-3 items-center flex-shrink-0">
                            @if($activeEntryStatus !== 'with_doctor')
                                <button wire:click="callPatient({{ $activeEntryId }})"
                                        wire:confirm="Call this patient in?"
                                        class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    Call In Patient
                                </button>
                            @else
                                @if($noteIsFinalized)
                                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 font-semibold">
                                        Note Finalized
                                    </span>
                                    <button wire:click="completeEncounter"
                                            wire:confirm="Mark consultation complete and discharge patient?"
                                            class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                        Complete &amp; Discharge
                                    </button>
                                @else
                                    <button wire:click="finalizeNote"
                                            wire:confirm="Finalize the consultation note? This cannot be undone."
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                        Finalize Note
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Tabs ── --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                        @foreach([
                            'soap'      => 'SOAP Note',
                            'history'   => 'History',
                            'vitals'    => 'Vitals',
                            'diagnoses' => 'Diagnoses',
                            'labs'      => 'Labs',
                            'imaging'   => 'Imaging',
                            'referrals' => 'Referrals',
                        ] as $tab => $label)
                            <button wire:click="$set('activeTab', '{{ $tab }}')"
                                    class="px-4 py-3 text-sm font-medium whitespace-nowrap transition
                                           {{ $activeTab === $tab
                                               ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                               : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                                {{ $label }}
                                @if($tab === 'imaging' && count($imagingReports) > 0)
                                    <span class="ml-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded-full">{{ count($imagingReports) }}</span>
                                @endif
                                @if($tab === 'labs' && count($labOrders) > 0)
                                    <span class="ml-1 text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 px-1.5 py-0.5 rounded-full">{{ count($labOrders) }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <div class="p-6">

                        {{-- ══ SOAP Note ══ --}}
                        @if($activeTab === 'soap')
                            @if($noteIsFinalized)
                                <div class="mb-4 rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-2 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                    This note has been finalized and is read-only.
                                </div>
                            @endif
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Subjective</label>
                                    <textarea wire:model="subjective" rows="3"
                                              @if($noteIsFinalized) disabled @endif
                                              placeholder="Chief complaint, history of present illness, review of systems..."
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:opacity-60"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Objective</label>
                                    <textarea wire:model="objective" rows="3"
                                              @if($noteIsFinalized) disabled @endif
                                              placeholder="Vital signs, physical examination findings..."
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:opacity-60"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Assessment</label>
                                    <textarea wire:model="assessment" rows="3"
                                              @if($noteIsFinalized) disabled @endif
                                              placeholder="Clinical assessment, differential diagnosis..."
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:opacity-60"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Plan</label>
                                    <textarea wire:model="plan" rows="3"
                                              @if($noteIsFinalized) disabled @endif
                                              placeholder="Treatment plan, prescriptions, follow-up..."
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:opacity-60"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Examination Findings</label>
                                    <textarea wire:model="examinationFindings" rows="2"
                                              @if($noteIsFinalized) disabled @endif
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:opacity-60"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Investigations Ordered</label>
                                    <textarea wire:model="investigationsOrdered" rows="2"
                                              @if($noteIsFinalized) disabled @endif
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:opacity-60"></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Follow-up Instructions</label>
                                        <textarea wire:model="followUpInstructions" rows="2"
                                                  @if($noteIsFinalized) disabled @endif
                                                  class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:opacity-60"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Follow-up in (days)</label>
                                        <input type="number" wire:model="followUpDays" min="1" max="365"
                                               @if($noteIsFinalized) disabled @endif
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:opacity-60" />
                                    </div>
                                </div>
                                @if(!$noteIsFinalized)
                                    <div class="flex justify-end">
                                        <button wire:click="saveNote"
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                            Save Note
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- ══ History ══ --}}
                        @if($activeTab === 'history')
                            <div class="space-y-5">

                                {{-- Allergies --}}
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-400 mb-2">Allergies</h3>
                                    @if(count($allergies) > 0)
                                        <div class="space-y-2">
                                            @foreach($allergies as $allergy)
                                                <div class="flex items-center gap-3 text-sm bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-lg px-3 py-2">
                                                    <span class="font-semibold text-red-800 dark:text-red-300">{{ $allergy['allergen'] }}</span>
                                                    @if($allergy['reaction'])
                                                        <span class="text-red-600 dark:text-red-400">→ {{ $allergy['reaction'] }}</span>
                                                    @endif
                                                    @if($allergy['severity'])
                                                        <span class="ml-auto text-xs px-1.5 py-0.5 rounded-full
                                                            {{ $allergy['severity'] === 'severe' ? 'bg-red-200 text-red-800' : ($allergy['severity'] === 'moderate' ? 'bg-orange-100 text-orange-700' : 'bg-yellow-100 text-yellow-700') }}">
                                                            {{ ucfirst($allergy['severity']) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-400 italic">No known drug allergies on record.</p>
                                    @endif
                                </div>

                                {{-- Current Medications --}}
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-purple-600 dark:text-purple-400 mb-2">Current Medications</h3>
                                    @if(count($medications) > 0)
                                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                                                        <th class="px-3 py-2 text-left">Medication</th>
                                                        <th class="px-3 py-2 text-left">Dosage</th>
                                                        <th class="px-3 py-2 text-left">Frequency</th>
                                                        <th class="px-3 py-2 text-left">Route</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                    @foreach($medications as $med)
                                                        <tr>
                                                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $med['medication_name'] }}</td>
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $med['dosage'] ?? '—' }}</td>
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $med['frequency'] ?? '—' }}</td>
                                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $med['route'] ?? '—' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-400 italic">No current medications on record.</p>
                                    @endif
                                </div>

                                {{-- Previous Visits --}}
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Previous Visits</h3>
                                    @forelse($patientHistory as $visit)
                                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-3">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $visit['encounter_date'] }}</span>
                                                @if($visit['chief_complaint'])
                                                    <span class="text-xs text-gray-500 italic">{{ $visit['chief_complaint'] }}</span>
                                                @endif
                                            </div>
                                            @if(count($visit['diagnoses']) > 0)
                                                <div class="flex flex-wrap gap-1 mb-2">
                                                    @foreach($visit['diagnoses'] as $dx)
                                                        <span class="text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-700 px-2 py-0.5 rounded-full">{{ $dx }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if($visit['assessment'])
                                                <div class="mb-1">
                                                    <span class="text-xs font-semibold text-gray-400 uppercase">Assessment: </span>
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($visit['assessment'], 300) }}</span>
                                                </div>
                                            @endif
                                            @if($visit['plan'])
                                                <div>
                                                    <span class="text-xs font-semibold text-gray-400 uppercase">Plan: </span>
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($visit['plan'], 300) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-400 italic">No previous visits on record.</p>
                                    @endforelse
                                </div>

                            </div>
                        @endif

                        {{-- ══ Vitals ══ --}}
                        @if($activeTab === 'vitals')
                            @forelse($vitals as $v)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 mb-3 text-sm">
                                    <p class="text-xs text-gray-400 mb-2">{{ \Carbon\Carbon::parse($v['recorded_at'])->format('d M Y H:i') }}</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        @if($v['systolic_bp'] && $v['diastolic_bp'])
                                            <div><span class="text-gray-500">BP:</span> <span class="font-semibold">{{ $v['systolic_bp'] }}/{{ $v['diastolic_bp'] }}</span> mmHg</div>
                                        @endif
                                        @if($v['pulse'])
                                            <div><span class="text-gray-500">Pulse:</span> <span class="font-semibold">{{ $v['pulse'] }}</span> bpm</div>
                                        @endif
                                        @if($v['temperature'])
                                            <div><span class="text-gray-500">Temp:</span> <span class="font-semibold">{{ $v['temperature'] }}°C</span></div>
                                        @endif
                                        @if($v['oxygen_saturation'])
                                            <div><span class="text-gray-500">SpO₂:</span> <span class="font-semibold">{{ $v['oxygen_saturation'] }}%</span></div>
                                        @endif
                                        @if($v['weight_kg'])
                                            <div><span class="text-gray-500">Weight:</span> <span class="font-semibold">{{ $v['weight_kg'] }} kg</span></div>
                                        @endif
                                        @if($v['bmi'])
                                            <div><span class="text-gray-500">BMI:</span> <span class="font-semibold">{{ $v['bmi'] }}</span></div>
                                        @endif
                                        @if($v['pain_score'] !== null)
                                            <div><span class="text-gray-500">Pain:</span> <span class="font-semibold">{{ $v['pain_score'] }}/10</span></div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 py-4 text-center">No vitals recorded.</p>
                            @endforelse
                        @endif

                        {{-- ══ Diagnoses ══ --}}
                        @if($activeTab === 'diagnoses')
                            <div class="mb-4 flex justify-end">
                                <button wire:click="$set('showDiagnosisForm', true)"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    + Add Diagnosis
                                </button>
                            </div>

                            @if($showDiagnosisForm)
                                <div class="border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4 bg-blue-50 dark:bg-blue-900/10 space-y-3">
                                    <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300">New Diagnosis</h3>

                                    <div x-data="{ open: false }" class="relative">
                                        <label class="block text-xs text-gray-500 mb-1">ICD-10 Search</label>
                                        <input type="text" wire:model.live="diagnosisSearch"
                                               wire:keyup.debounce.300ms="searchIcd"
                                               x-on:focus="open = true"
                                               x-on:click.outside="open = false"
                                               placeholder="Search by code or description..."
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                                        @if(count($icdResults) > 0)
                                            <div x-show="open"
                                                 class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
                                                @foreach($icdResults as $result)
                                                    <button wire:click="selectIcd({{ $result['id'] }})"
                                                            x-on:click="open = false"
                                                            class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                                        <span class="font-mono text-blue-600 dark:text-blue-400">{{ $result['code'] }}</span>
                                                        <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $result['description'] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    @if($selectedIcdId)
                                        <p class="text-xs text-green-700 dark:text-green-400">Selected: {{ $selectedIcdCode }} — {{ $selectedIcdText }}</p>
                                    @endif

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Type</label>
                                            <select wire:model="diagnosisType"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2">
                                                <option value="primary">Primary</option>
                                                <option value="secondary">Secondary</option>
                                                <option value="differential">Differential</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Certainty</label>
                                            <select wire:model="certainty"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2">
                                                <option value="confirmed">Confirmed</option>
                                                <option value="probable">Probable</option>
                                                <option value="suspected">Suspected</option>
                                                <option value="ruled_out">Ruled Out</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Diagnosis Text</label>
                                        <input type="text" wire:model="selectedIcdText"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Notes</label>
                                        <textarea wire:model="diagnosisNotes" rows="2"
                                                  class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                    </div>

                                    <div class="flex gap-2 justify-end">
                                        <button wire:click="$set('showDiagnosisForm', false)"
                                                class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5">Cancel</button>
                                        <button wire:click="saveDiagnosis"
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                                            Save Diagnosis
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @forelse($diagnoses as $d)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 mb-3 flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-semibold uppercase
                                                {{ $d['diagnosis_type'] === 'primary' ? 'text-blue-700 dark:text-blue-400' : 'text-gray-500' }}">
                                                {{ ucfirst($d['diagnosis_type']) }}
                                            </span>
                                            <span class="text-xs px-1.5 py-0.5 rounded
                                                {{ $d['certainty'] === 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                                    : ($d['certainty'] === 'ruled_out' ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                                    : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300') }}">
                                                {{ ucfirst(str_replace('_', ' ', $d['certainty'])) }}
                                            </span>
                                        </div>
                                        @if($d['icd10_code'])
                                            <span class="text-xs font-mono text-blue-600 dark:text-blue-400">{{ $d['icd10_code'] }}</span>
                                        @endif
                                        <p class="text-sm text-gray-900 dark:text-white">{{ $d['diagnosis_text'] }}</p>
                                        @if($d['notes'])
                                            <p class="text-xs text-gray-500 mt-1">{{ $d['notes'] }}</p>
                                        @endif
                                    </div>
                                    <button wire:click="removeDiagnosis({{ $d['id'] }})"
                                            wire:confirm="Remove this diagnosis?"
                                            class="text-red-400 hover:text-red-600 text-xs ml-4">Remove</button>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 py-4 text-center">No diagnoses recorded.</p>
                            @endforelse
                        @endif

                        {{-- ══ Labs ══ --}}
                        @if($activeTab === 'labs')
                            @forelse($labOrders as $order)
                                <div class="border {{ $order['is_current'] ? 'border-blue-300 dark:border-blue-700' : 'border-gray-200 dark:border-gray-700' }} rounded-lg p-4 mb-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $order['order_number'] }}</span>
                                            @if($order['is_current'])
                                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">This Visit</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                {{ ucfirst(str_replace('_', ' ', $order['status'])) }}
                                            </span>
                                            @if(count($order['attachments'] ?? []) > 0)
                                                @foreach($order['attachments'] as $att)
                                                    <a href="{{ route('tenant.lab.attachment.view', $att['id']) }}"
                                                       target="_blank"
                                                       class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 border border-blue-200 dark:border-blue-700 px-2 py-0.5 rounded transition">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                        PDF Report
                                                    </a>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                    @foreach($order['items'] ?? [] as $item)
                                        <div class="ml-2 mb-2">
                                            <p class="text-xs font-semibold text-gray-500">{{ $item['test_name'] }}</p>
                                            @foreach($item['results'] ?? [] as $result)
                                                <div class="text-sm flex gap-4 mt-1">
                                                    <span class="font-semibold {{ ($result['flag'] ?? '') && $result['flag'] !== 'normal' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                                        {{ $result['value'] }} {{ $result['value_unit'] }}
                                                        @if(($result['flag'] ?? '') && $result['flag'] !== 'normal')
                                                            <span class="text-xs">({{ strtoupper($result['flag']) }})</span>
                                                        @endif
                                                    </span>
                                                    @if($result['reference_range'])
                                                        <span class="text-gray-400 text-xs">Ref: {{ $result['reference_range'] }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 py-4 text-center">No lab orders on record for this patient.</p>
                            @endforelse
                        @endif

                        {{-- ══ Imaging ══ --}}
                        @if($activeTab === 'imaging')

                            {{-- Compare toolbar --}}
                            @if(count($compareReportIds) > 0)
                                <div class="mb-4 flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                                    <span class="text-sm text-blue-700 dark:text-blue-300">
                                        {{ count($compareReportIds) }} of 2 selected for comparison
                                    </span>
                                    @if(count($compareReportIds) === 2)
                                        <button wire:click="openComparison"
                                                class="ml-auto text-sm font-semibold px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                            Open Side-by-Side
                                        </button>
                                    @else
                                        <span class="ml-auto text-xs text-blue-500">Select one more to compare</span>
                                    @endif
                                    <button wire:click="$set('compareReportIds', [])"
                                            class="text-xs text-gray-500 hover:text-gray-700">Clear</button>
                                </div>
                            @endif

                            @forelse($imagingReports as $report)
                                <div class="border {{ $report['is_current'] ? 'border-blue-300 dark:border-blue-700' : 'border-gray-200 dark:border-gray-700' }} rounded-lg p-4 mb-3">
                                    <div class="flex items-start gap-3">

                                        {{-- Compare checkbox --}}
                                        @if($report['has_pdf'])
                                            <div class="flex-shrink-0 mt-0.5">
                                                <input type="checkbox"
                                                       wire:click="toggleCompare({{ $report['id'] }})"
                                                       @checked(in_array($report['id'], $compareReportIds))
                                                       @disabled(!in_array($report['id'], $compareReportIds) && count($compareReportIds) >= 2)
                                                       class="w-4 h-4 rounded text-blue-600 cursor-pointer"
                                                       title="Select for comparison" />
                                            </div>
                                        @endif

                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ strtoupper($report['modality'] ?? '—') }}
                                                    @if($report['body_part']) — {{ $report['body_part'] }} @endif
                                                </span>
                                                @if($report['is_current'])
                                                    <span class="text-xs px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">This Visit</span>
                                                @endif
                                                <span class="text-xs text-gray-400">{{ $report['report_date'] }}</span>
                                                @if($report['order_number'])
                                                    <span class="text-xs font-mono text-gray-400">{{ $report['order_number'] }}</span>
                                                @endif
                                                @if($report['has_pdf'])
                                                    <a href="{{ route('tenant.imaging.report.view', $report['id']) }}"
                                                       target="_blank"
                                                       class="ml-auto inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 px-2 py-0.5 rounded transition">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                        View Report
                                                    </a>
                                                @endif
                                            </div>

                                            @if($report['impression'])
                                                <div class="mt-2 pl-3 border-l-2 border-blue-400 dark:border-blue-600">
                                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Impression</p>
                                                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ Str::limit($report['impression'], 400) }}</p>
                                                </div>
                                            @endif
                                            @if($report['findings'])
                                                <div class="mt-2 pl-3 border-l-2 border-gray-300 dark:border-gray-600">
                                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Findings</p>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($report['findings'], 400) }}</p>
                                                </div>
                                            @endif
                                            @if($report['radiologist_name'])
                                                <p class="text-xs text-gray-400 mt-1">Radiologist: {{ $report['radiologist_name'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 py-4 text-center">No imaging reports on record for this patient.</p>
                            @endforelse
                        @endif

                        {{-- ══ Referrals ══ --}}
                        @if($activeTab === 'referrals')
                            <div class="mb-4 flex justify-end">
                                <button wire:click="$set('showReferralForm', true)"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    + Add Referral
                                </button>
                            </div>

                            @if($showReferralForm)
                                <div class="border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4 bg-blue-50 dark:bg-blue-900/10 space-y-3">
                                    <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300">New Referral</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="col-span-2">
                                            <label class="block text-xs text-gray-500 mb-1">Referred To *</label>
                                            <input type="text" wire:model="referredToName" placeholder="Specialist or facility name"
                                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Specialty</label>
                                            <input type="text" wire:model="referredToSpecialty"
                                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Facility</label>
                                            <input type="text" wire:model="referredToFacility"
                                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-xs text-gray-500 mb-1">Urgency</label>
                                            <select wire:model="referralUrgency"
                                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2">
                                                <option value="routine">Routine</option>
                                                <option value="urgent">Urgent</option>
                                                <option value="emergency">Emergency</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-xs text-gray-500 mb-1">Reason *</label>
                                            <textarea wire:model="referralReason" rows="2"
                                                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-xs text-gray-500 mb-1">Clinical Summary</label>
                                            <textarea wire:model="clinicalSummary" rows="2"
                                                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-xs text-gray-500 mb-1">Instructions for Patient</label>
                                            <textarea wire:model="patientInstructions" rows="2"
                                                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 justify-end">
                                        <button wire:click="$set('showReferralForm', false)"
                                                class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5">Cancel</button>
                                        <button wire:click="saveReferral"
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                                            Save Referral
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @forelse($referrals as $r)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 mb-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $r['referred_to_name'] }}</p>
                                            @if($r['referred_to_specialty'])
                                                <p class="text-xs text-gray-500">{{ $r['referred_to_specialty'] }}</p>
                                            @endif
                                        </div>
                                        <div class="flex gap-2 items-center">
                                            <span class="text-xs px-2 py-0.5 rounded-full
                                                {{ $r['urgency'] === 'emergency' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                                    : ($r['urgency'] === 'urgent' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">
                                                {{ ucfirst($r['urgency']) }}
                                            </span>
                                            <span class="text-xs text-gray-400">{{ ucfirst($r['status']) }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $r['reason'] }}</p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 py-4 text-center">No referrals for this encounter.</p>
                            @endforelse
                        @endif

                    </div>
                </div>

            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         Side-by-side imaging comparison modal
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($showComparisonModal && count($compareReportIds) === 2)
        <div class="fixed inset-0 z-50 flex flex-col"
             x-data
             x-on:keydown.escape.window="$wire.closeComparison()">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/80" wire:click="closeComparison"></div>

            {{-- Modal --}}
            <div class="relative z-10 flex flex-col w-full h-full max-h-screen">

                {{-- Header bar --}}
                <div class="flex items-center justify-between bg-gray-900 px-6 py-3 flex-shrink-0">
                    <h2 class="text-sm font-semibold text-white">Imaging Comparison</h2>
                    <button wire:click="closeComparison"
                            class="text-gray-400 hover:text-white transition text-sm font-medium px-3 py-1 rounded">
                        Close ✕
                    </button>
                </div>

                {{-- Two PDF iframes --}}
                <div class="flex flex-1 gap-1 bg-gray-800 overflow-hidden min-h-0">
                    @foreach($compareReportIds as $rId)
                        @php
                            $rpt = collect($imagingReports)->firstWhere('id', $rId);
                        @endphp
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

</div>
