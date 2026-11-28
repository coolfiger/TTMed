<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Observation Rooms</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage rooms assigned to patients during observation.</p>
        </div>
        @if(!$showForm)
        <button wire:click="openCreate"
                class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Room
        </button>
        @endif
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="rounded-xl bg-teal-50 border border-teal-200 px-4 py-3 text-sm text-teal-800">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        {{ session('error') }}
    </div>
    @endif

    {{-- Branch selector --}}
    @if(count($branches) > 1)
    <div class="flex items-center gap-3">
        <label class="text-sm font-medium text-slate-700">Branch</label>
        <select wire:model.live="branchId"
                class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none bg-white">
            @foreach($branches as $branch)
                <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Create / Edit form --}}
    @if($showForm)
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-4">
        <h2 class="text-base font-semibold text-slate-800">{{ $editingId ? 'Edit Room' : 'New Room' }}</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">Room name <span class="text-red-500">*</span></label>
                <input type="text" wire:model="formName" placeholder="e.g. Obs Bay 1"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none @error('formName') border-red-400 @enderror">
                @error('formName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1.5">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="text" wire:model="formNotes" placeholder="e.g. Near nurses' station"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none @error('formNotes') border-red-400 @enderror">
                @error('formNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" wire:model="formActive" id="formActive"
                   class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
            <label for="formActive" class="text-sm text-slate-700">Room is active (available for assignment)</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button wire:click="save"
                    class="rounded-xl bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors">
                {{ $editingId ? 'Save Changes' : 'Create Room' }}
            </button>
            <button wire:click="cancelForm"
                    class="rounded-xl border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                Cancel
            </button>
        </div>
    </div>
    @endif

    {{-- Rooms list --}}
    @if($rooms->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
        <p class="text-sm text-slate-500">No observation rooms set up for this branch yet.</p>
    </div>
    @else
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Room</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Notes</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Active</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rooms as $room)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $room->name }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $room->notes ?: '—' }}</td>
                    <td class="px-4 py-3">
                        @if($room->status === 'occupied')
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Occupied
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-800">
                                <span class="h-1.5 w-1.5 rounded-full bg-teal-500"></span>
                                Vacant
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($room->is_active)
                            <span class="text-teal-700 font-medium">Yes</span>
                        @else
                            <span class="text-slate-400">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($confirmDeleteId === $room->id)
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-red-600 font-medium">Delete?</span>
                            <button wire:click="delete"
                                    class="text-xs font-medium text-red-600 hover:text-red-800">Yes</button>
                            <button wire:click="cancelDelete"
                                    class="text-xs font-medium text-slate-500 hover:text-slate-700">No</button>
                        </div>
                        @else
                        <div class="flex items-center gap-3 justify-end">
                            <button wire:click="openEdit({{ $room->id }})"
                                    class="text-xs font-medium text-blue-600 hover:text-blue-800">Edit</button>
                            @if($room->status !== 'occupied')
                            <button wire:click="confirmDelete({{ $room->id }})"
                                    class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
