<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reception Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">{{ today()->format('l, d F Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="branchId" wire:change="switchBranch($event.target.value)"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                @foreach($branches as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
            @if(auth()->guard('tenant')->user()?->hasPermission('queue.checkin'))
                <button wire:click="$set('showCheckinForm', true)"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    + Check In Patient
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4 border border-green-200">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Check-in form --}}
    @if($showCheckinForm)
        <div class="bg-white rounded-xl shadow border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-semibold text-gray-900">Check In Patient</h2>
                <button wire:click="cancelCheckin" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>

            {{-- Patient search --}}
            @if(!$selectedPatientId)
                <div class="relative mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Patient</label>
                    <input wire:model.live.debounce.300ms="patientSearch"
                           type="text"
                           placeholder="Name, patient number, phone..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />

                    @if(count($searchResults) > 0)
                        <div class="absolute z-20 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200">
                            @foreach($searchResults as $result)
                                <button wire:click="selectPatient({{ $result['id'] }})"
                                        class="w-full text-left px-4 py-3 hover:bg-indigo-50 border-b border-gray-100 last:border-b-0">
                                    <span class="font-medium text-gray-900 text-sm">{{ $result['display_name'] }}</span>
                                    <span class="ml-2 text-xs text-gray-500">{{ $result['patient_number'] }}</span>
                                    @if($result['dob'])
                                        <span class="ml-2 text-xs text-gray-400">DOB: {{ $result['dob'] }}</span>
                                    @endif
                                    @if($result['phone'])
                                        <span class="ml-2 text-xs text-gray-400">{{ $result['phone'] }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if(strlen($patientSearch) >= 2 && count($searchResults) === 0)
                        <p class="mt-1 text-xs text-gray-500">No patients found.</p>
                    @endif
                </div>
            @else
                <div class="mb-4 flex items-center gap-3 bg-indigo-50 rounded-md p-3">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-indigo-900">{{ $selectedPatientName }}</p>
                    </div>
                    <button wire:click="$set('selectedPatientId', 0)" class="text-xs text-indigo-600 hover:underline">Change</button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Visit Type</label>
                        <select wire:model="visitType"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="walk_in">Walk-in</option>
                            <option value="appointment">Appointment</option>
                            <option value="follow_up">Follow-up</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assign Doctor <span class="text-gray-400 font-normal">(optional)</span></label>
                        <select wire:model="selectedDoctorId"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="0">— Unassigned —</option>
                            @foreach($activeDoctors as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->staffProfile?->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chief Complaint <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input wire:model="chiefComplaint" type="text"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                               placeholder="Reason for visit" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model.live="isPriority" type="checkbox"
                                   class="rounded border-gray-300 text-red-600 focus:ring-red-500" />
                            <span class="text-sm font-medium text-gray-700">Mark as Priority</span>
                        </label>
                    </div>

                    @if($isPriority)
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority Reason</label>
                            <input wire:model="priorityReason" type="text"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500 text-sm"
                                   placeholder="e.g. Chest pain, elderly patient" />
                        </div>
                    @endif
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button wire:click="cancelCheckin"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="checkIn" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="checkIn">Check In</span>
                        <span wire:loading wire:target="checkIn">Processing...</span>
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Main grid: doctor panel + queue --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Doctor status panel --}}
        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Doctors On Duty</h2>
            @forelse($doctorStatuses as $doc)
                <div class="mb-4 pb-4 border-b border-gray-100 last:border-b-0 last:mb-0 last:pb-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $doc['name'] }}</p>
                            @if($doc['specialty'])
                                <p class="text-xs text-gray-500">{{ $doc['specialty'] }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            {{ $doc['status'] === 'available'    ? 'bg-green-100 text-green-800' : '' }}
                            {{ $doc['status'] === 'busy'         ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $doc['status'] === 'on_break'     ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ in_array($doc['status'], ['off_duty', 'not_accepting']) ? 'bg-gray-100 text-gray-700' : '' }}">
                            {{ $doc['statusLabel'] }}
                        </span>
                    </div>
                    <div class="mt-1 flex items-center gap-3">
                        <span class="text-xs text-gray-500">{{ $doc['queueCount'] }} in queue</span>
                        @if($doc['statusNote'])
                            <span class="text-xs text-gray-400 italic truncate">{{ $doc['statusNote'] }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 italic">No doctors currently assigned to this branch.</p>
            @endforelse
        </div>

        {{-- Today's queue --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">
                    Today's Queue
                    <span class="ml-2 text-indigo-600 font-bold">{{ count($queueEntries) }}</span>
                </h2>
                <button wire:click="loadQueue" class="text-xs text-indigo-500 hover:underline">Refresh</button>
            </div>

            @if(count($queueEntries) === 0)
                <div class="py-12 text-center">
                    <p class="text-sm text-gray-400">No patients in queue.</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($queueEntries as $entry)
                        <div class="px-5 py-3 flex items-center gap-4">
                            <div class="flex-shrink-0 w-10 text-center">
                                <span class="text-lg font-bold {{ $entry['isPriority'] ? 'text-red-600' : 'text-gray-800' }}">
                                    {{ $entry['number'] }}
                                </span>
                                @if($entry['isPriority'])
                                    <div class="text-xs text-red-500 leading-tight">PRIORITY</div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $entry['patientName'] }}</p>
                                <p class="text-xs text-gray-500">{{ $entry['doctorName'] }} &middot; {{ ucfirst(str_replace('_', ' ', $entry['visitType'])) }}</p>
                            </div>
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $entry['status'] === 'waiting'          ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $entry['status'] === 'with_nurse'       ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $entry['status'] === 'awaiting_doctor'  ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $entry['status'] === 'with_doctor'      ? 'bg-green-100 text-green-800' : '' }}">
                                {{ $entry['statusLabel'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Real-time update via Echo --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            @if(app('App\Tenancy\TenantContext')->get())
                const tenantUuid = "{{ app('App\Tenancy\TenantContext')->get()->uuid }}";
                const branchId   = {{ $branchId }};
                const channel    = `tenant.${tenantUuid}.branch.${branchId}.queue`;

                if (window.Echo) {
                    window.Echo.channel(channel).listen('.queue.updated', () => {
                        @this.loadQueue();
                        @this.loadDoctorStatuses();
                    });
                }
            @endif
        });
    </script>
</div>
