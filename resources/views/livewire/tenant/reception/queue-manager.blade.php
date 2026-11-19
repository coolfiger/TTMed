<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Queue Manager</h1>
            <p class="text-sm text-gray-500 mt-1">{{ today()->format('l, d F Y') }}</p>
        </div>
        <select wire:model.live="branchId" class="rounded-md border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            @foreach($branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
    </div>

    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4 border border-green-200">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Doctor availability cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($doctors as $doc)
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <p class="text-sm font-semibold text-gray-900 leading-tight truncate">{{ $doc->name }}</p>
                    <span class="shrink-0 w-2.5 h-2.5 rounded-full mt-1
                        {{ $doc->statusColor === 'green'  ? 'bg-green-500'  : '' }}
                        {{ $doc->statusColor === 'yellow' ? 'bg-yellow-500' : '' }}
                        {{ $doc->statusColor === 'orange' ? 'bg-orange-500' : '' }}
                        {{ $doc->statusColor === 'gray'   ? 'bg-gray-400'   : '' }}
                        {{ $doc->statusColor === 'red'    ? 'bg-red-500'    : '' }}">
                    </span>
                </div>
                <p class="text-xs font-medium
                    {{ $doc->statusColor === 'green'  ? 'text-green-700'  : '' }}
                    {{ $doc->statusColor === 'yellow' ? 'text-yellow-700' : '' }}
                    {{ $doc->statusColor === 'orange' ? 'text-orange-700' : '' }}
                    {{ $doc->statusColor === 'gray'   ? 'text-gray-500'   : '' }}
                    {{ $doc->statusColor === 'red'    ? 'text-red-700'    : '' }}">
                    {{ $doc->statusLabel }}
                </p>
                @if($doc->statusNote)
                    <p class="text-xs text-gray-400 mt-1 italic truncate">{{ $doc->statusNote }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">{{ $doc->queueCount }} waiting</p>
                @if(auth()->guard('tenant')->user()?->hasPermission('queue.manage'))
                    <button wire:click="openAvailForm({{ $doc->id }}, '{{ $doc->status }}')"
                            class="mt-2 text-xs text-indigo-600 hover:underline">
                        Update status
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 items-center">
        <select wire:model.live="filterStatus" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">All Statuses</option>
            <option value="waiting">Waiting</option>
            <option value="with_nurse">With Nurse</option>
            <option value="awaiting_doctor">Awaiting Doctor</option>
            <option value="with_doctor">With Doctor</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="no_show">No Show</option>
        </select>
        <select wire:model.live="filterDoctorId" class="rounded-md border-gray-300 text-sm shadow-sm">
            <option value="0">All Doctors</option>
            @foreach($transferDoctors as $doc)
                <option value="{{ $doc->id }}">{{ $doc->staffProfile?->full_name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Queue table --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    @if(auth()->guard('tenant')->user()?->hasPermission('queue.manage'))
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($entries as $entry)
                    <tr class="{{ $entry->is_priority ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3 text-sm font-bold {{ $entry->is_priority ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $entry->queue_number }}
                            @if($entry->is_priority)
                                <div class="text-xs font-normal text-red-500">PRIORITY</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $entry->patient?->display_name }}</p>
                            <p class="text-xs text-gray-500">{{ $entry->patient?->patient_number }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $entry->doctor?->staffProfile?->full_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ ucfirst(str_replace('_', ' ', $entry->visit_type)) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $entry->status === 'waiting'          ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $entry->status === 'with_nurse'       ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $entry->status === 'awaiting_doctor'  ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $entry->status === 'with_doctor'      ? 'bg-green-100 text-green-800' : '' }}
                                {{ $entry->status === 'completed'        ? 'bg-gray-100 text-gray-700' : '' }}
                                {{ in_array($entry->status, ['cancelled','no_show']) ? 'bg-red-100 text-red-700' : '' }}">
                                {{ $entry->status_label }}
                            </span>
                        </td>
                        @if(auth()->guard('tenant')->user()?->hasPermission('queue.manage'))
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    {{-- Next status transitions --}}
                                    @foreach(\App\Models\Tenant\QueueEntry::VALID_TRANSITIONS[$entry->status] ?? [] as $next)
                                        @if(!in_array($next, ['cancelled', 'no_show']))
                                            <button wire:click="advance({{ $entry->id }}, '{{ $next }}')"
                                                    class="text-xs px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                                                → {{ ucfirst(str_replace('_', ' ', $next)) }}
                                            </button>
                                        @endif
                                    @endforeach

                                    {{-- Transfer --}}
                                    @if(!in_array($entry->status, ['completed', 'cancelled', 'no_show']))
                                        <button wire:click="openTransfer({{ $entry->id }})"
                                                class="text-xs px-2 py-1 rounded bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                                            Transfer
                                        </button>
                                        <button wire:click="openPriorityForm({{ $entry->id }}, {{ $entry->is_priority ? 'true' : 'false' }})"
                                                class="text-xs px-2 py-1 rounded {{ $entry->is_priority ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                            {{ $entry->is_priority ? 'Remove Priority' : 'Priority' }}
                                        </button>
                                        <button wire:click="markNoShow({{ $entry->id }})"
                                                onclick="return confirm('Mark as no-show?')"
                                                class="text-xs px-2 py-1 rounded bg-white border border-gray-300 text-gray-500 hover:bg-gray-50">
                                            No Show
                                        </button>
                                        <button wire:click="cancel({{ $entry->id }})"
                                                onclick="return confirm('Cancel this entry?')"
                                                class="text-xs px-2 py-1 rounded bg-white border border-red-300 text-red-600 hover:bg-red-50">
                                            Cancel
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400 italic">
                            No queue entries for today.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Transfer modal --}}
    @if($showTransferForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transfer Patient to Doctor</h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Doctor</label>
                    <select wire:model="transferDoctorId" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="0">— Select Doctor —</option>
                        @foreach($transferDoctors as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->staffProfile?->full_name }}</option>
                        @endforeach
                    </select>
                    @error('transferDoctorId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelTransfer"
                            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="confirmTransfer"
                            class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Transfer
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Priority modal --}}
    @if($showPriorityForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ $priorityValue ? 'Mark as Priority' : 'Remove Priority' }}
                </h3>
                @if($priorityValue)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                        <input wire:model="priorityReason" type="text"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                               placeholder="e.g. Chest pain, elderly" />
                    </div>
                @endif
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelPriorityForm"
                            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="savePriority"
                            class="px-4 py-2 text-sm text-white {{ $priorityValue ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-600 hover:bg-gray-700' }} rounded-md">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Doctor availability modal --}}
    @if($showAvailForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Doctor Status</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select wire:model="availStatus" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="available">Available</option>
                            <option value="busy">Busy</option>
                            <option value="on_break">On Break</option>
                            <option value="not_accepting">Not Accepting</option>
                            <option value="off_duty">Off Duty</option>
                        </select>
                        @error('availStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input wire:model="availNote" type="text"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                               placeholder="e.g. Back at 2pm" />
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-5">
                    <button wire:click="cancelAvailForm"
                            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="saveAvailability"
                            class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Real-time update --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            @if(app('App\Tenancy\TenantContext')->get())
                const tenantUuid = "{{ app('App\Tenancy\TenantContext')->get()->uuid }}";
                const branchId   = {{ $branchId }};
                const channel    = `tenant.${tenantUuid}.branch.${branchId}.queue`;

                if (window.Echo) {
                    window.Echo.channel(channel).listen('.queue.updated', () => {
                        @this.$refresh();
                    });
                }
            @endif
        });
    </script>
</div>
