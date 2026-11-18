<div class="max-w-4xl mx-auto py-6 space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('tenant.appointments.calendar') }}"
           class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
        <h1 class="text-2xl font-bold text-gray-900">Schedule — Dr. {{ $doctorName }}</h1>
    </div>

    @if($successMessage)
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-green-800 text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-red-800 text-sm">{{ $errorMessage }}</div>
    @endif

    {{-- Weekly schedule --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Weekly Schedule</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($days as $dow => $day)
            <div class="px-5 py-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="w-28">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   wire:model="days.{{ $dow }}.is_working_day"
                                   class="rounded border-gray-300 text-blue-600">
                            <span class="text-sm font-medium text-gray-800">{{ $day['name'] }}</span>
                        </label>
                    </div>

                    @if($days[$dow]['is_working_day'])
                    <div class="flex items-center gap-2 flex-wrap">
                        <input type="time" wire:model="days.{{ $dow }}.start_time"
                               class="border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                        <span class="text-gray-400 text-sm">to</span>
                        <input type="time" wire:model="days.{{ $dow }}.end_time"
                               class="border border-gray-300 rounded-md px-2 py-1.5 text-sm">

                        <div class="flex items-center gap-1">
                            <input type="number" wire:model="days.{{ $dow }}.slot_duration_minutes"
                                   min="5" max="120" step="5"
                                   class="border border-gray-300 rounded-md px-2 py-1.5 text-sm w-16">
                            <span class="text-xs text-gray-500">min/slot</span>
                        </div>

                        <div class="flex items-center gap-1">
                            <input type="number" wire:model="days.{{ $dow }}.max_patients_per_slot"
                                   min="1" max="10"
                                   class="border border-gray-300 rounded-md px-2 py-1.5 text-sm w-14">
                            <span class="text-xs text-gray-500">per slot</span>
                        </div>

                        <div class="flex items-center gap-1">
                            <input type="number" wire:model="days.{{ $dow }}.max_patients_per_day"
                                   min="1" max="200" placeholder="∞"
                                   class="border border-gray-300 rounded-md px-2 py-1.5 text-sm w-16">
                            <span class="text-xs text-gray-500">max/day</span>
                        </div>
                    </div>
                    @else
                    <span class="text-sm text-gray-400 italic">Not working</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button wire:click="saveSchedule"
                    class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                Save Schedule
            </button>
        </div>
    </div>

    {{-- Unavailability --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Unavailability</h2>
            <button wire:click="$toggle('showUnavailForm')"
                    class="text-sm text-blue-600 hover:text-blue-800">+ Add</button>
        </div>

        @if($showUnavailForm)
        <div class="px-5 py-4 border-b border-gray-100 bg-blue-50">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                    <input type="date" wire:model="unavailDate"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 mt-5">
                        <input type="checkbox" wire:model.live="unavailWholeDay" class="rounded border-gray-300">
                        Whole day
                    </label>
                </div>
                @if(!$unavailWholeDay)
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
                    <input type="time" wire:model="unavailFromTime"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                    <input type="time" wire:model="unavailToTime"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
                @endif
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Reason (optional)</label>
                    <input type="text" wire:model="unavailReason" maxlength="250"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                </div>
            </div>
            <div class="mt-3 flex justify-end gap-2">
                <button wire:click="$set('showUnavailForm', false)"
                        class="px-4 py-2 text-sm text-gray-600">Cancel</button>
                <button wire:click="addUnavailability"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Add
                </button>
            </div>
        </div>
        @endif

        @if($upcoming->isEmpty())
            <div class="p-6 text-center text-gray-400 text-sm">No upcoming unavailability.</div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($upcoming as $u)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-gray-800">{{ $u->unavailable_date->format('d M Y') }}</span>
                    @if($u->from_time)
                        <span class="text-xs text-gray-500 ml-2">{{ $u->from_time }} – {{ $u->to_time }}</span>
                    @else
                        <span class="text-xs text-gray-500 ml-2">All day</span>
                    @endif
                    @if($u->reason)
                        <span class="text-xs text-gray-400 ml-2">— {{ $u->reason }}</span>
                    @endif
                </div>
                <button wire:click="removeUnavailability({{ $u->id }})"
                        wire:confirm="Remove this unavailability?"
                        class="text-xs text-red-600 hover:text-red-800">Remove</button>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
