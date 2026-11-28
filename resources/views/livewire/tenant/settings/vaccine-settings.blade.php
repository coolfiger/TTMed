<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Vaccine Settings</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage the T&T EPI vaccine catalog and control which doctors can record vaccinations.</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-slate-200">
        <button wire:click="$set('activeTab', 'catalog')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px
                       {{ $activeTab === 'catalog' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Vaccine Catalog
        </button>
        <button wire:click="$set('activeTab', 'doctors')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px
                       {{ $activeTab === 'doctors' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Doctor Access
        </button>
    </div>

    {{-- Catalog tab --}}
    @if($activeTab === 'catalog')
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Vaccine</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Targets</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Schedule</th>
                    <th class="text-center px-4 py-3 font-semibold text-slate-600">Active</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($vaccines as $vaccine)
                <tr class="{{ $vaccine->is_active ? '' : 'opacity-50' }} hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800">{{ $vaccine->name }}</p>
                        @if($vaccine->description)
                            <p class="text-xs text-slate-400 mt-0.5">{{ $vaccine->description }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $vaccine->disease_target ?: '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="space-y-0.5">
                            @foreach($vaccine->doseDefinitions as $dose)
                            <div class="text-xs text-slate-600">
                                <span class="font-medium">{{ $dose->dose_label }}:</span>
                                @if($dose->reference === 'dob')
                                    @if($dose->reference_weeks === 0)
                                        At birth
                                    @elseif($dose->reference_weeks < 52)
                                        {{ $dose->reference_weeks }} wks
                                    @elseif($dose->reference_weeks < 104)
                                        {{ round($dose->reference_weeks / 4.33) }} mo
                                    @else
                                        ~{{ round($dose->reference_weeks / 52) }} yrs
                                    @endif
                                @else
                                    +{{ $dose->reference_weeks }} wks after prev
                                @endif
                                @if($dose->notes)
                                    <span class="text-slate-400">({{ $dose->notes }})</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button wire:click="toggleVaccine({{ $vaccine->id }})"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none
                                       {{ $vaccine->is_active ? 'bg-teal-500' : 'bg-slate-300' }}">
                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                         {{ $vaccine->is_active ? 'translate-x-4' : 'translate-x-1' }}"></span>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Doctors tab --}}
    @if($activeTab === 'doctors')
    <div class="space-y-3">
        <p class="text-sm text-slate-500">Enable the vaccine module for a doctor to allow them to view and record patient vaccinations.</p>

        @if($doctors->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                <p class="text-sm text-slate-500">No active doctors found.</p>
            </div>
        @else
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Doctor</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-600">Vaccine module</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($doctors as $doctor)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $doctor->name }}</td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleDoctorModule({{ $doctor->id }})"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none
                                           {{ $doctor->vaccine_module_enabled ? 'bg-teal-500' : 'bg-slate-300' }}">
                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                             {{ $doctor->vaccine_module_enabled ? 'translate-x-4' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

</div>
