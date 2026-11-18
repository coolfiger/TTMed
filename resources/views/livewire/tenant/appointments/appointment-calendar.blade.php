<div class="max-w-6xl mx-auto py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Appointments</h1>
        <a href="{{ route('tenant.appointments.follow-ups') }}"
           class="text-sm text-blue-600 hover:underline">Follow-up Dashboard</a>
    </div>

    {{-- Alerts --}}
    @if($successMessage)
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-green-800 text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-red-800 text-sm">{{ $errorMessage }}</div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
            <input type="date" wire:model.live="selectedDate"
                   class="border border-gray-300 rounded-md px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Doctor</label>
            <select wire:model.live="selectedDoctor"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm min-w-[180px]">
                <option value="0">Select doctor</option>
                @foreach($doctors as $doc)
                    <option value="{{ $doc->id }}">Dr. {{ $doc->staffProfile?->full_name }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="loadSlots"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
            Load Schedule
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Slots panel --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700">Available Slots</h2>
                </div>
                @if(empty($slots))
                    <div class="p-6 text-center text-gray-400 text-sm">
                        {{ $selectedDoctor ? 'No slots available.' : 'Select a doctor to see slots.' }}
                    </div>
                @else
                <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                    @foreach($slots as $slot)
                    <div class="flex items-center justify-between px-4 py-2 {{ $slot['available'] ? 'hover:bg-blue-50' : 'opacity-50' }}">
                        <div>
                            <span class="text-sm font-mono font-medium text-gray-800">{{ $slot['time'] }}</span>
                            <span class="text-xs text-gray-400 ml-2">{{ $slot['booked'] }}/{{ $slot['capacity'] }}</span>
                        </div>
                        @if($slot['available'])
                            <button wire:click="selectSlot('{{ $slot['time'] }}')"
                                    class="text-xs font-medium text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-100">
                                Book
                            </button>
                        @else
                            <span class="text-xs text-gray-400">Full</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Appointments panel --}}
        <div class="lg:col-span-2 space-y-4">
            @if($appointments->isEmpty())
                <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-400 text-sm">
                    No appointments for this day.
                </div>
            @else
            @foreach($appointments as $appt)
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-sm font-semibold text-gray-700">{{ $appt->appointment_time }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                bg-{{ $appt->status_color }}-100 text-{{ $appt->status_color }}-800">
                                {{ $appt->status_label }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $appt->type_label }}</span>
                        </div>
                        <div class="mt-1 font-semibold text-gray-900">{{ $appt->patient?->full_name }}</div>
                        @if($appt->reason)
                            <div class="text-xs text-gray-500 mt-0.5">{{ $appt->reason }}</div>
                        @endif
                        <div class="text-xs text-gray-400 mt-0.5">{{ $appt->appointment_number }}</div>
                    </div>
                    <div class="flex flex-col gap-1 items-end shrink-0">
                        @if(in_array($appt->status, ['scheduled', 'confirmed']))
                            <button wire:click="addToQueue({{ $appt->id }})"
                                    class="text-xs font-medium text-green-700 hover:text-green-900 px-2 py-1 rounded hover:bg-green-50">
                                Add to Queue
                            </button>
                            <button wire:click="openRescheduleModal({{ $appt->id }})"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 px-2 py-1 rounded hover:bg-indigo-50">
                                Reschedule
                            </button>
                            <button wire:click="markNoShow({{ $appt->id }})"
                                    wire:confirm="Mark this patient as no-show?"
                                    class="text-xs font-medium text-gray-500 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-50">
                                No Show
                            </button>
                            <button wire:click="openCancelModal({{ $appt->id }})"
                                    class="text-xs font-medium text-red-600 hover:text-red-800 px-2 py-1 rounded hover:bg-red-50">
                                Cancel
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>

    {{-- Booking form modal --}}
    @if($showBookingForm)
    <div class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-40">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Book Appointment — {{ $bookingTime }}</h3>

            <div x-data="{ open: false }" x-on:click.outside="open = false">
                <label class="block text-xs font-medium text-gray-600 mb-1">Patient <span class="text-red-500">*</span></label>
                <input type="text"
                       wire:model.live.debounce.300ms="patientSearch"
                       wire:keyup="searchPatients"
                       x-on:focus="open = ($wire.patientResults.length > 0)"
                       x-on:input="open = true"
                       placeholder="Search patient…"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <div x-show="open && $wire.patientResults.length > 0"
                     x-cloak
                     class="mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto z-50">
                    @foreach($patientResults as $p)
                        <button wire:click="selectPatient({{ $p['id'] }})"
                                x-on:click="open = false"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 border-b border-gray-100 last:border-0">
                            <span class="font-medium">{{ $p['name'] }}</span>
                            <span class="text-xs text-gray-400 ml-2">{{ $p['dob'] }}</span>
                        </button>
                    @endforeach
                </div>
                @error('bookingPatientId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                <select wire:model="bookingType"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="scheduled">Scheduled</option>
                    <option value="walk_in">Walk-in</option>
                    <option value="follow_up">Follow-up</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Reason</label>
                <input type="text" wire:model="bookingReason" maxlength="500"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                <textarea wire:model="bookingNotes" rows="2" maxlength="1000"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button wire:click="$set('showBookingForm', false)"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button wire:click="book"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Confirm Booking
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Cancel modal --}}
    @if($showCancelModal)
    <div class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-40">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Cancel Appointment</h3>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Reason <span class="text-red-500">*</span></label>
                <textarea wire:model="cancelReason" rows="3"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></textarea>
                @error('cancelReason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2">
                <button wire:click="$set('showCancelModal', false)"
                        class="px-4 py-2 text-sm text-gray-600">Close</button>
                <button wire:click="cancelAppointment"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                    Cancel Appointment
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Reschedule modal --}}
    @if($showRescheduleModal)
    <div class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-40">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Reschedule Appointment</h3>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">New date <span class="text-red-500">*</span></label>
                <input type="date" wire:model="rescheduleDate" min="{{ now()->toDateString() }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                @error('rescheduleDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">New time <span class="text-red-500">*</span></label>
                <input type="time" wire:model="rescheduleTime"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                @error('rescheduleTime') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2">
                <button wire:click="$set('showRescheduleModal', false)"
                        class="px-4 py-2 text-sm text-gray-600">Close</button>
                <button wire:click="reschedule"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Reschedule
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
