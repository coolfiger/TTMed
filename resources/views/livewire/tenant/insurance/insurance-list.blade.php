<div>
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    <div class="flex gap-3 mb-6 justify-between items-end">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search providers…"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm w-64 focus:border-blue-500 focus:outline-none">
        <a href="{{ route('tenant.insurance.create') }}"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
            New Provider
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Provider</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($providers as $provider)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">{{ $provider->name }}</td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $provider->code }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $provider->contact_name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $provider->phone ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <button wire:click="toggleActive({{ $provider->id }})"
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium cursor-pointer
                                {{ $provider->is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                            {{ $provider->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('tenant.insurance.edit', $provider->id) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">No insurance providers found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $providers->links() }}
        </div>
    </div>
</div>
