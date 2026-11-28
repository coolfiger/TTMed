<div class="flex flex-col h-full min-h-0">

    {{-- Page header --}}
    <div class="flex-shrink-0 bg-white border-b border-slate-200 px-6 py-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('tenant.prescriptions.index') }}"
                   class="flex-shrink-0 text-slate-400 hover:text-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-slate-900 truncate">
                        Prescription — {{ $patientName }}
                    </h1>
                    @if($prescription)
                    <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $prescription->prescription_number }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                @if($prescription && $prescription->isIssued())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Issued
                    </span>
                    <a href="{{ route('tenant.prescriptions.download', $prescription->id) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download PDF
                    </a>
                @elseif($prescription && $prescription->isDraft())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Draft
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Scrollable body --}}
    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">

        {{-- Alerts --}}
        @if($successMessage)
        <div class="flex items-center gap-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ $successMessage }}
        </div>
        @endif
        @if($errorMessage)
        <div class="flex items-center gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ $errorMessage }}
        </div>
        @endif

        {{-- Allergy warning --}}
        @if(!empty($allergyWarnings))
        <div class="rounded-lg bg-amber-50 border border-amber-300 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-amber-900">Potential allergy conflict detected</p>
                    <p class="text-sm text-amber-800 mt-0.5">{{ implode(', ', $allergyWarnings) }}</p>
                    @if(!$allergyChecked)
                        <button wire:click="markAllergyChecked"
                                class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-amber-900 bg-amber-200 hover:bg-amber-300 px-2.5 py-1 rounded transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Confirm allergy review complete
                        </button>
                    @else
                        <p class="mt-1.5 text-xs font-semibold text-amber-700 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Allergy review completed
                        </p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Add medication (doctors with prescriptions.create only) --}}
        @if(!$isIssued)
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm"
             x-data="{ open: false }"
             x-on:click.outside="open = false">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>
                </svg>
                <h2 class="text-sm font-semibold text-slate-700">Add Medication</h2>
            </div>

            <div class="p-5">
                <div class="relative">
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input type="text"
                           wire:model.live.debounce.300ms="medSearch"
                           wire:keyup="searchMedications"
                           x-on:focus="open = ($wire.medResults.length > 0)"
                           x-on:input="open = true"
                           placeholder="Search medication by name…"
                           class="w-full border border-slate-300 rounded-md pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">

                    <div x-show="open && $wire.medResults.length > 0"
                         x-cloak
                         class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
                        @foreach($medResults as $med)
                            <button wire:click="selectMedication({{ $med['id'] }})"
                                    x-on:click="open = false"
                                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50 border-b border-slate-100 last:border-0 transition-colors">
                                <span class="font-medium text-slate-800">{{ $med['name'] }}</span>
                                @if($med['strength'])
                                    <span class="text-slate-500 ml-1">{{ $med['strength'] }}</span>
                                @endif
                                @if($med['form'])
                                    <span class="text-xs text-slate-400 ml-1.5 bg-slate-100 px-1.5 py-0.5 rounded">{{ $med['form'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                @if($showItemForm)
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Medication name</label>
                        <input type="text" wire:model="selectedMedName"
                               class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        @error('selectedMedName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Dose</label>
                        <input type="text" wire:model="dose" placeholder="e.g. 500 mg"
                               class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        @error('dose') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Frequency</label>
                        <input type="text" wire:model="frequency" placeholder="e.g. Twice daily"
                               class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        @error('frequency') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Duration</label>
                            <input type="number" wire:model="duration" min="1" max="365" placeholder="—"
                                   class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                        <div class="w-28">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">&nbsp;</label>
                            <select wire:model="durationUnit"
                                    class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                                <option value="days">Days</option>
                                <option value="weeks">Weeks</option>
                                <option value="months">Months</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Quantity</label>
                        <input type="text" wire:model="quantity" placeholder="e.g. 30 tablets"
                               class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Refills allowed</label>
                        <input type="number" wire:model="refills" min="0" max="10"
                               class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Special instructions</label>
                        <input type="text" wire:model="instructions" placeholder="e.g. Take with food, avoid alcohol"
                               class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button wire:click="$set('showItemForm', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button wire:click="addItem"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-md hover:bg-sky-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add to prescription
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Prescribed medications --}}
        @if($prescription && $prescription->items->count())
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-slate-700">Prescribed Medications</h2>
                </div>
                <span class="text-xs text-slate-400 font-medium">{{ $prescription->items->count() }} item{{ $prescription->items->count() === 1 ? '' : 's' }}</span>
            </div>
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Medication</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Dose &amp; Frequency</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Duration</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Qty</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Refills</th>
                        @if(!$isIssued)<th class="px-5 py-2.5"></th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($prescription->items as $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="font-semibold text-sm text-slate-900">{{ $item->medication_name }}</div>
                            @if($item->strength || $item->form)
                            <div class="text-xs text-slate-400 mt-0.5">
                                {{ $item->strength }}
                                @if($item->form)
                                    <span class="ml-1 bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ $item->form }}</span>
                                @endif
                            </div>
                            @endif
                            @if($item->instructions)
                            <div class="text-xs text-slate-500 italic mt-1">{{ $item->instructions }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="text-sm font-medium text-slate-800">{{ $item->dose }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $item->frequency }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-sm text-slate-600">{{ $item->duration_label ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-sm text-slate-600">{{ $item->quantity ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-sm text-slate-600">{{ $item->refills_allowed ?? 0 }}</td>
                        @if(!$isIssued)
                        <td class="px-5 py-3.5 text-right">
                            <button wire:click="removeItem({{ $item->id }})"
                                    wire:confirm="Remove {{ $item->medication_name }} from this prescription?"
                                    class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors">
                                Remove
                            </button>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Issue prescription --}}
        @if(!$isIssued && $prescription && $prescription->items->count())
        <div class="flex items-center justify-between bg-white rounded-lg border border-slate-200 shadow-sm px-5 py-4">
            <div>
                <p class="text-sm font-semibold text-slate-800">Ready to issue?</p>
                <p class="text-xs text-slate-500 mt-0.5">Once issued this prescription cannot be edited. A PDF will be generated.</p>
            </div>
            <button wire:click="issuePrescription"
                    wire:confirm="Issue this prescription? It cannot be edited after issuance."
                    class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
                </svg>
                Issue Prescription
            </button>
        </div>
        @endif

        @if(!$prescription || !$prescription->items->count())
        <div class="text-center py-12 text-slate-400">
            <svg class="mx-auto w-10 h-10 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
            </svg>
            <p class="text-sm">No medications added yet. Search above to begin.</p>
        </div>
        @endif

    </div>
</div>
