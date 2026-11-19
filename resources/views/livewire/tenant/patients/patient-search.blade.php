<div class="flex flex-col h-full min-h-0 bg-slate-100">

    {{-- Top bar --}}
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-lg font-bold text-slate-800">Patients</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ $patients->total() }} {{ Str::plural('patient', $patients->total()) }} registered</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <input wire:model.live.debounce.300ms="search" type="search"
                       placeholder="Name, number, phone, ID…"
                       class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm shadow-sm w-64 focus:border-blue-500 focus:bg-white focus:outline-none transition" />
                <input wire:model.live="dobFilter" type="date"
                       class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:outline-none transition" />
                <select wire:model.live="branchFilter"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:outline-none transition">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @if(auth()->guard('tenant')->user()?->hasPermission('patients.create'))
                    <a href="{{ route('tenant.patients.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition whitespace-nowrap shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Register Patient
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="flex-1 overflow-y-auto px-6 py-5">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-10"></th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Patient</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">ID #</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">DOB / Age</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Phone</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Doctor</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Branch</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($patients as $patient)
                        <tr onclick="window.location='{{ route('tenant.patients.show', $patient->id) }}'"
                            class="hover:bg-blue-50 cursor-pointer transition group">
                            <td class="px-5 py-3">
                                <div class="w-9 h-9 rounded-full bg-blue-100 group-hover:bg-blue-200 flex items-center justify-center transition">
                                    <span class="text-xs font-bold text-blue-700">
                                        {{ mb_strtoupper(mb_substr($patient->first_name, 0, 1) . mb_substr($patient->last_name, 0, 1)) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="text-sm font-semibold text-slate-800 group-hover:text-blue-700 transition">
                                    {{ $patient->display_name }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    @if($patient->allergies->isNotEmpty())
                                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-xs bg-red-100 text-red-700 font-medium">
                                            ⚠ {{ $patient->allergies->count() }}
                                        </span>
                                    @endif
                                    @if($patient->blood_type)
                                        <span class="text-xs text-slate-400">{{ $patient->blood_type }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-sm font-mono text-slate-500">{{ $patient->patient_number }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">
                                {{ $patient->date_of_birth?->format('d M Y') ?? '—' }}
                                @if($patient->age !== null)
                                    <span class="text-slate-400 text-xs ml-1">{{ $patient->age }}y</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $patient->phone_primary ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $patient->primaryDoctor?->staffProfile?->full_name ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-600">{{ $patient->branch?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="text-slate-300 text-4xl mb-3">👤</div>
                                <p class="text-sm font-medium text-slate-500">
                                    {{ $search || $dobFilter ? 'No patients match your search.' : 'No patients registered yet.' }}
                                </p>
                                @if(!$search && !$dobFilter && auth()->guard('tenant')->user()?->hasPermission('patients.create'))
                                    <a href="{{ route('tenant.patients.create') }}"
                                       class="mt-3 inline-block text-sm font-semibold text-blue-600 hover:text-blue-800">
                                        Register the first patient →
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($patients->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50">
                    {{ $patients->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
