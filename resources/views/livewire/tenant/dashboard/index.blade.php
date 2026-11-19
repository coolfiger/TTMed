<div class="space-y-6">

    {{-- Stat tiles --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Patients Today</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $patientsToday }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active Queue</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $queueActive }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Appointments Today</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">{{ $appointmentsToday }}</p>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Revenue Today</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900">TTD ${{ number_format($revenueToday / 100, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Live queue --}}
        <div class="bg-white rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Live Queue</h3>
                <a href="{{ route('tenant.reception.dashboard') }}" class="text-xs text-blue-600 hover:underline">Manage →</a>
            </div>
            @if($queueEntries->isEmpty())
                <p class="px-5 py-8 text-sm text-slate-400 text-center">No active patients in queue.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($queueEntries as $entry)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center flex-shrink-0">
                            {{ $entry->queue_number }}
                        </span>
                        @if($entry->is_priority)
                            <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0" title="Priority"></span>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $entry->patient?->full_name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $entry->doctor?->staffProfile?->full_name ?? 'Unassigned' }}</p>
                        </div>
                        @php
                            $badgeClass = match($entry->status) {
                                'waiting'          => 'bg-slate-100 text-slate-600',
                                'with_nurse'       => 'bg-yellow-100 text-yellow-700',
                                'awaiting_doctor'  => 'bg-blue-100 text-blue-700',
                                'with_doctor'      => 'bg-green-100 text-green-700',
                                default            => 'bg-slate-100 text-slate-500',
                            };
                            $badgeLabel = match($entry->status) {
                                'waiting'          => 'Waiting',
                                'with_nurse'       => 'With Nurse',
                                'awaiting_doctor'  => 'Awaiting Doctor',
                                'with_doctor'      => 'With Doctor',
                                default            => ucfirst($entry->status),
                            };
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Today's appointments --}}
        <div class="bg-white rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Today's Appointments</h3>
                <a href="{{ route('tenant.appointments.calendar') }}" class="text-xs text-blue-600 hover:underline">View all →</a>
            </div>
            @if($upcomingAppointments->isEmpty())
                <p class="px-5 py-8 text-sm text-slate-400 text-center">No appointments scheduled for today.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($upcomingAppointments as $appt)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="text-xs font-mono text-slate-500 w-12 flex-shrink-0">
                            {{ \Carbon\Carbon::parse($appt->appointment_time)->format('H:i') }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $appt->patient?->full_name ?? '—' }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $appt->doctor?->staffProfile?->full_name ?? '—' }} · {{ $appt->reason }}</p>
                        </div>
                        @php
                            $apptBadge = match($appt->appointment_type) {
                                'follow_up'  => 'bg-purple-100 text-purple-700',
                                'urgent'     => 'bg-red-100 text-red-700',
                                'walk_in'    => 'bg-slate-100 text-slate-600',
                                default      => 'bg-blue-100 text-blue-700',
                            };
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $apptBadge }} capitalize">
                            {{ str_replace('_', ' ', $appt->appointment_type) }}
                        </span>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
</div>
