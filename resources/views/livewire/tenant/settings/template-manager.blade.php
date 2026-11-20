<div class="flex flex-col h-full min-h-0 bg-slate-100">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-slate-800">Document Templates</h1>
                <p class="text-xs text-slate-400 mt-0.5">Customise headers, footers, watermarks and branding for every document type</p>
            </div>
            @if(!$editing)
                <button wire:click="newTemplate"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition shadow-sm">
                    + New Template
                </button>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if($successMessage)
        <div class="mx-6 mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm text-emerald-800 flex justify-between flex-shrink-0"
             x-data x-init="setTimeout(() => $el.remove(), 3500)">
            {{ $successMessage }}
            <button wire:click="$set('successMessage','')" class="text-xs text-emerald-500">✕</button>
        </div>
    @endif

    @if($editing)
    {{-- ═══════ Template Editor ═══════ --}}
    <div class="flex-1 overflow-y-auto px-6 py-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">
                    {{ $editingId ? 'Edit Template' : 'New Template' }}
                </h2>
                <button wire:click="cancelEdit" class="text-sm text-slate-400 hover:text-slate-700">✕ Cancel</button>
            </div>

            <div class="p-6 space-y-6">

                {{-- Identity --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Template Name</label>
                        <input wire:model="name" type="text" placeholder="e.g. Standard Prescription"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none transition" />
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Document Type</label>
                        <select wire:model="type" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none">
                            @foreach(\App\Models\Tenant\DocumentTemplate::TYPES as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Branch</label>
                        <select wire:model="branchId" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-4 pb-0.5">
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                            <input type="checkbox" wire:model="isDefault" class="rounded text-blue-600" />
                            Default for type
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                            <input type="checkbox" wire:model="isActive" class="rounded text-blue-600" />
                            Active
                        </label>
                    </div>
                </div>

                <hr class="border-slate-100">

                {{-- Branding --}}
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Branding</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Primary Colour</label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="primaryColor"
                                       class="h-10 w-14 rounded-lg border border-slate-200 cursor-pointer p-0.5" />
                                <input type="text" wire:model="primaryColor" placeholder="#1e40af"
                                       class="flex-1 border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none transition" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Font Family</label>
                            <select wire:model="fontFamily" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none">
                                <option value="DejaVu Sans">DejaVu Sans (Default)</option>
                                <option value="DejaVu Serif">DejaVu Serif</option>
                                <option value="Helvetica">Helvetica</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Logo Image</label>
                            <input type="file" wire:model="logoFile" accept="image/*"
                                   class="w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                            @error('logoFile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Branch in header/footer --}}
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" wire:model="showBranchHeader" class="rounded text-blue-600" />
                        Show branch details in header
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" wire:model="showBranchFooter" class="rounded text-blue-600" />
                        Show branch details in footer
                    </label>
                </div>

                <hr class="border-slate-100">

                {{-- Content Overrides --}}
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Custom Header HTML</h3>
                    <textarea wire:model="headerHtml" rows="4"
                              placeholder="Leave blank to use the default header. HTML is supported."
                              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-mono text-slate-700 focus:border-blue-500 focus:outline-none transition resize-y"></textarea>
                </div>

                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Custom Footer HTML</h3>
                    <textarea wire:model="footerHtml" rows="4"
                              placeholder="Leave blank to use the default footer. HTML is supported."
                              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-mono text-slate-700 focus:border-blue-500 focus:outline-none transition resize-y"></textarea>
                </div>

                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Custom CSS</h3>
                    <textarea wire:model="customCss" rows="4"
                              placeholder="Additional CSS applied to the PDF. Use sparingly."
                              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-mono text-slate-700 focus:border-blue-500 focus:outline-none transition resize-y"></textarea>
                </div>

                <hr class="border-slate-100">

                {{-- Watermark --}}
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Watermark</h3>
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                            <input type="checkbox" wire:model="watermarkEnabled" class="rounded text-blue-600" />
                            Enable
                        </label>
                    </div>
                    @if($watermarkEnabled)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Watermark Text</label>
                                <input wire:model="watermarkText" type="text" placeholder="e.g. CONFIDENTIAL"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none transition" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Opacity (0.01 – 0.50)</label>
                                <input wire:model="watermarkOpacity" type="number" step="0.01" min="0.01" max="0.50"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none transition" />
                            </div>
                        </div>
                    @endif
                </div>

                <hr class="border-slate-100">

                {{-- Page layout --}}
                <div>
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Page Layout</h3>
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Page Size</label>
                            <select wire:model="pageSize" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none">
                                <option value="A4">A4</option>
                                <option value="Letter">Letter</option>
                                <option value="Legal">Legal</option>
                                <option value="A5">A5</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Orientation</label>
                            <select wire:model="orientation" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none">
                                <option value="portrait">Portrait</option>
                                <option value="landscape">Landscape</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Top (mm)</label>
                            <input wire:model="marginTop" type="number" min="5" max="60"
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none transition" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Bottom (mm)</label>
                            <input wire:model="marginBottom" type="number" min="5" max="60"
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none transition" />
                        </div>
                    </div>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
                <button wire:click="cancelEdit"
                        class="text-sm font-semibold text-slate-500 hover:text-slate-700 px-4 py-2 rounded-xl transition">
                    Cancel
                </button>
                <button wire:click="save"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-xl shadow-sm transition">
                    Save Template
                </button>
            </div>

        </div>
    </div>

    @else
    {{-- ═══════ Template List ═══════ --}}
    <div class="flex-1 overflow-y-auto px-6 py-4">

        {{-- Type tabs --}}
        <div class="flex gap-1 mb-4 bg-white rounded-xl border border-slate-200 p-1 overflow-x-auto flex-shrink-0">
            @foreach(\App\Models\Tenant\DocumentTemplate::TYPES as $val => $lbl)
                <button wire:click="$set('activeTypeTab', '{{ $val }}')"
                        class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                               {{ $activeTypeTab === $val
                                  ? 'bg-blue-600 text-white shadow-sm'
                                  : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                    {{ $lbl }}
                </button>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Branch</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Colour</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Watermark</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($templates as $tpl)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $tpl->name }}</p>
                                @if($tpl->is_default)
                                    <span class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full font-semibold">Default</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $tpl->branch?->name ?? 'All Branches' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-5 rounded border border-slate-200" style="background:{{ $tpl->primary_color }}"></div>
                                    <span class="text-xs font-mono text-slate-500">{{ $tpl->primary_color }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                @if($tpl->watermark_enabled && $tpl->watermark_text)
                                    <span class="text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded">{{ $tpl->watermark_text }}</span>
                                @else
                                    <span class="text-slate-300 text-xs">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $tpl->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $tpl->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button wire:click="editTemplate({{ $tpl->id }})"
                                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 mr-3">Edit</button>
                                <button wire:click="deleteTemplate({{ $tpl->id }})"
                                        wire:confirm="Delete this template? This cannot be undone."
                                        class="text-xs text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-sm text-slate-400 italic">
                                No templates for this document type yet. Click "+ New Template" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Usage note --}}
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-xs text-blue-700">
            <strong>How templates work:</strong> The <em>default</em> template for each type is applied automatically when that document is generated. If a branch-specific template exists, it takes priority over the global default. Custom HTML fields support standard HTML tags. Changes take effect immediately on the next document generated.
        </div>

    </div>
    @endif

</div>
