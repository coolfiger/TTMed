<div>
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    <div class="flex justify-end mb-6">
        <a href="{{ route('tenant.branches.create') }}"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
            New Branch
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Branch</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">City</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Staff</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($branches as $branch)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">
                        {{ $branch->name }}
                        @if($branch->is_main)
                            <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-blue-50 text-blue-700">Main</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $branch->city ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $branch->phone ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $branch->staff_count }}</td>
                    <td class="px-6 py-3">
                        <button wire:click="toggleActive({{ $branch->id }})"
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium cursor-pointer
                                {{ $branch->is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                            {{ $branch->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('tenant.branches.edit', $branch->id) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">No branches yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @error('toggle')
        <div class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
    @enderror
</div>
