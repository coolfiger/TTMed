<div class="flex flex-col h-full min-h-0 bg-slate-100">

    {{-- Header --}}
    <div class="page-topbar">
        <div>
            <h1 class="text-lg font-bold">Document Tracker</h1>
            <p class="text-xs mt-0.5 opacity-50">Inbound imaging reports, lab results, and uploaded documents</p>
        </div>
    </div>

    {{-- Stats row --}}
    <div class="bg-slate-50 border-b border-slate-200 px-6 py-3 flex-shrink-0">
        <div class="flex gap-6">
            <div class="text-center">
                <p class="text-xl font-bold text-blue-600">{{ $stats['total_today'] }}</p>
                <p class="text-xs text-slate-400">Received today</p>
            </div>
            <div class="text-center">
                <p class="text-xl font-bold {{ $stats['pending_review'] > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ $stats['pending_review'] }}</p>
                <p class="text-xs text-slate-400">Pending review</p>
            </div>
            <div class="text-center">
                <p class="text-xl font-bold {{ $stats['critical'] > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $stats['critical'] }}</p>
                <p class="text-xs text-slate-400">Critical</p>
            </div>
            <div class="text-center">
                <p class="text-xl font-bold text-emerald-600">{{ $stats['reviewed_today'] }}</p>
                <p class="text-xs text-slate-400">Reviewed today</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-slate-50 border-b border-slate-200 px-6 py-3 flex-shrink-0">
        <div class="flex flex-wrap items-center gap-3">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Patient name, description…"
                   class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 w-52 focus:border-blue-500 focus:bg-white focus:outline-none transition" />

            <select wire:model.live="typeFilter" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none">
                <option value="">All Types</option>
                @foreach(\App\Models\Tenant\DocumentInboundLog::TYPES as $val => $lbl)
                    <option value="{{ $val }}">{{ $lbl }}</option>
                @endforeach
            </select>

            <select wire:model.live="sourceFilter" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none">
                <option value="">All Sources</option>
                @foreach(\App\Models\Tenant\DocumentInboundLog::SOURCES as $val => $lbl)
                    <option value="{{ $val }}">{{ $lbl }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none">
                <option value="">All Statuses</option>
                <option value="received">Received</option>
                <option value="critical">Critical</option>
                <option value="reviewed">Reviewed</option>
                <option value="error">Error</option>
            </select>

            <select wire:model.live="branchFilter" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>

            <input wire:model.live="dateFrom" type="date"
                   class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none" />
            <input wire:model.live="dateTo" type="date"
                   class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none" />

            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                <input type="checkbox" wire:model.live="criticalOnly" class="rounded text-red-600" />
                Critical only
            </label>
        </div>
    </div>

    {{-- Alert --}}
    @if($successMessage)
        <div class="mx-6 mt-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800 flex justify-between flex-shrink-0"
             x-data x-init="setTimeout(() => $el.remove(), 3000)">
            {{ $successMessage }}
            <button wire:click="$set('successMessage','')" class="text-xs text-emerald-500">✕</button>
        </div>
    @endif

    {{-- Table --}}
    <div class="flex-1 overflow-y-auto px-6 py-4">
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Arrived</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Source</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Reviewed by</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($items as $item)
                        @php
                            $statusChip = match($item->status) {
                                'critical' => 'bg-red-100 text-red-700',
                                'reviewed' => 'bg-emerald-100 text-emerald-700',
                                'error'    => 'bg-orange-100 text-orange-700',
                                default    => 'bg-amber-100 text-amber-700',
                            };
                            $typeChip = match($item->type) {
                                'imaging_report'   => 'bg-indigo-50 text-indigo-700',
                                'lab_result'       => 'bg-purple-50 text-purple-700',
                                'patient_document' => 'bg-blue-50 text-blue-700',
                                default            => 'bg-slate-50 text-slate-600',
                            };
                        @endphp
                        <tr class="{{ $item->is_critical ? 'bg-red-50' : 'hover:bg-slate-50' }} transition">
                            <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
                                {{ $item->created_at?->format('d M Y') }}
                                <p class="text-slate-400">{{ $item->created_at?->format('H:i') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $typeChip }}">
                                    {{ $item->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-slate-600 bg-slate-100 px-2 py-0.5 rounded">{{ $item->source_label }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($item->patient)
                                    <a href="{{ route('tenant.patients.show', $item->patient_id) }}"
                                       class="text-sm font-medium text-slate-800 hover:text-blue-700">
                                        {{ $item->patient->display_name }}
                                    </a>
                                    <p class="text-xs text-slate-400">{{ $item->patient->patient_number }}</p>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 max-w-xs">
                                {{ $item->description ?? '—' }}
                                @if($item->is_critical && $item->critical_note)
                                    <p class="text-xs text-red-600 mt-0.5">⚠ {{ $item->critical_note }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusChip }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                @if($item->reviewedBy)
                                    {{ $item->reviewedBy->name }}
                                    <p class="text-slate-400">{{ $item->reviewed_at?->format('d M H:i') }}</p>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($item->status !== 'reviewed')
                                    <button wire:click="markReviewed({{ $item->id }})"
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 whitespace-nowrap">
                                        Mark Reviewed
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-sm text-slate-400 italic">
                                No inbound documents match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($items->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
