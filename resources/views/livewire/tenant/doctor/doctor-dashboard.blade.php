<div class="flex h-full min-h-0 overflow-hidden bg-slate-100"
     x-data="panelLayout()"
     x-init="init()">

    {{-- ══════════════════════════════════════════════════════
         LEFT: Queue panel
    ══════════════════════════════════════════════════════ --}}
    <div :style="queueStyle()"
         class="flex-shrink-0 flex flex-col bg-white border-r border-slate-200 transition-[width] duration-150 overflow-hidden"
         id="panel-queue">

        <div class="px-3 py-2.5 border-b border-slate-200 flex items-center justify-between flex-shrink-0 gap-2">
            <div x-show="!queueCollapsed" class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-800">Doctor Queue</p>
                <p class="text-xs text-slate-400">{{ today()->format('d M Y') }}</p>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
                <button wire:click="loadQueue" x-show="!queueCollapsed"
                        class="text-slate-400 hover:text-blue-600 transition p-1 rounded"
                        title="Refresh queue">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
                <button @click="toggleQueue()"
                        class="text-slate-400 hover:text-slate-700 transition p-1 rounded hover:bg-slate-100"
                        :title="queueCollapsed ? 'Expand queue' : 'Collapse queue'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!queueCollapsed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7"/>
                        <path x-show="queueCollapsed"  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
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

    {{-- Drag handle: queue ↔ center --}}
    <div class="w-1 flex-shrink-0 bg-slate-200 hover:bg-blue-400 cursor-col-resize transition-colors active:bg-blue-500 relative group"
         x-show="!queueCollapsed"
         @mousedown.prevent="startDrag($event, 'queue')">
        <div class="absolute inset-y-0 -left-1 -right-1"></div>
    </div>

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
                @if($activeEntryStatus === 'with_doctor')
                    @if($wizardStep)
                        {{-- Wizard is active — show step breadcrumb, no edit buttons --}}
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold
                                {{ $wizardStep === 'prescription' ? 'bg-blue-600 text-white' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $wizardStep === 'prescription' ? '1' : '✓' }}
                            </span>
                            <span class="text-sm {{ $wizardStep === 'prescription' ? 'font-semibold text-blue-700' : 'text-slate-400 line-through' }}">Prescription</span>
                            <span class="text-slate-300">›</span>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold
                                {{ $wizardStep === 'instructions' ? 'bg-teal-600 text-white' : ($wizardStep === 'billing' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500') }}">
                                {{ $wizardStep === 'billing' ? '✓' : '2' }}
                            </span>
                            <span class="text-sm {{ $wizardStep === 'instructions' ? 'font-semibold text-teal-700' : ($wizardStep === 'billing' ? 'text-slate-400 line-through' : 'text-slate-400') }}">Instructions</span>
                            <span class="text-slate-300">›</span>
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold
                                {{ $wizardStep === 'billing' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }}">3</span>
                            <span class="text-sm {{ $wizardStep === 'billing' ? 'font-semibold text-emerald-700' : 'text-slate-400' }}">Billing</span>
                        </div>
                    @elseif($noteIsFinalized)
                        <span class="text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 font-semibold">Note Finalized</span>
                        <button wire:click="openBillingSelector"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Complete &amp; Send to Reception
                        </button>
                    @else
                        <button wire:click="saveNote"
                                class="text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-500 text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Save Note
                        </button>
                        <span x-data="{ confirming: false }">
                            <button x-show="!confirming"
                                    @click="confirming = true"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                Finalize Note
                            </button>
                            <span x-show="confirming" class="flex items-center gap-2">
                                <span class="text-xs text-slate-500">Make read-only?</span>
                                <button @click="confirming = false"
                                        class="text-sm font-medium text-slate-600 border border-slate-300 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition">
                                    Cancel
                                </button>
                                <button @click="$wire.finalizeNote(); confirming = false"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition">
                                    Yes, Finalize
                                </button>
                            </span>
                        </span>
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

        {{-- ── Call-in gate ── --}}
        @if($activeEntryStatus !== 'with_doctor')
        <div class="flex-1 flex flex-col items-center justify-center px-8 text-center">
            <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <p class="text-base font-semibold text-slate-800 mb-1">{{ $activePatientName }} is waiting</p>
            <p class="text-sm text-slate-500 mb-6">Call the patient in before recording any consultation notes.</p>
            <button wire:click="callPatient({{ $activeEntryId }})"
                    class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition shadow-sm">
                Call In {{ $activePatientName }}
            </button>
        </div>
        @elseif($wizardStep)

        {{-- ══════════════════════════════════════════════════════
             Inline post-consultation wizard (replaces consultation form)
        ══════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col min-h-0 overflow-hidden">

            @if($wizardStep === 'prescription')
            {{-- ── Step 1: Prescription ── --}}
            <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
                @if($errorMessage)
                <div class="mx-5 mt-3 flex-shrink-0 rounded-lg bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-700 flex items-center justify-between">
                    <span>{{ $errorMessage }}</span>
                    <button wire:click="$set('errorMessage', '')" class="ml-2 text-red-400 hover:text-red-600 text-xs">✕</button>
                </div>
                @endif
                <div class="px-5 pt-4 pb-2 flex-shrink-0">
                    <p class="text-xs text-slate-500">Add medications to this prescription. Skip if none needed.</p>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto px-5 pb-4 space-y-4">
                    @foreach($rxItems as $i => $item)
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Medication {{ $i + 1 }}</p>
                            @if(count($rxItems) > 1)
                                <button wire:click="removeRxItem({{ $i }})" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs text-slate-500 mb-1">Medication Name <span class="text-red-400">*</span></label>
                                <input type="text" wire:model="rxItems.{{ $i }}.name" placeholder="e.g. Amoxicillin"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Strength</label>
                                <input type="text" wire:model="rxItems.{{ $i }}.strength" placeholder="e.g. 500mg"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Form</label>
                                <select wire:model="rxItems.{{ $i }}.form"
                                        class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="">— Select —</option>
                                    <option value="tablet">Tablet</option>
                                    <option value="capsule">Capsule</option>
                                    <option value="liquid">Liquid</option>
                                    <option value="cream">Cream / Ointment</option>
                                    <option value="injection">Injection</option>
                                    <option value="inhaler">Inhaler</option>
                                    <option value="drops">Drops</option>
                                    <option value="patch">Patch</option>
                                    <option value="suppository">Suppository</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Dose</label>
                                <input type="text" wire:model="rxItems.{{ $i }}.dose" placeholder="e.g. 1 tablet"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Frequency</label>
                                <input type="text" wire:model="rxItems.{{ $i }}.frequency" placeholder="e.g. Twice daily"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Duration</label>
                                <div class="flex gap-1.5">
                                    <input type="number" wire:model="rxItems.{{ $i }}.duration" min="1" placeholder="7"
                                           class="w-20 rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                                    <select wire:model="rxItems.{{ $i }}.duration_unit"
                                            class="flex-1 rounded-lg border border-slate-300 bg-white text-sm px-2 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                        <option value="days">Days</option>
                                        <option value="weeks">Weeks</option>
                                        <option value="months">Months</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-span-2 md:col-span-3">
                                <label class="block text-xs text-slate-500 mb-1">Special Instructions</label>
                                <input type="text" wire:model="rxItems.{{ $i }}.instructions" placeholder="e.g. Take with food, avoid alcohol"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                        </div>
                    </div>
                    @endforeach
                    <button wire:click="addRxItem"
                            class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 font-medium transition">
                        <span class="text-lg leading-none">+</span> Add Another Medication
                    </button>
                </div>
                <div class="px-5 py-4 border-t-2 border-blue-100 flex items-center justify-between flex-shrink-0 bg-white">
                    <button wire:click="skipPrescription"
                            class="text-sm font-semibold text-slate-600 hover:text-slate-800 border border-slate-300 hover:border-slate-400 px-5 py-2.5 rounded-lg transition">
                        Skip — No Prescription
                    </button>
                    <button wire:click="issuePrescription"
                            wire:loading.attr="disabled" wire:target="issuePrescription"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-8 py-2.5 rounded-lg transition shadow-sm disabled:opacity-50">
                        <span wire:loading.remove wire:target="issuePrescription">Save Prescription &amp; Continue →</span>
                        <span wire:loading wire:target="issuePrescription">Saving…</span>
                    </button>
                </div>
            </div>

            @elseif($wizardStep === 'instructions')
            {{-- ── Step 2: Instructions ── --}}
            <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
                <div class="px-5 pt-4 pb-2 flex-shrink-0">
                    <p class="text-xs text-slate-500">Give the patient written guidance to take home. Reception can print this at checkout.</p>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto px-5 pb-4 space-y-5">
                    @php
                        $tmplList = [
                            'Drink at least 8 glasses of water daily',
                            'Rest and avoid strenuous physical activity',
                            'Elevate the affected limb above heart level',
                            'Apply ice pack for 15-20 minutes every 2 hours',
                            'Take all medication as prescribed, even if feeling better',
                            'Avoid alcohol and smoking during treatment',
                            'Eat light, easily digestible meals',
                            'Monitor temperature and return if fever exceeds 38.5 degrees C',
                            'Keep wound site clean and dry',
                            'Return to clinic immediately if symptoms worsen',
                            'Avoid driving while on medication',
                            'Follow up with physiotherapy as arranged',
                        ];
                    @endphp
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Common Instructions &mdash; click to toggle</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tmplList as $tmpl)
                                @php $active = in_array($tmpl, $instructionLines); @endphp
                                <button wire:click="toggleInstructionTemplate('{{ e($tmpl) }}')"
                                        class="text-xs px-3 py-1.5 rounded-full border transition
                                            {{ $active ? 'bg-teal-600 border-teal-600 text-white' : 'bg-white border-slate-300 text-slate-700 hover:border-teal-400 hover:text-teal-700' }}">
                                    {{ $tmpl }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Custom Instructions</p>
                        <div class="space-y-2">
                            @foreach($instructionLines as $j => $line)
                                @if(!in_array($line, $tmplList) || trim($line) === '')
                                <div class="flex gap-2 items-center">
                                    <input type="text" wire:model="instructionLines.{{ $j }}"
                                           placeholder="Add a custom instruction&hellip;"
                                           class="flex-1 rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none" />
                                    <button wire:click="removeInstructionLine({{ $j }})"
                                            class="text-red-400 hover:text-red-600 text-xs flex-shrink-0">Remove</button>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        <button wire:click="addInstructionLine"
                                class="mt-2 flex items-center gap-1.5 text-sm text-teal-600 hover:text-teal-800 font-medium">
                            <span class="text-lg leading-none">+</span> Add Custom Line
                        </button>
                    </div>
                    @if(count(array_filter($instructionLines)) > 0)
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Preview</p>
                        <ul class="space-y-1.5">
                            @foreach($instructionLines as $ln)
                                @if(trim($ln))
                                <li class="text-sm text-slate-700 flex gap-2">
                                    <span class="text-teal-500 flex-shrink-0 mt-0.5">&#8226;</span>
                                    <span>{{ $ln }}</span>
                                </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    {{-- Follow-up scheduling --}}
                    <div class="border border-amber-200 rounded-xl bg-amber-50 p-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model.live="scheduleFollowUp"
                                   class="w-4 h-4 rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                            <span class="text-sm font-semibold text-amber-800">Schedule a follow-up appointment</span>
                        </label>
                        @if($scheduleFollowUp)
                        <div class="mt-4 space-y-3 pt-3 border-t border-amber-200">
                            <div class="flex items-center gap-3">
                                <label class="text-xs font-semibold text-amber-700 w-28 flex-shrink-0">Return in</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" wire:model="followUpScheduleDays" min="1" max="365" placeholder="7"
                                           class="w-20 rounded-lg border border-amber-300 bg-white text-sm px-3 py-1.5 focus:ring-2 focus:ring-amber-400 focus:outline-none">
                                    <span class="text-sm text-amber-700">days</span>
                                    @if($followUpScheduleDays && is_numeric($followUpScheduleDays))
                                        <span class="text-xs text-amber-600 ml-1">({{ now()->addDays((int)$followUpScheduleDays)->format('d M Y') }})</span>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-amber-700 block mb-1">Follow-up instructions / reason</label>
                                <textarea wire:model="followUpScheduleReason" rows="2"
                                          placeholder="e.g. Review blood pressure results, wound check&hellip;"
                                          class="w-full rounded-lg border border-amber-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-amber-400 focus:outline-none resize-none"></textarea>
                            </div>
                            <p class="text-xs text-amber-600">Reception will see this in the follow-up queue to call and confirm a time.</p>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="px-5 py-4 border-t-2 border-teal-100 flex items-center justify-between flex-shrink-0 bg-white">
                    <div class="flex items-center gap-2">
                        <button wire:click="backToPrescription"
                                class="text-sm font-medium text-slate-500 hover:text-slate-700 border border-slate-200 hover:border-slate-300 px-3 py-2.5 rounded-lg transition flex items-center gap-1.5">
                            ← Back
                        </button>
                        <button wire:click="skipInstructions"
                                class="text-sm font-semibold text-slate-600 hover:text-slate-800 border border-slate-300 hover:border-slate-400 px-5 py-2.5 rounded-lg transition">
                            Skip — No Instructions
                        </button>
                    </div>
                    <button wire:click="saveInstructions"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold px-8 py-2.5 rounded-lg transition shadow-sm">
                        Save Instructions &amp; Continue →
                    </button>
                </div>
            </div>

            @elseif($wizardStep === 'billing')
            {{-- ── Step 3: Billing ── --}}
            <div class="flex-1 flex flex-col min-h-0 overflow-hidden" x-data="{ search: '' }">
                <div class="px-5 pt-4 pb-2 flex-shrink-0">
                    <p class="text-xs text-slate-500 mb-3">Select everything done during this visit. The receptionist will collect payment.</p>
                    <input type="text" x-model="search" placeholder="Search services…"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto px-5 pb-2 space-y-1">
                    @php $grouped = collect($billingCatalog)->groupBy('category'); @endphp
                    @foreach($grouped as $category => $services)
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide pt-2 pb-1"
                           x-show="{{ collect($services)->pluck('name')->map(fn($n) => "search===''||'".strtolower($n)."'.includes(search.toLowerCase())")->join('||') }}">
                           {{ $category }}
                        </p>
                        @foreach($services as $svc)
                        <label class="flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-slate-50 transition"
                               x-show="search === '' || '{{ strtolower($svc['name']) }}'.includes(search.toLowerCase())">
                            <input type="checkbox"
                                   wire:click="toggleBillingService({{ $svc['id'] }})"
                                   @checked(in_array($svc['id'], $selectedServiceIds))
                                   class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800">{{ $svc['name'] }}</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-600 flex-shrink-0">
                                ${{ number_format($svc['price'] / 100, 2) }}
                            </span>
                        </label>
                        @endforeach
                    @endforeach
                    @if(count($billingCatalog) === 0)
                        <p class="text-sm text-slate-400 italic text-center py-6">No active services found. Add services under Settings → Services.</p>
                    @endif
                </div>
                <div class="px-5 py-4 border-t border-slate-200 flex-shrink-0 bg-white">
                    @php
                        $selectedTotal = collect($billingCatalog)->whereIn('id', $selectedServiceIds)->sum('price');
                    @endphp
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-slate-500">{{ count($selectedServiceIds) }} service(s) selected</span>
                        <span class="text-base font-bold text-slate-800">${{ number_format($selectedTotal / 100, 2) }}</span>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="backToInstructions"
                                class="px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                            ← Back
                        </button>
                        <button wire:click="completeEncounterWithBilling"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="flex-1 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition">
                            Send to Reception
                        </button>
                    </div>
                </div>
            </div>
            @endif

        </div>

        @else

        {{-- Scrollable consultation form --}}
        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

            {{-- Finalized banner --}}
            @if($noteIsFinalized)
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-2 text-sm text-amber-800">
                    This consultation note has been finalized and is read-only.
                </div>
            @endif

            {{-- ── Chief Complaint ── --}}
            @if($chiefComplaint)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 flex items-start gap-2.5">
                <svg class="w-3.5 h-3.5 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <div class="min-w-0">
                    <span class="text-xs font-semibold uppercase tracking-wide text-amber-600">Chief Complaint</span>
                    <p class="text-sm text-amber-900 mt-0.5">{{ $chiefComplaint }}</p>
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

            {{-- ── Doctor Procedures ── --}}
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Procedures Performed</p>
                    @if(!$noteIsFinalized)
                        <button wire:click="$set('showDoctorProcedureForm', true)"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                            + Add Procedure
                        </button>
                    @endif
                </div>

                @if($showDoctorProcedureForm)
                    <div class="p-4 border-b border-slate-100 bg-blue-50 space-y-3">
                        <h3 class="text-sm font-semibold text-slate-700">Record Procedure</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Type</label>
                                <select wire:model="doctorProcedureType"
                                        class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="">Select type…</option>
                                    @foreach(\App\Models\Tenant\NursingProcedure::TYPES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('doctorProcedureType') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Name / Description</label>
                                <input type="text" wire:model="doctorProcedureName" maxlength="200"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                                @error('doctorProcedureName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Site</label>
                                <input type="text" wire:model="doctorProcedureSite"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Outcome</label>
                                <input type="text" wire:model="doctorProcedureOutcome"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Details</label>
                            <textarea wire:model="doctorProcedureDetails" rows="2"
                                      class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button wire:click="$set('showDoctorProcedureForm', false)"
                                    class="text-sm text-slate-500 hover:text-slate-700 px-3 py-1.5">Cancel</button>
                            <button wire:click="saveDoctorProcedure"
                                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                                Save Procedure
                            </button>
                        </div>
                    </div>
                @endif

                <div class="p-4">
                    @if(count($doctorProcedures) > 0)
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="text-left pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Procedure</th>
                                    <th class="text-left pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Site</th>
                                    <th class="text-left pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Outcome</th>
                                    <th class="text-left pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wide">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($doctorProcedures as $proc)
                                    <tr>
                                        <td class="py-2 pr-4">
                                            <p class="font-medium text-slate-800">{{ $proc['type_label'] }}</p>
                                            @if($proc['name'] !== $proc['type_label'])
                                                <p class="text-xs text-slate-500">{{ $proc['name'] }}</p>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4 text-slate-600 text-sm">{{ $proc['site'] ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-slate-600 text-sm">{{ $proc['outcome'] ?? '—' }}</td>
                                        <td class="py-2 text-xs text-slate-400">{{ $proc['performed_at'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-slate-400 text-center py-4">No procedures recorded for this visit.</p>
                    @endif
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
                        <div x-data="{
                            query: '',
                            results: [],
                            loading: false,
                            open: false,
                            async search() {
                                if (this.query.length < 2) { this.results = []; this.open = false; return; }
                                this.loading = true;
                                try {
                                    const r = await fetch('https://clinicaltables.nlm.nih.gov/api/icd10cm/v3/search?sf=code,name&terms=' + encodeURIComponent(this.query) + '&maxList=12');
                                    const d = await r.json();
                                    this.results = (d[3] || []).map(([code, name]) => ({ code, name }));
                                    this.open = this.results.length > 0;
                                } catch(e) { this.results = []; }
                                this.loading = false;
                            },
                            select(code, name) {
                                this.query = code + ' — ' + name;
                                this.results = [];
                                this.open = false;
                                $wire.selectIcdExternal(code, name);
                            }
                        }" class="relative" x-on:click.outside="open = false">
                            <label class="block text-xs text-slate-500 mb-1">ICD-10 Search</label>
                            <div class="relative">
                                <input type="text"
                                       x-model="query"
                                       @input.debounce.300ms="search()"
                                       @focus="if(results.length) open = true"
                                       placeholder="Type a diagnosis or ICD-10 code…"
                                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 pr-8 focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                                <span x-show="loading" class="absolute right-2 top-2.5 text-slate-400 text-xs">…</span>
                            </div>
                            <div x-show="open"
                                 class="absolute z-20 w-full bg-white border border-slate-200 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto">
                                <template x-for="r in results" :key="r.code">
                                    <button @click="select(r.code, r.name)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 transition border-b border-slate-50 last:border-0">
                                        <span class="font-mono text-blue-600 text-xs" x-text="r.code"></span>
                                        <span class="ml-2 text-slate-700" x-text="r.name"></span>
                                    </button>
                                </template>
                            </div>
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
                @if(!empty($savedFollowUp))
                <div class="px-4 pb-3">
                    <div class="flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>
                            Follow-up queued for reception
                            @if($savedFollowUp->target_date)
                                — target {{ $savedFollowUp->target_date->format('d M Y') }}
                            @elseif($savedFollowUp->follow_up_days)
                                — in {{ $savedFollowUp->follow_up_days }} days
                            @endif
                            @if($savedFollowUp->isBooked())
                                <strong class="text-blue-700 ml-1">(booked)</strong>
                            @endif
                        </span>
                    </div>
                </div>
                @endif
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

        @endif {{-- end call-in gate --}}

    </div>

    {{-- Drag handle: center ↔ context --}}
    <div class="w-1 flex-shrink-0 bg-slate-200 hover:bg-blue-400 cursor-col-resize transition-colors active:bg-blue-500 relative"
         @mousedown.prevent="startDrag($event, 'context')">
        <div class="absolute inset-y-0 -left-1 -right-1"></div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         RIGHT: Patient context panel
    ══════════════════════════════════════════════════════ --}}
    <div :style="contextStyle()"
         class="flex-shrink-0 flex flex-col bg-white border-l border-slate-200 overflow-y-auto"
         id="panel-context"
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
            <div x-show="open.allergies" class="px-4 pb-3">
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
            <div x-show="open.meds" class="px-4 pb-3">
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

        {{-- Latest Vitals (current encounter) --}}
        @if(count($vitals) > 0)
        @php $v = $vitals[0]; @endphp
        <div class="border-b border-slate-100 px-4 py-3">
            <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Latest Vitals</p>
            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
                @if(!empty($v['systolic_bp']) && !empty($v['diastolic_bp']))
                @php $hypertensive = $v['systolic_bp'] >= 140 || $v['diastolic_bp'] >= 90; @endphp
                <div class="flex flex-col">
                    <span class="text-xs text-slate-400">BP</span>
                    <span class="text-sm font-semibold {{ $hypertensive ? 'text-red-600' : 'text-slate-800' }}">
                        {{ $v['systolic_bp'] }}/{{ $v['diastolic_bp'] }}
                        @if($hypertensive)<span class="text-xs font-normal ml-0.5">↑</span>@endif
                    </span>
                </div>
                @endif
                @if(!empty($v['pulse_rate']))
                <div class="flex flex-col">
                    <span class="text-xs text-slate-400">HR</span>
                    <span class="text-sm font-semibold text-slate-800">{{ $v['pulse_rate'] }} <span class="text-xs font-normal text-slate-500">bpm</span></span>
                </div>
                @endif
                @if(!empty($v['temperature']))
                <div class="flex flex-col">
                    <span class="text-xs text-slate-400">Temp</span>
                    <span class="text-sm font-semibold {{ $v['temperature'] >= 38 ? 'text-orange-600' : 'text-slate-800' }}">{{ $v['temperature'] }}°C</span>
                </div>
                @endif
                @if(!empty($v['oxygen_saturation']))
                <div class="flex flex-col">
                    <span class="text-xs text-slate-400">SpO₂</span>
                    <span class="text-sm font-semibold {{ $v['oxygen_saturation'] < 95 ? 'text-red-600' : 'text-slate-800' }}">{{ $v['oxygen_saturation'] }}%</span>
                </div>
                @endif
                @if(!empty($v['weight_kg']))
                <div class="flex flex-col">
                    <span class="text-xs text-slate-400">Weight</span>
                    <span class="text-sm font-semibold text-slate-800">{{ $v['weight_kg'] }} <span class="text-xs font-normal text-slate-500">kg</span></span>
                </div>
                @endif
                @if(!empty($v['blood_glucose']))
                <div class="flex flex-col">
                    <span class="text-xs text-slate-400">Glucose{{ !empty($v['blood_glucose_timing']) ? ' ('.$v['blood_glucose_timing'].')' : '' }}</span>
                    @php
                        $glucoseHigh  = $bloodGlucoseUnit === 'mg_dl' ? 200 : 11.1;
                        $glucoseMild  = $bloodGlucoseUnit === 'mg_dl' ? 140 : 7.8;
                        $glucoseLabel = $bloodGlucoseUnit === 'mg_dl' ? 'mg/dL' : 'mmol/L';
                    @endphp
                    <span class="text-sm font-semibold {{ $v['blood_glucose'] > $glucoseHigh ? 'text-red-600' : ($v['blood_glucose'] > $glucoseMild ? 'text-orange-500' : 'text-slate-800') }}">{{ $v['blood_glucose'] }} <span class="text-xs font-normal text-slate-500">{{ $glucoseLabel }}</span></span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Nursing Assessment & Procedures (current encounter) --}}
        @if($nursingAssessment || count($nursingProcedures) > 0)
        <div class="border-b border-slate-100" x-data="{ openNursing: true }">
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="openNursing = !openNursing">
                <span class="text-xs font-semibold text-teal-700 uppercase tracking-wide">
                    Nursing — This Visit
                    @if(count($nursingProcedures) > 0)
                        <span class="normal-case font-normal text-slate-400">({{ count($nursingProcedures) }} procedure{{ count($nursingProcedures) > 1 ? 's' : '' }})</span>
                    @endif
                </span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="openNursing ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="openNursing" class="px-4 pb-3 space-y-2">
                @if($nursingAssessment)
                    @if($nursingAssessment['chief_complaint'])
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Chief Complaint</p>
                        <p class="text-sm text-slate-700">{{ $nursingAssessment['chief_complaint'] }}</p>
                    </div>
                    @endif
                    @if($nursingAssessment['nursing_notes'])
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide">Nursing Notes</p>
                        <p class="text-sm text-slate-700 whitespace-pre-line">{{ $nursingAssessment['nursing_notes'] }}</p>
                    </div>
                    @endif
                @endif
                @if(count($nursingProcedures) > 0)
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide mb-1">Procedures Performed</p>
                    <div class="space-y-1.5">
                        @foreach($nursingProcedures as $proc)
                        <div class="rounded bg-teal-50 border border-teal-100 px-2.5 py-1.5">
                            <p class="text-xs font-semibold text-teal-800">
                                {{ $proc['type_label'] }}
                                @if($proc['name'] !== $proc['type_label']) — {{ $proc['name'] }} @endif
                                @if($proc['site']) <span class="font-normal text-teal-600">· {{ $proc['site'] }}</span> @endif
                                @if($proc['performed_at']) <span class="font-normal text-slate-400">· {{ $proc['performed_at'] }}</span> @endif
                            </p>
                            @if($proc['outcome'])
                                <p class="text-xs text-teal-700 mt-0.5">{{ $proc['outcome'] }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Documents attached at reception (current encounter) --}}
        @if(count($encounterDocuments) > 0)
        <div class="border-b border-slate-100" x-data="{
            openDocs: true,
            viewer: { open: false, url: '', title: '', isImage: false },
            compare: { slots: [], open: false },
            openViewer(url, title, isImage) {
                this.viewer = { open: true, url, title, isImage };
            },
            toggleCompare(id, url, title) {
                const idx = this.compare.slots.findIndex(s => s.id === id);
                if (idx >= 0) {
                    this.compare.slots.splice(idx, 1);
                } else if (this.compare.slots.length < 2) {
                    this.compare.slots.push({ id, url, title });
                }
            },
            inCompare(id) { return this.compare.slots.some(s => s.id === id); }
        }">
            {{-- Section header --}}
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="openDocs = !openDocs">
                <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">
                    Documents — This Visit
                    <span class="normal-case font-normal text-slate-400">({{ count($encounterDocuments) }})</span>
                </span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="openDocs ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="openDocs" class="px-4 pb-3 space-y-1.5">

                {{-- Compare bar --}}
                <div x-show="compare.slots.length > 0"
                     class="flex items-center justify-between bg-indigo-50 border border-indigo-200 rounded-lg px-3 py-1.5">
                    <span class="text-xs text-indigo-700 font-medium"
                          x-text="compare.slots.length === 1 ? 'Select one more to compare' : '2 selected'"></span>
                    <div class="flex gap-2">
                        <button @click="compare.slots = []"
                                class="text-xs text-slate-500 hover:text-slate-700">Clear</button>
                        <button x-show="compare.slots.length === 2"
                                @click="compare.open = true"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Compare →</button>
                    </div>
                </div>

                @foreach($encounterDocuments as $doc)
                @php
                    $isImage = in_array($doc['mime_type'], ['image/jpeg','image/png','image/gif']);
                    $viewUrl  = $doc['is_viewable'] ? route('tenant.documents.view', $doc['id']) : null;
                    $dlUrl    = route('tenant.documents.download', $doc['id']);
                    $label    = $doc['title'] ?: $doc['original_filename'];
                @endphp
                <div class="flex items-start gap-2 rounded border bg-slate-50 px-2.5 py-2 transition"
                     :class="inCompare({{ $doc['id'] }}) ? 'border-indigo-400 bg-indigo-50' : 'border-slate-100'">

                    {{-- Mime icon --}}
                    <div class="flex-shrink-0 mt-0.5">
                        @if($isImage)
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        @elseif($doc['mime_type'] === 'application/pdf')
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        @else
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ $label }}</p>
                        @if($doc['description'])
                            <p class="text-xs text-slate-500">{{ $doc['description'] }}</p>
                        @endif
                        <p class="text-xs text-slate-400">{{ strtoupper($doc['extension']) }} · {{ $doc['file_size_label'] }}</p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex-shrink-0 flex flex-col items-end gap-1 mt-0.5">
                        @if($viewUrl)
                        <button @click="openViewer('{{ $viewUrl }}', '{{ addslashes($label) }}', {{ $isImage ? 'true' : 'false' }})"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 leading-none">
                            View
                        </button>
                        @if($isImage)
                        <button @click="toggleCompare({{ $doc['id'] }}, '{{ $viewUrl }}', '{{ addslashes($label) }}')"
                                :class="inCompare({{ $doc['id'] }}) ? 'text-indigo-700 font-semibold' : 'text-slate-400 hover:text-indigo-600'"
                                class="text-xs leading-none transition">
                            <span x-text="inCompare({{ $doc['id'] }}) ? '✓ Compare' : 'Compare'"></span>
                        </button>
                        @endif
                        @else
                        <a href="{{ $dlUrl }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 leading-none">Download</a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ── Single-document viewer overlay ── --}}
            <template x-teleport="body">
            <div x-show="viewer.open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @keydown.escape.window="viewer.open = false"
                 class="fixed inset-0 z-[60] flex flex-col bg-black/90"
                 style="display:none">

                {{-- Viewer toolbar --}}
                <div class="flex items-center justify-between px-5 py-3 bg-black/70 flex-shrink-0">
                    <span class="text-sm font-medium text-white truncate max-w-xs" x-text="viewer.title"></span>
                    <div class="flex items-center gap-4">
                        <a :href="viewer.url.replace('/view', '/download')"
                           target="_blank"
                           class="text-xs text-slate-300 hover:text-white font-medium transition">
                            Download
                        </a>
                        <button @click="viewer.open = false" class="text-slate-300 hover:text-white text-2xl leading-none">&times;</button>
                    </div>
                </div>

                {{-- Image viewer --}}
                <div x-show="viewer.isImage"
                     class="flex-1 flex items-center justify-center p-6 overflow-auto">
                    <img :src="viewer.url"
                         class="max-w-full max-h-full object-contain rounded shadow-2xl select-none"
                         alt="">
                </div>

                {{-- PDF viewer --}}
                <div x-show="!viewer.isImage"
                     class="flex-1 overflow-hidden">
                    <iframe :src="viewer.url"
                            class="w-full h-full border-0"
                            x-show="!viewer.isImage">
                    </iframe>
                </div>
            </div>
            </template>

            {{-- ── Comparison overlay ── --}}
            <template x-teleport="body">
            <div x-show="compare.open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @keydown.escape.window="compare.open = false"
                 class="fixed inset-0 z-[60] flex flex-col bg-black/95"
                 style="display:none">

                {{-- Comparison toolbar --}}
                <div class="flex items-center justify-between px-5 py-3 bg-black/70 flex-shrink-0">
                    <span class="text-sm font-semibold text-white">Document Comparison</span>
                    <button @click="compare.open = false" class="text-slate-300 hover:text-white text-2xl leading-none">&times;</button>
                </div>

                {{-- Side-by-side panes --}}
                <div class="flex-1 grid grid-cols-2 gap-1 min-h-0 p-1">
                    <template x-for="(slot, idx) in compare.slots" :key="slot.id">
                        <div class="flex flex-col bg-black/40 rounded overflow-hidden">
                            <div class="px-3 py-1.5 bg-black/60 flex-shrink-0">
                                <span class="text-xs font-semibold text-slate-300" x-text="slot.title"></span>
                            </div>
                            <div class="flex-1 flex items-center justify-center overflow-auto p-2">
                                <img :src="slot.url"
                                     class="max-w-full max-h-full object-contain select-none"
                                     alt="">
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Zoom hint --}}
                <div class="text-center py-2 flex-shrink-0">
                    <span class="text-xs text-slate-500">Use Ctrl + scroll to zoom each image individually</span>
                </div>
            </div>
            </template>

        </div>
        @endif

        {{-- Past Visits --}}
        <div class="border-b border-slate-100">
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="open.history = !open.history">
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                    Visit History
                    @if($historyTotal > 0)
                        <span class="normal-case font-normal text-slate-400">({{ count($patientHistory) }} of {{ $historyTotal }})</span>
                    @endif
                </span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open.history ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="open.history" class="px-4 pb-3">
                @forelse($patientHistory as $index => $visit)
                    <div class="mb-3 last:mb-0" x-data="{ expanded: {{ $index === 0 ? 'true' : 'false' }} }">
                        <button class="w-full text-left flex items-center justify-between py-1.5 group"
                                @click="expanded = !expanded">
                            <div>
                                <p class="text-xs font-semibold text-slate-700">{{ $visit['encounter_date'] }}</p>
                                @if(!empty($visit['attending_doctor']))
                                    <p class="text-xs text-blue-600 font-medium">{{ $visit['attending_doctor'] }}</p>
                                @endif
                                @if($visit['chief_complaint'])
                                    <p class="text-xs text-slate-500 italic truncate max-w-[200px]">{{ $visit['chief_complaint'] }}</p>
                                @endif
                                {{-- Inline vitals summary on header for quick scanning --}}
                                @if(!empty($visit['vitals']))
                                @php $hv = $visit['vitals']; $hHtn = (!empty($hv['systolic_bp']) && ($hv['systolic_bp'] >= 140 || $hv['diastolic_bp'] >= 90)); @endphp
                                <p class="text-xs text-slate-400 mt-0.5 space-x-2">
                                    @if(!empty($hv['systolic_bp']))<span class="{{ $hHtn ? 'text-red-500 font-medium' : '' }}">{{ $hv['systolic_bp'] }}/{{ $hv['diastolic_bp'] }}</span>@endif
                                    @if(!empty($hv['pulse_rate']))<span>{{ $hv['pulse_rate'] }}bpm</span>@endif
                                    @if(!empty($hv['blood_glucose']))<span class="{{ $hv['blood_glucose'] > ($bloodGlucoseUnit === 'mg_dl' ? 140 : 7.8) ? 'text-orange-500 font-medium' : '' }}">{{ $hv['blood_glucose'] }}{{ $bloodGlucoseUnit === 'mg_dl' ? 'mg/dL' : 'mmol' }}</span>@endif
                                </p>
                                @endif
                            </div>
                            <svg class="w-3 h-3 text-slate-400 transition-transform flex-shrink-0 ml-2" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="expanded" class="pl-2 border-l-2 border-slate-200 mt-1 space-y-1.5">
                            {{-- Per-visit vitals grid --}}
                            @if(!empty($visit['vitals']))
                            @php $hv = $visit['vitals']; @endphp
                            <div class="bg-slate-50 rounded px-2 py-1.5 grid grid-cols-3 gap-x-2 gap-y-1">
                                @if(!empty($hv['systolic_bp']))
                                @php $hHtn = $hv['systolic_bp'] >= 140 || $hv['diastolic_bp'] >= 90; @endphp
                                <div><p class="text-xs text-slate-400">BP</p><p class="text-xs font-semibold {{ $hHtn ? 'text-red-600' : 'text-slate-700' }}">{{ $hv['systolic_bp'] }}/{{ $hv['diastolic_bp'] }}</p></div>
                                @endif
                                @if(!empty($hv['pulse_rate']))
                                <div><p class="text-xs text-slate-400">HR</p><p class="text-xs font-semibold text-slate-700">{{ $hv['pulse_rate'] }}bpm</p></div>
                                @endif
                                @if(!empty($hv['temperature']))
                                <div><p class="text-xs text-slate-400">Temp</p><p class="text-xs font-semibold {{ $hv['temperature'] >= 38 ? 'text-orange-600' : 'text-slate-700' }}">{{ $hv['temperature'] }}°C</p></div>
                                @endif
                                @if(!empty($hv['oxygen_saturation']))
                                <div><p class="text-xs text-slate-400">SpO₂</p><p class="text-xs font-semibold {{ $hv['oxygen_saturation'] < 95 ? 'text-red-600' : 'text-slate-700' }}">{{ $hv['oxygen_saturation'] }}%</p></div>
                                @endif
                                @if(!empty($hv['weight_kg']))
                                <div><p class="text-xs text-slate-400">Wt</p><p class="text-xs font-semibold text-slate-700">{{ $hv['weight_kg'] }}kg</p></div>
                                @endif
                                @if(!empty($hv['blood_glucose']))
                                <div><p class="text-xs text-slate-400">Glucose <span class="text-slate-300">({{ $bloodGlucoseUnit === 'mg_dl' ? 'mg/dL' : 'mmol/L' }})</span></p><p class="text-xs font-semibold {{ $hv['blood_glucose'] > ($bloodGlucoseUnit === 'mg_dl' ? 140 : 7.8) ? 'text-orange-500' : 'text-slate-700' }}">{{ $hv['blood_glucose'] }}</p></div>
                                @endif
                            </div>
                            @endif
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
                            @if(!empty($visit['procedures']))
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Procedures</p>
                                    <div class="space-y-0.5">
                                        @foreach($visit['procedures'] as $proc)
                                        <p class="text-xs text-slate-600">
                                            <span class="font-medium">{{ $proc['type_label'] }}:</span>
                                            {{ $proc['name'] }}
                                            @if($proc['site']) <span class="text-slate-400">({{ $proc['site'] }})</span>@endif
                                            — <span class="text-slate-400">{{ $proc['role'] === 'doctor' ? 'Dr.' : 'Nurse' }} {{ $proc['performer'] }}</span>
                                        </p>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if(!empty($visit['prescriptions']))
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Medications Prescribed</p>
                                    <div class="space-y-0.5">
                                        @foreach($visit['prescriptions'] as $rx)
                                        <p class="text-xs text-slate-600">
                                            <span class="font-medium">{{ $rx['name'] }}</span>
                                            @if($rx['dose']) {{ $rx['dose'] }}@endif
                                            @if($rx['frequency']) — {{ $rx['frequency'] }}@endif
                                            @if($rx['duration']) for {{ $rx['duration'] }}@endif
                                        </p>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if(!empty($visit['lab_orders']))
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Labs Ordered</p>
                                    <p class="text-xs text-slate-600">{{ implode(', ', $visit['lab_orders']) }}</p>
                                </div>
                            @endif
                            @if(!empty($visit['referrals']))
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Referrals</p>
                                    @foreach($visit['referrals'] as $ref)
                                    <p class="text-xs text-slate-600">
                                        <span class="font-medium">{{ $ref['to'] }}</span>
                                        @if($ref['specialty']) ({{ $ref['specialty'] }})@endif
                                        — <span class="capitalize text-slate-400">{{ $ref['urgency'] }}</span>
                                    </p>
                                    @endforeach
                                </div>
                            @endif
                            @if(!empty($visit['instructions']))
                                <div>
                                    <p class="text-xs font-semibold text-slate-400 uppercase mb-0.5">Patient Instructions</p>
                                    <p class="text-xs text-slate-600">{{ Str::limit($visit['instructions'], 200) }}</p>
                                </div>
                            @endif
                            @if(!empty($visit['follow_up']))
                                <p class="text-xs text-slate-400">
                                    Follow-up: <span class="text-slate-600 font-medium">{{ $visit['follow_up']['target_date'] }}</span>
                                    @if($visit['follow_up']['reason']) — {{ $visit['follow_up']['reason'] }}@endif
                                </p>
                            @endif
                            @if(!empty($visit['attending_doctor']))
                                <p class="text-xs text-slate-400">Attending: <span class="text-slate-600">{{ $visit['attending_doctor'] }}</span></p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic">No previous visits on record.</p>
                @endforelse

                @if(count($patientHistory) < $historyTotal)
                    <button wire:click="loadMoreHistory"
                            wire:loading.attr="disabled"
                            wire:target="loadMoreHistory"
                            class="w-full mt-2 py-1.5 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition border border-blue-100">
                        <span wire:loading.remove wire:target="loadMoreHistory">
                            Show {{ min(5, $historyTotal - count($patientHistory)) }} more
                            ({{ $historyTotal - count($patientHistory) }} remaining)
                        </span>
                        <span wire:loading wire:target="loadMoreHistory">Loading…</span>
                    </button>
                @endif
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
            <div x-show="open.labs" class="px-4 pb-3 space-y-2">
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
            <div x-show="open.imaging" class="px-4 pb-3">

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

        {{-- Family History --}}
        @if(count($familyHistories) > 0)
        <div class="border-b border-slate-100" x-data="{ openFamily: false }">
            <button class="w-full px-4 py-2.5 flex items-center justify-between text-left hover:bg-slate-50 transition"
                    @click="openFamily = !openFamily">
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                    Family History
                    <span class="normal-case font-normal text-slate-400">({{ count($familyHistories) }})</span>
                </span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="openFamily ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="openFamily" class="px-4 pb-3 space-y-1.5">
                @foreach($familyHistories as $fh)
                <div class="flex items-baseline gap-2">
                    <span class="text-xs font-semibold text-slate-500 flex-shrink-0 w-28">{{ $fh['relationship_label'] }}</span>
                    <div class="flex-1 min-w-0">
                        <span class="text-xs text-slate-800">{{ $fh['condition'] }}</span>
                        @if($fh['notes'])
                            <span class="text-xs text-slate-400"> — {{ $fh['notes'] }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="h-4 flex-shrink-0"></div>

    </div>

    @endif {{-- end activeEntryId --}}

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


</div>{{-- end root Livewire div --}}

@push('body-scripts')
<script>
function panelLayout() {
    const QUEUE_KEY   = 'doc_queue_w';
    const CONTEXT_KEY = 'doc_context_w';
    const QUEUE_MIN   = 48;
    const QUEUE_DEF   = 256;
    const CONTEXT_MIN = 240;
    const CONTEXT_DEF = 320;

    return {
        queueW:        QUEUE_DEF,
        contextW:      CONTEXT_DEF,
        queueCollapsed: false,
        _dragging:     null,
        _startX:       0,
        _startW:       0,

        init() {
            try {
                const qw = parseInt(localStorage.getItem(QUEUE_KEY));
                const cw = parseInt(localStorage.getItem(CONTEXT_KEY));
                const qc = localStorage.getItem('doc_queue_collapsed');
                if (qw >= QUEUE_MIN) this.queueW = qw;
                if (cw >= CONTEXT_MIN) this.contextW = cw;
                if (qc === '1') { this.queueCollapsed = true; this.queueW = QUEUE_MIN; }
            } catch {}

            const onMove = (e) => this._onMove(e);
            const onUp   = (e) => this._onUp(e);
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup',   onUp);
            this.$el.addEventListener('selectstart', (e) => { if (this._dragging) e.preventDefault(); });
        },

        queueStyle() {
            return 'width:' + this.queueW + 'px;min-width:' + this.queueW + 'px;max-width:' + this.queueW + 'px;';
        },

        contextStyle() {
            return 'width:' + this.contextW + 'px;min-width:' + this.contextW + 'px;max-width:' + this.contextW + 'px;';
        },

        toggleQueue() {
            this.queueCollapsed = !this.queueCollapsed;
            this.queueW = this.queueCollapsed ? QUEUE_MIN : QUEUE_DEF;
            try {
                localStorage.setItem('doc_queue_collapsed', this.queueCollapsed ? '1' : '0');
                localStorage.setItem(QUEUE_KEY, this.queueW);
            } catch {}
        },

        startDrag(e, panel) {
            this._dragging = panel;
            this._startX   = e.clientX;
            this._startW   = panel === 'queue' ? this.queueW : this.contextW;
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        },

        _onMove(e) {
            if (!this._dragging) return;
            const dx = e.clientX - this._startX;
            if (this._dragging === 'queue') {
                const nw = Math.max(QUEUE_MIN, this._startW + dx);
                this.queueW = nw;
                this.queueCollapsed = nw <= QUEUE_MIN;
            } else {
                this.contextW = Math.max(CONTEXT_MIN, Math.min(600, this._startW - dx));
            }
        },

        _onUp() {
            if (!this._dragging) return;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            try {
                localStorage.setItem(QUEUE_KEY, this.queueW);
                localStorage.setItem(CONTEXT_KEY, this.contextW);
                localStorage.setItem('doc_queue_collapsed', this.queueCollapsed ? '1' : '0');
            } catch {}
            this._dragging = null;
        },
    };
}
</script>
@endpush
