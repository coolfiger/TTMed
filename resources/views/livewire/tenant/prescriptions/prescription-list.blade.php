<div class="flex flex-col h-full min-h-0">

    {{-- Page header --}}
    <div class="flex-shrink-0 bg-white border-b border-slate-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">Prescriptions</h1>
                <p class="text-sm text-slate-500 mt-0.5">Search, review and download issued prescriptions</p>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="text-slate-400">Draft: <span class="font-semibold text-amber-600">{{ $counts['draft'] }}</span></span>
                <span class="text-slate-400">Issued: <span class="font-semibold text-emerald-600">{{ $counts['issued'] }}</span></span>
                @if($counts['cancelled'])
                <span class="text-slate-400">Cancelled: <span class="font-semibold text-red-500">{{ $counts['cancelled'] }}</span></span>
                @endif
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex-shrink-0 bg-slate-50 border-b border-slate-200 px-6 py-3">
        <div class="flex flex-wrap items-end gap-3">
            {{-- Rx number --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Rx Number</label>
                <div class="relative">
                    <svg class="absolute left-2.5 top-2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="rxSearch" type="search"
                        placeholder="e.g. RX-00142"
                        class="pl-7 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm shadow-sm w-40 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                </div>
            </div>

            {{-- Patient --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Patient</label>
                <div class="relative">
                    <svg class="absolute left-2.5 top-2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="patientSearch" type="search"
                        placeholder="Name or patient #"
                        class="pl-7 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm shadow-sm w-48 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select wire:model.live="statusFilter"
                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="issued">Issued</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            {{-- Branch --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Branch</label>
                <select wire:model.live="branchFilter"
                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                    <option value="">All branches</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date range --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                <input wire:model.live="dateFrom" type="date"
                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
                <input wire:model.live="dateTo" type="date"
                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none">
            </div>

            @if($patientSearch || $rxSearch || $statusFilter || $branchFilter || $dateFrom || $dateTo)
            <div class="pb-0.5">
                <button wire:click="$set('patientSearch', ''); $set('rxSearch', ''); $set('statusFilter', ''); $set('branchFilter', ''); $set('dateFrom', ''); $set('dateTo', '')"
                        class="text-xs text-slate-400 hover:text-slate-700 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Clear
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="flex-1 overflow-y-auto px-6 py-4">
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Rx #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Prescribing Doctor</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($prescriptions as $rx)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-mono text-sm font-semibold text-slate-700">{{ $rx->prescription_number ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('tenant.patients.show', $rx->patient_id) }}"
                               class="text-sm font-medium text-slate-800 hover:text-sky-600 transition-colors">
                                {{ $rx->patient->full_name ?? '—' }}
                            </a>
                            <div class="text-xs text-slate-400 font-mono">{{ $rx->patient->patient_number ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            {{ $rx->doctor->staffProfile->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                {{ $rx->items->count() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($rx->isIssued())
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Issued
                                </span>
                            @elseif($rx->isCancelled())
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 ring-1 ring-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Cancelled
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Draft
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-500 tabular-nums">
                            @if($rx->isIssued() && $rx->issued_at)
                                {{ $rx->issued_at->format('d M Y') }}
                                <div class="text-xs text-slate-400">{{ $rx->issued_at->format('g:i A') }}</div>
                            @else
                                {{ $rx->created_at->format('d M Y') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($rx->isIssued())
                                <a href="{{ route('tenant.prescriptions.download', $rx->id) }}"
                                   class="inline-flex items-center gap-1 text-xs font-medium text-sky-600 hover:text-sky-800 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    PDF
                                </a>
                                @endif
                                @if($rx->encounter_id && $canCreate)
                                <a href="{{ route('tenant.prescriptions.encounter', $rx->encounter_id) }}"
                                   class="text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
                                    {{ $rx->isDraft() ? 'Edit' : 'View' }}
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <svg class="mx-auto w-8 h-8 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm text-slate-400">No prescriptions match your filters.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($prescriptions->hasPages())
        <div class="mt-4">{{ $prescriptions->links() }}</div>
        @endif
    </div>

</div>
