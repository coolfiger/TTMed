<div class="max-w-4xl mx-auto py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Prescription</h1>
            <p class="text-sm text-gray-500">{{ $patientName }}</p>
        </div>
        @if($prescription && $prescription->isIssued())
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    Issued — {{ $prescription->prescription_number }}
                </span>
                <a href="{{ route('tenant.prescriptions.download', $prescription->id) }}"
                   target="_blank"
                   class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Download PDF
                </a>
            </div>
        @endif
    </div>

    {{-- Alerts --}}
    @if($successMessage)
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-green-800 text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-red-800 text-sm">{{ $errorMessage }}</div>
    @endif

    {{-- Allergy warnings --}}
    @if(!empty($allergyWarnings))
        <div class="rounded-md bg-yellow-50 border border-yellow-300 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-yellow-800">Potential allergy conflict</p>
                    <p class="text-sm text-yellow-700 mt-1">{{ implode(', ', $allergyWarnings) }}</p>
                    @if(!$allergyChecked)
                        <button wire:click="markAllergyChecked"
                                class="mt-2 text-xs font-medium text-yellow-800 underline hover:no-underline">
                            Mark allergy review complete
                        </button>
                    @else
                        <p class="mt-1 text-xs text-yellow-700 font-medium">Allergy review completed.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Medication search --}}
    @if(!$isIssued)
    <div class="bg-white rounded-lg border border-gray-200 p-5"
         x-data="{ open: false }"
         x-on:click.outside="open = false">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Add Medication</h2>

        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.300ms="medSearch"
                   wire:keyup="searchMedications"
                   x-on:focus="open = ($wire.medResults.length > 0)"
                   x-on:input="open = true"
                   placeholder="Search medications…"
                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

            <div x-show="open && $wire.medResults.length > 0"
                 x-cloak
                 class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                @foreach($medResults as $med)
                    <button wire:click="selectMedication({{ $med['id'] }})"
                            x-on:click="open = false"
                            class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 border-b border-gray-100 last:border-0">
                        <span class="font-medium">{{ $med['name'] }}</span>
                        @if($med['strength']) <span class="text-gray-500">{{ $med['strength'] }}</span> @endif
                        @if($med['form']) <span class="text-xs text-gray-400 ml-1">({{ $med['form'] }})</span> @endif
                    </button>
                @endforeach
            </div>
        </div>

        @if($showItemForm)
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Medication name</label>
                <input type="text" wire:model="selectedMedName"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                @error('selectedMedName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Dose</label>
                <input type="text" wire:model="dose" placeholder="e.g. 500mg"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                @error('dose') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Frequency</label>
                <input type="text" wire:model="frequency" placeholder="e.g. Twice daily"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                @error('frequency') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Duration</label>
                    <input type="number" wire:model="duration" min="1" max="365"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div class="w-24">
                    <label class="block text-xs font-medium text-gray-600 mb-1">&nbsp;</label>
                    <select wire:model="durationUnit"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="days">Days</option>
                        <option value="weeks">Weeks</option>
                        <option value="months">Months</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                <input type="text" wire:model="quantity" placeholder="e.g. 30 tablets"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Refills allowed</label>
                <input type="number" wire:model="refills" min="0" max="10"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Instructions</label>
                <input type="text" wire:model="instructions" placeholder="e.g. Take with food"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-3 flex justify-end gap-2">
            <button wire:click="$set('showItemForm', false)"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
            <button wire:click="addItem"
                    class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Add to prescription
            </button>
        </div>
        @endif
    </div>
    @endif

    {{-- Items list --}}
    @if($prescription && $prescription->items->count())
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Prescribed Medications</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-100">
            <thead>
                <tr class="text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-5 py-2 text-left">Medication</th>
                    <th class="px-5 py-2 text-left">Dose / Frequency</th>
                    <th class="px-5 py-2 text-left">Duration</th>
                    <th class="px-5 py-2 text-left">Qty</th>
                    @if(!$isIssued)<th class="px-5 py-2"></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($prescription->items as $item)
                <tr class="text-sm">
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-900">{{ $item->medication_name }}</div>
                        @if($item->strength || $item->form)
                            <div class="text-xs text-gray-400">
                                {{ $item->strength }}
                                @if($item->form)({{ $item->form }})@endif
                            </div>
                        @endif
                        @if($item->instructions)
                            <div class="text-xs text-gray-500 italic mt-0.5">{{ $item->instructions }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $item->dose }}<br><span class="text-xs text-gray-500">{{ $item->frequency }}</span></td>
                    <td class="px-5 py-3 text-gray-600">{{ $item->duration_label ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $item->quantity ?? '—' }}</td>
                    @if(!$isIssued)
                    <td class="px-5 py-3 text-right">
                        <button wire:click="removeItem({{ $item->id }})"
                                wire:confirm="Remove this medication?"
                                class="text-xs text-red-600 hover:text-red-800">Remove</button>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Issue button --}}
    @if(!$isIssued && $prescription && $prescription->items->count())
    <div class="flex justify-end">
        <button wire:click="issuePrescription"
                wire:confirm="Issue this prescription? It cannot be edited after issuance."
                class="inline-flex items-center px-6 py-2.5 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
            Issue Prescription
        </button>
    </div>
    @endif

</div>
