<div class="max-w-3xl space-y-6">
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $tenant->name }}</h2>
                <p class="text-sm text-slate-500 font-mono mt-0.5">{{ $tenant->code }}.ttmed.app</p>
            </div>
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                {{ match($tenant->status) {
                    'active'    => 'bg-green-50 text-green-700 ring-1 ring-green-200',
                    'trial'     => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
                    'suspended' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                    default     => 'bg-slate-100 text-slate-600',
                } }}">
                {{ ucfirst($tenant->status) }}
            </span>
        </div>

        <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
            <div>
                <dt class="text-slate-500">UUID</dt>
                <dd class="font-mono text-slate-700 mt-0.5">{{ $tenant->uuid }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Database</dt>
                <dd class="font-mono text-slate-700 mt-0.5">{{ $tenant->database_name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Custom Domain</dt>
                <dd class="text-slate-700 mt-0.5">{{ $tenant->custom_domain ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Created</dt>
                <dd class="text-slate-700 mt-0.5">{{ $tenant->created_at->format('d M Y, g:ia') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Plan</dt>
                <dd class="text-slate-700 mt-0.5">{{ $tenant->subscription?->plan?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Subscription expires</dt>
                <dd class="text-slate-700 mt-0.5">{{ $tenant->subscription?->expires_at?->format('d M Y') ?? '—' }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex gap-3">
            <a href="{{ route('platform.tenants.edit', $tenant->uuid) }}"
                class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                Edit
            </a>
            <a href="{{ $tenant->login_url }}" target="_blank"
                class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Open portal ↗
            </a>
        </div>
    </div>

    {{-- Tenant users --}}
    <div class="bg-white rounded-lg border border-slate-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-800">Users</h3>
            <a href="{{ route('platform.tenants.users.create', $tenant->uuid) }}"
                class="rounded-md bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">
                New User
            </a>
        </div>
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Roles</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tenantUsers as $u)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium text-slate-800">{{ $u->name }}</td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $u->username }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $u->email }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">
                        {{ $u->roles->pluck('name')->join(', ') ?: '—' }}
                    </td>
                    <td class="px-6 py-3 text-sm">
                        <span class="{{ $u->is_active ? 'text-green-600' : 'text-red-500' }}">
                            {{ $u->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('platform.tenants.users.edit', [$tenant->uuid, $u->id]) }}"
                            class="text-xs text-slate-500 hover:text-slate-800">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-slate-400">No users yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
