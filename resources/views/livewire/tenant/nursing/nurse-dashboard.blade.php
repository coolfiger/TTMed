<div class="p-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nursing Station</h1>
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
                <h2 class="font-semibold text-gray-700 dark:text-gray-300">Waiting Queue</h2>
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
                            <span class="text-xs px-2 py-0.5 rounded-full
                                {{ $entry['status'] === 'with_nurse' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' }}">
                                {{ $entry['status_label'] }}
                            </span>
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
                        <button wire:click="completeAndSendToDoctor"
                                wire:confirm="Mark nursing complete and send patient to doctor?"
                                class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Complete &amp; Send to Doctor
                        </button>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="border-b border-gray-200 dark:border-gray-700 flex">
                        @foreach(['vitals' => 'Vitals', 'assessment' => 'Assessment', 'procedures' => 'Procedures'] as $tab => $label)
                            <button wire:click="$set('activeTab', '{{ $tab }}')"
                                    class="px-5 py-3 text-sm font-medium border-b-2 transition
                                        {{ $activeTab === $tab
                                            ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div class="p-6">

                        {{-- ---- Vitals Tab ---- --}}
                        @if($activeTab === 'vitals')
                            <form wire:submit="saveVitals" class="space-y-5">

                                {{-- Blood Pressure & Pulse --}}
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Systolic (mmHg)</label>
                                        <input wire:model="systolic" type="number" min="50" max="300"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Diastolic (mmHg)</label>
                                        <input wire:model="diastolic" type="number" min="20" max="200"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pulse (bpm)</label>
                                        <input wire:model="pulse" type="number" min="20" max="300"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Resp. Rate (br/min)</label>
                                        <input wire:model="respiratoryRate" type="number" min="1" max="60"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                </div>

                                {{-- O2 Sat & Temperature --}}
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">SpO₂ (%)</label>
                                        <input wire:model="oxygenSat" type="number" step="0.1" min="50" max="100"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Temperature (°C)</label>
                                        <input wire:model="temperature" type="number" step="0.1" min="30" max="45"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Site</label>
                                        <select wire:model="tempSite"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                            <option value="oral">Oral</option>
                                            <option value="axillary">Axillary</option>
                                            <option value="rectal">Rectal</option>
                                            <option value="tympanic">Tympanic</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pain Score (0–10)</label>
                                        <input wire:model="painScore" type="number" min="0" max="10"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                </div>

                                {{-- Anthropometrics with live BMI --}}
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Weight (kg)</label>
                                        <input wire:model.live="weightKg" type="number" step="0.1" min="0.5" max="500"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Height (cm)</label>
                                        <input wire:model.live="heightCm" type="number" step="0.1" min="20" max="250"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">BMI</label>
                                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 min-h-[38px]">
                                            {{ $bmi ?: '—' }}
                                        </div>
                                    </div>
                                    <div></div>
                                </div>

                                {{-- Blood Glucose --}}
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Blood Glucose (mmol/L)</label>
                                        <input wire:model="bloodGlucose" type="number" step="0.1" min="0.5" max="50"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Timing</label>
                                        <select wire:model="glucoseTiming"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                            <option value="fasting">Fasting</option>
                                            <option value="random">Random</option>
                                            <option value="post_prandial">Post-prandial</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Notes</label>
                                    <textarea wire:model="vitalsNotes" rows="2"
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                        Save Vitals
                                    </button>
                                </div>
                            </form>
                        @endif

                        {{-- ---- Assessment Tab ---- --}}
                        @if($activeTab === 'assessment')
                            <form wire:submit="saveAssessment" class="space-y-5">
                                <div class="space-y-3">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input wire:model="allergiesReviewed" type="checkbox"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Allergies reviewed with patient</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input wire:model="medicationsReviewed" type="checkbox"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                        <span class="text-sm text-gray-700 dark:text-gray-300">Current medications reviewed</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Assessment Notes</label>
                                    <textarea wire:model="assessmentNotes" rows="4"
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                                              placeholder="Nursing observations, patient concerns..."></textarea>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                        Save Assessment
                                    </button>
                                </div>
                            </form>
                        @endif

                        {{-- ---- Procedures Tab ---- --}}
                        @if($activeTab === 'procedures')
                            <div class="space-y-4">
                                @if(!$showProcedureForm)
                                    <div class="flex justify-end">
                                        <button wire:click="$set('showProcedureForm', true)"
                                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                            + Add Procedure
                                        </button>
                                    </div>
                                @else
                                    <form wire:submit="saveProcedure" class="space-y-4 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type</label>
                                                <select wire:model="procedureType"
                                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                                    <option value="">Select...</option>
                                                    @foreach(\App\Models\Tenant\NursingProcedure::TYPES as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('procedureType') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Name / Description</label>
                                                <input wire:model="procedureName" type="text" maxlength="200"
                                                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                                @error('procedureName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Site</label>
                                                <input wire:model="procedureSite" type="text"
                                                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Outcome</label>
                                                <input wire:model="procedureOutcome" type="text"
                                                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Details</label>
                                            <textarea wire:model="procedureDetails" rows="2"
                                                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                                        </div>
                                        <div class="flex gap-3 justify-end">
                                            <button type="button" wire:click="$set('showProcedureForm', false)"
                                                    class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                                Save Procedure
                                            </button>
                                        </div>
                                    </form>
                                @endif

                                {{-- Procedure history for active encounter --}}
                                @if($activeEncounterId)
                                    @php
                                        $procedures = \App\Models\Tenant\NursingProcedure::on('tenant')
                                            ->where('encounter_id', $activeEncounterId)
                                            ->orderByDesc('performed_at')
                                            ->get();
                                    @endphp
                                    @if($procedures->isNotEmpty())
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-xs text-gray-500 uppercase tracking-wide border-b dark:border-gray-700">
                                                    <th class="text-left py-2 pr-4">Procedure</th>
                                                    <th class="text-left py-2 pr-4">Site</th>
                                                    <th class="text-left py-2 pr-4">Outcome</th>
                                                    <th class="text-left py-2">Time</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                @foreach($procedures as $proc)
                                                    <tr>
                                                        <td class="py-2 pr-4">
                                                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $proc->type_label }}</span>
                                                            @if($proc->procedure_name !== $proc->type_label)
                                                                <br><span class="text-xs text-gray-500">{{ $proc->procedure_name }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">{{ $proc->site ?? '—' }}</td>
                                                        <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">{{ $proc->outcome ?? '—' }}</td>
                                                        <td class="py-2 text-gray-500 text-xs">{{ $proc->performed_at?->format('H:i') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p class="text-sm text-gray-400 py-4 text-center">No procedures recorded yet.</p>
                                    @endif
                                @endif
                            </div>
                        @endif

                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
