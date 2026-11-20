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
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Forms</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($providers as $provider)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-3 text-sm font-medium">
                        <button wire:click="openTemplates({{ $provider->id }})"
                            class="text-blue-600 hover:text-blue-800 hover:underline text-left">
                            {{ $provider->name }}
                        </button>
                    </td>
                    <td class="px-6 py-3 text-sm font-mono text-slate-600">{{ $provider->code }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $provider->contact_name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-slate-600">{{ $provider->phone ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-slate-500">
                        {{ $provider->formTemplates()->count() ?: '—' }}
                    </td>
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
                <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-slate-400">No insurance providers found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $providers->links() }}
        </div>
    </div>

    {{-- Templates slide-over --}}
    @if($showTemplates)
    <div class="fixed inset-0 z-50 overflow-hidden" x-on:keydown.escape.window="$wire.closeTemplates()">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-slate-900/40" wire:click="closeTemplates"></div>

        {{-- Panel --}}
        <div class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-white shadow-xl">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">{{ $selectedProviderName }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Form Templates</p>
                </div>
                <button wire:click="closeTemplates" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto px-6 py-4">

                @if($canManage)
                <div class="mb-4">
                    @if(!$showUploadForm)
                        <button wire:click="$set('showUploadForm', true)"
                            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Upload Form
                        </button>
                    @else
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-3">
                            <p class="text-sm font-medium text-slate-700">Upload New Form</p>

                            @error('templateName')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Name *</label>
                                <input wire:model="templateName" type="text" placeholder="e.g. Pre-Authorization Form"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
                                <input wire:model="templateDescription" type="text" placeholder="Optional notes"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                            </div>

                            @error('templateFile')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">File * <span class="text-slate-400">(PDF, Word, Excel, Image — max 10 MB)</span></label>
                                <input wire:model="templateFile" type="file"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                                    class="w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div class="flex gap-2 pt-1">
                                <button wire:click="uploadTemplate"
                                    class="rounded-md bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-500">
                                    Save
                                </button>
                                <button wire:click="$set('showUploadForm', false)"
                                    class="rounded-md border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                @if($templates->isEmpty())
                    <div class="text-center py-12 text-sm text-slate-400">
                        No form templates yet.
                        @if($canManage) Click "Upload Form" to add one. @endif
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($templates as $t)
                        <div class="flex items-start justify-between rounded-lg border border-slate-200 bg-white px-4 py-3">
                            <div class="min-w-0 flex-1 pr-4">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $t->name }}</p>
                                @if($t->description)
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $t->description }}</p>
                                @endif
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $t->original_name }} &middot; {{ $t->file_size_human }} &middot; {{ $t->created_at->format('d M Y') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <a href="{{ route('tenant.insurance.templates.download', $t->id) }}"
                                    class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                    Download
                                </a>
                                @if($canManage)
                                <button wire:click="deleteTemplate({{ $t->id }})"
                                    wire:confirm="Delete '{{ $t->name }}'?"
                                    class="text-xs text-red-500 hover:text-red-700">
                                    Delete
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
