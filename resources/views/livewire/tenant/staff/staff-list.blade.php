<div>
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    <div class="flex gap-3 mb-6 justify-between items-end">
        <div class="flex gap-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search staff…"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm w-64 focus:border-blue-500 focus:outline-none">
            <select wire:model.live="branchFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('tenant.staff.create') }}"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
            New Staff Member
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Branch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($staff as $member)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">{{ $member->full_name }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $member->branch?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">
                        @foreach($member->user->roles as $role)
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs bg-slate-100 text-slate-700">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-600">
                        @if($member->doctor)
                            <span class="text-xs text-blue-600">{{ $member->doctor->specialty ?? 'Doctor' }}</span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        @if($member->deleted_at)
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs bg-red-50 text-red-600">Deactivated</span>
                        @else
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs bg-green-50 text-green-700">Active</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('tenant.staff.edit', $member->id) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">No staff found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $staff->links() }}
        </div>
    </div>
</div>
