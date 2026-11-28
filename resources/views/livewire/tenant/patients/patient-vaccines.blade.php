<div class="space-y-5">

    @if(session('vaccine_saved'))
    <div class="rounded-xl bg-teal-50 border border-teal-200 px-4 py-3 text-sm text-teal-800">
        {{ session('vaccine_saved') }}
    </div>
    @endif

    {{-- Record form --}}
    @if($showForm)
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 space-y-4">
        <h3 class="text-sm font-semibold text-blue-900">Record Vaccination</h3>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">Date administered <span class="text-red-500">*</span></label>
                <input type="date" wire:model="formDate" max="{{ now()->toDateString() }}"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none @error('formDate') border-red-400 @enderror">
                @error('formDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">Lot number</label>
                <input type="text" wire:model="formLotNumber" placeholder="e.g. AB1234"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">Site</label>
                <select wire:model="formSite"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none bg-white">
                    <option value="">— Select site —</option>
                    <option>Left thigh</option>
                    <option>Right thigh</option>
                    <option>Left arm</option>
                    <option>Right arm</option>
                    <option>Left deltoid</option>
                    <option>Right deltoid</option>
                    <option>Oral</option>
                    <option>Intradermal (forearm)</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">Route</label>
                <select wire:model="formRoute"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none bg-white">
                    <option value="IM">Intramuscular (IM)</option>
                    <option value="SC">Subcutaneous (SC)</option>
                    <option value="Oral">Oral</option>
                    <option value="ID">Intradermal (ID)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-700 block mb-1.5">Notes</label>
            <input type="text" wire:model="formNotes" placeholder="Any reactions, batch notes…"
                   class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none">
        </div>

        <div class="flex gap-3">
            <button wire:click="saveVaccination"
                    class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                Save
            </button>
            <button wire:click="cancelForm"
                    class="rounded-xl border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                Cancel
            </button>
        </div>
    </div>
    @endif

    {{-- Schedule table --}}
    @php
        $grouped = collect($schedule)->groupBy(fn($row) => $row['vaccine']->id);
    @endphp

    @foreach($grouped as $vaccineId => $rows)
    @php $vaccine = $rows->first()['vaccine']; @endphp
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
            <div>
                <span class="font-semibold text-slate-800 text-sm">{{ $vaccine->name }}</span>
                @if($vaccine->disease_target)
                    <span class="ml-2 text-xs text-slate-400">{{ $vaccine->disease_target }}</span>
                @endif
            </div>
            <span class="text-xs text-slate-400">{{ $vaccine->type === 'single_dose' ? 'Single dose' : count($rows).' dose series' }}</span>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">Dose</th>
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">Recommended</th>
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">Status</th>
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">Given on</th>
                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500">By / Lot</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($rows as $row)
                @php
                    $dose   = $row['dose'];
                    $given  = $row['given'];
                    $status = $row['status'];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-4 py-3 font-medium text-slate-700">{{ $dose->dose_label }}</td>
                    <td class="px-4 py-3 text-slate-500">
                        @if($row['due_date'])
                            {{ $row['due_date']->format('d M Y') }}
                        @elseif($dose->reference === 'prev_dose')
                            <span class="text-slate-400 italic">After previous dose</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($status === 'given')
                            <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>Given
                            </span>
                        @elseif($status === 'overdue')
                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Overdue
                            </span>
                        @elseif($status === 'upcoming')
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>Upcoming
                            </span>
                        @else
                            <span class="text-slate-400 text-xs">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        {{ $given ? $given->administered_on->format('d M Y') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">
                        @if($given)
                            {{ $given->administeredBy?->name ?? '—' }}
                            @if($given->lot_number)
                                <span class="text-slate-400">· {{ $given->lot_number }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['can_record'] && !$showForm)
                        <button wire:click="openRecord({{ $vaccine->id }}, {{ $dose->id }}, {{ $dose->dose_number }})"
                                class="text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors">
                            Record
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    @if(empty($schedule))
    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
        <p class="text-sm text-slate-500">No active vaccines in the catalog.</p>
    </div>
    @endif

</div>
