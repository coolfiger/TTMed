<div>
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    <div class="flex gap-3 mb-6 justify-between items-end">
        <div class="flex gap-3 flex-wrap">
            <input wire:model.live.debounce.300ms="search" type="search"
                placeholder="Name, number, phone, ID…"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm w-72 focus:border-blue-500 focus:outline-none">
            <input wire:model.live="dobFilter" type="date"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
            <select wire:model.live="branchFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        @if(auth()->guard('tenant')->user()?->hasPermission('patients.create'))
        <a href="{{ route('tenant.patients.create') }}"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 whitespace-nowrap">
            Register Patient
        </a>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">DOB / Age</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Branch</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($patients as $patient)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3">
                        <div class="text-sm font-medium text-slate-800">{{ $patient->display_name }}</div>
                        @if($patient->allergies->isNotEmpty())
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs bg-red-50 text-red-700 mt-0.5">
                                ⚠ Allergies
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $patient->patient_number }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">
                        {{ $patient->date_of_birth?->format('d M Y') ?? '—' }}
                        @if($patient->age !== null)
                            <span class="text-slate-400">({{ $patient->age }}y)</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $patient->phone_primary ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $patient->branch?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('tenant.patients.show', $patient->id) }}"
                            class="text-xs font-medium text-blue-600 hover:text-blue-800">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">
                        {{ $search || $dobFilter ? 'No patients match your search.' : 'No patients registered yet.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $patients->links() }}
        </div>
    </div>
</div>
