<div class="flex flex-col h-full min-h-0 bg-slate-100">

    {{-- Header --}}
    <div class="page-topbar">
        <div>
            <h1 class="text-lg font-bold">Imaging Workqueue</h1>
            <p class="text-xs mt-0.5 opacity-50">All imaging orders and reports across the practice</p>
        </div>
        <div class="flex items-center gap-3">
            @if($criticalCount > 0)
                <span class="inline-flex items-center gap-1.5 bg-red-500/20 text-red-300 text-xs font-bold px-3 py-1.5 rounded-full ring-1 ring-red-500/40 animate-pulse">
                    ⚠ {{ $criticalCount }} Critical
                </span>
            @endif
            @if($pendingReviewCount > 0)
                <span class="inline-flex items-center gap-1.5 bg-amber-500/20 text-amber-300 text-xs font-semibold px-3 py-1.5 rounded-full ring-1 ring-amber-500/40">
                    {{ $pendingReviewCount }} Pending Review
                </span>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-slate-50 border-b border-slate-200 px-6 py-3 flex-shrink-0">
        <div class="flex flex-wrap items-center gap-3">
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="Patient name, order #, accession…"
                   class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 w-56 focus:border-blue-500 focus:bg-white focus:outline-none transition" />

            <select wire:model.live="statusFilter" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none">
                <option value="">All Statuses</option>
                @foreach($statuses as $val => $lbl)
                    <option value="{{ $val }}">{{ $lbl }}</option>
                @endforeach
            </select>

            <select wire:model.live="modalityFilter" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none">
                <option value="">All Modalities</option>
                @foreach(\App\Models\Tenant\ImagingOrder::MODALITIES as $code => $lbl)
                    <option value="{{ $code }}">{{ $lbl }}</option>
                @endforeach
            </select>

            <select wire:model.live="sourceFilter" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-slate-50 focus:outline-none">
                <option value="">All Sources</option>
                <option value="hl7_v2">HL7 v2</option>
                <option value="vepro">VEPRO</option>
                <option value="manual">Manual</option>
                <option value="patient_provided">Patient-Provided</option>
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

    {{-- Alerts --}}
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Patient</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Order #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Modality / Area</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Ordered</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Source</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Report</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($orders as $order)
                        @php
                            $report    = $order->report;
                            $critical  = $report?->is_critical;
                            $statusColors = [
                                'pending'           => 'bg-slate-100 text-slate-600',
                                'sent_to_ris'       => 'bg-blue-100 text-blue-700',
                                'scheduled'         => 'bg-indigo-100 text-indigo-700',
                                'in_progress'       => 'bg-amber-100 text-amber-700',
                                'report_available'  => 'bg-emerald-100 text-emerald-700',
                                'report_preliminary'=> 'bg-orange-100 text-orange-700',
                                'report_final'      => 'bg-teal-100 text-teal-700',
                                'report_reviewed'   => 'bg-slate-100 text-slate-500',
                                'cancelled'         => 'bg-red-50 text-red-400',
                            ];
                            $statusClass = $statusColors[$order->status] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr class="{{ $critical ? 'bg-red-50' : 'hover:bg-slate-50' }} transition">
                            <td class="px-4 py-3">
                                <a href="{{ route('tenant.patients.show', $order->patient_id) }}"
                                   class="font-semibold text-slate-800 hover:text-blue-700">
                                    {{ $order->patient?->display_name }}
                                </a>
                                @if($critical)
                                    <span class="ml-1 text-xs text-red-600 font-bold">⚠ CRITICAL</span>
                                @endif
                                <p class="text-xs text-slate-400">{{ $order->patient?->patient_number }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">
                                {{ $order->order_number }}
                                @if($order->accession_number)
                                    <p class="text-slate-400">{{ $order->accession_number }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{ $order->modality }}</span>
                                <span class="ml-1.5 text-slate-700">{{ $order->body_part }}</span>
                                @if($order->laterality)
                                    <span class="text-xs text-slate-400 ml-1">{{ $order->laterality }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">
                                {{ $order->ordered_at?->format('d M Y') }}
                                <p class="text-slate-400">{{ $order->ordered_at?->format('H:i') }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($report)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                        {{ ucfirst(str_replace('_', ' ', $report->source ?? '—')) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusClass }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($report)
                                    <div class="text-xs space-y-0.5">
                                        @if($report->radiologist_name)
                                            <p class="text-slate-500">{{ $report->radiologist_name }}</p>
                                        @endif
                                        @if($report->impression)
                                            <p class="text-slate-600 line-clamp-2">{{ $report->impression }}</p>
                                        @endif
                                        @if($report->report_pdf_path)
                                            <a href="{{ route('tenant.imaging.report.view', $report->id) }}" target="_blank"
                                               class="text-blue-600 hover:text-blue-800 font-semibold">View PDF →</a>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-300">No report yet</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($report && !in_array($order->status, ['report_reviewed', 'cancelled']))
                                    <button wire:click="markReviewed({{ $order->id }})"
                                            wire:confirm="Mark this imaging order as reviewed?"
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 mr-3">
                                        Mark Reviewed
                                    </button>
                                @endif
                                @if($report)
                                    @if(!$critical)
                                        <button wire:click="flagCritical({{ $report->id }}, true)"
                                                class="text-xs text-red-500 hover:text-red-700">Flag Critical</button>
                                    @else
                                        <button wire:click="flagCritical({{ $report->id }}, false)"
                                                class="text-xs text-slate-400 hover:text-slate-600">Clear Flag</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-sm text-slate-400 italic">
                                No imaging orders match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($orders->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
