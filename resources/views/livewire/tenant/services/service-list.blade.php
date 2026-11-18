<div>
    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-200">{{ session('message') }}</div>
    @endif

    <div class="flex gap-3 mb-6 justify-between items-end">
        <div class="flex gap-3">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search services…"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm w-64 focus:border-blue-500 focus:outline-none">
            <select wire:model.live="categoryFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <a href="{{ route('tenant.services.create') }}"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
            New Service
        </a>
    </div>

    <div class="bg-white rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Service</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($services as $service)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">
                        {{ $service->name }}
                        @if($service->is_default)
                            <span class="ml-1 text-xs text-slate-400">default</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $service->code }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $service->category?->name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-right text-slate-800 font-medium">${{ $service->price_formatted }}</td>
                    <td class="px-6 py-3">
                        <button wire:click="toggleActive({{ $service->id }})"
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium cursor-pointer
                                {{ $service->is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                            {{ $service->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('tenant.services.edit', $service->id) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">No services found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $services->links() }}
        </div>
    </div>
</div>
