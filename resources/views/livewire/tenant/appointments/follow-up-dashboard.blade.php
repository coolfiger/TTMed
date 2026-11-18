<div class="max-w-5xl mx-auto py-6 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Follow-ups</h1>
            <p class="text-sm text-gray-500">Pending follow-up appointments for your patients</p>
        </div>
        <a href="{{ route('tenant.appointments.calendar') }}"
           class="text-sm text-blue-600 hover:underline">&larr; Appointment Calendar</a>
    </div>

    @if($successMessage)
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-green-800 text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-red-800 text-sm">{{ $errorMessage }}</div>
    @endif

    {{-- Filters --}}
    <div class="flex gap-3 items-center flex-wrap">
        <select wire:model.live="filterStatus"
                class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="booked">Booked</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    {{-- Follow-up list --}}
    @if($followUps->isEmpty())
        <div class="text-center py-12 text-gray-400 text-sm">No follow-ups found.</div>
    @else
    <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
        @foreach($followUps as $fu)
        <div class="px-5 py-4 flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-900 text-sm">{{ $fu->patient?->full_name }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        bg-{{ $fu->status_color }}-100 text-{{ $fu->status_color }}-800">
                        {{ ucfirst($fu->status) }}
                    </span>
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                    Dr. {{ $fu->doctor?->staffProfile?->full_name }}
                    @if($fu->target_date)
                        &middot; Target: {{ $fu->target_date->format('d M Y') }}
                        @if($fu->isPending() && $fu->target_date->isPast())
                            <span class="text-red-600 font-medium ml-1">overdue</span>
                        @endif
                    @elseif($fu->follow_up_days)
                        &middot; In {{ $fu->follow_up_days }} days
                    @endif
                </div>
                @if($fu->reason)
                    <div class="text-xs text-gray-500 mt-0.5">{{ $fu->reason }}</div>
                @endif
                @if($fu->isBooked() && $fu->appointment)
                    <div class="text-xs text-blue-600 mt-0.5">
                        Booked: {{ $fu->appointment->appointment_date->format('d M Y') }}
                        at {{ $fu->appointment->appointment_time }}
                        ({{ $fu->appointment->appointment_number }})
                    </div>
                @endif
            </div>
            @if($fu->isPending())
            <button wire:click="openBookForm({{ $fu->id }})"
                    class="shrink-0 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700">
                Book Appointment
            </button>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Book slot modal --}}
    @if($showBookForm)
    <div class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-40">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Book Follow-up Slot</h3>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                <input type="date" wire:model.live="bookDate" min="{{ now()->toDateString() }}"
                       wire:change="loadSlots"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>

            @if(!empty($availableSlots))
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Time slot</label>
                <div class="grid grid-cols-4 gap-2 max-h-40 overflow-y-auto">
                    @foreach($availableSlots as $slot)
                        @if($slot['available'])
                        <button wire:click="$set('bookTime', '{{ $slot['time'] }}')"
                                class="text-xs font-mono px-2 py-1.5 rounded border
                                    {{ $bookTime === $slot['time'] ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:border-blue-400' }}">
                            {{ $slot['time'] }}
                        </button>
                        @endif
                    @endforeach
                </div>
                @error('bookTime') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @elseif($bookDate)
            <p class="text-sm text-gray-400">No available slots on this date.</p>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <button wire:click="$set('showBookForm', false)"
                        class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                <button wire:click="bookFollowUp"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
