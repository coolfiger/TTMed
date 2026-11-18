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

        {{-- Queue panel --}}
        <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h2 class="font-semibold text-gray-700 dark:text-gray-300">Awaiting Doctor</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700 overflow-y-auto max-h-[calc(100vh-220px)]">
                @forelse($queue as $entry)
                    <button wire:click="selectPatient({{ $entry['id'] }})"
                            class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition
                                   {{ $activeEntryId === $entry['id'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-mono font-bold text-gray-500">#{{ $entry['queue_number'] }}</span>
                                @if($entry['is_priority'])
                                    <span class="ml-1 text-xs font-semibold text-red-600">PRIORITY</span>
                                @endif
                                <p class="text-sm font-medium text-gray-900 dark:text-white mt-0.5">{{ $entry['patient_name'] }}</p>
                            </div>
                        </div>
                    </button>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-gray-400">No patients waiting.</p>
                @endforelse
            </div>
        </div>

        {{-- Main panel --}}
        <div class="lg:col-span-2 space-y-4">

            @if(!$activeEntryId)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-6 py-16 text-center">
                    <p class="text-gray-400 dark:text-gray-500">Select a patient from the queue.</p>
                </div>
            @else
                {{-- Patient header --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Active Patient</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $activePatientName }}</p>
                        </div>
                        <div class="flex gap-3 items-center">
                            @if($noteIsFinalized)
                                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 font-semibold">
                                    Note Finalized
                                </span>
                                <button wire:click="completeEncounter"
                                        wire:confirm="Mark consultation complete and discharge patient?"
                                        class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    Complete Encounter
                                </button>
                            @else
                                <button wire:click="finalizeNote"
                                        wire:confirm="Finalize the consultation note? This cannot be undone."
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    Finalize Note
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                        @foreach([
                            'soap'      => 'SOAP Note',
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
                            </button>
                        @endforeach
                    </div>

                    <div class="p-6">

                        {{-- SOAP Note --}}
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

                        {{-- Vitals --}}
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

                        {{-- Diagnoses --}}
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

                                    {{-- ICD-10 search --}}
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
                                        <label class="block text-xs text-gray-500 mb-1">Diagnosis Text (overrides ICD description if entered)</label>
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

                        {{-- Labs --}}
                        @if($activeTab === 'labs')
                            @forelse($labOrders as $order)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $order['order_number'] }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            {{ ucfirst($order['status']) }}
                                        </span>
                                    </div>
                                    @foreach($order['items'] ?? [] as $item)
                                        <div class="ml-2 mb-2">
                                            <p class="text-xs font-semibold text-gray-500">{{ $item['test_name'] }}</p>
                                            @foreach($item['results'] ?? [] as $result)
                                                <div class="text-sm flex gap-4 mt-1">
                                                    <span class="font-semibold {{ $result['abnormal_flag'] ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                                        {{ $result['value'] }} {{ $result['unit'] }}
                                                        @if($result['abnormal_flag'])
                                                            <span class="text-xs">({{ $result['abnormal_flag'] }})</span>
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
                                <p class="text-sm text-gray-400 py-4 text-center">No lab orders for this encounter.</p>
                            @endforelse
                        @endif

                        {{-- Imaging --}}
                        @if($activeTab === 'imaging')
                            @forelse($imagingOrders as $order)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-3">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ $order['accession_number'] }} — {{ strtoupper($order['modality']) }}
                                        </span>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            {{ ucfirst($order['status']) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ $order['study_description'] }}</p>
                                    @foreach($order['reports'] ?? [] as $report)
                                        <div class="mt-2 pl-2 border-l-2 border-blue-300 dark:border-blue-700">
                                            @if($report['impression'])
                                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Impression</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-200">{{ $report['impression'] }}</p>
                                            @endif
                                            @if($report['findings'])
                                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-1">Findings</p>
                                                <p class="text-sm text-gray-800 dark:text-gray-200">{{ $report['findings'] }}</p>
                                            @endif
                                            @if($report['radiologist_name'])
                                                <p class="text-xs text-gray-400 mt-1">Radiologist: {{ $report['radiologist_name'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 py-4 text-center">No imaging orders for this encounter.</p>
                            @endforelse
                        @endif

                        {{-- Referrals --}}
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
</div>
