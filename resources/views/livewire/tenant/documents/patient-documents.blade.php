<div class="max-w-4xl mx-auto py-6 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Documents</h1>
            <p class="text-sm text-gray-500">{{ $patientName }}</p>
        </div>
        <button wire:click="$toggle('showUploadForm')"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
            Upload Document
        </button>
    </div>

    {{-- Alerts --}}
    @if($successMessage)
        <div class="rounded-md bg-green-50 border border-green-200 p-3 text-green-800 text-sm">{{ $successMessage }}</div>
    @endif
    @if($errorMessage)
        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-red-800 text-sm">{{ $errorMessage }}</div>
    @endif

    {{-- Upload form --}}
    @if($showUploadForm)
    <div class="bg-white rounded-lg border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Upload New Document</h2>
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">File <span class="text-red-500">*</span></label>
                <input type="file" wire:model="uploadFile"
                       accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.txt"
                       class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md px-3 py-2">
                <p class="text-xs text-gray-400 mt-1">PDF, images, Word docs up to 20 MB</p>
                @error('uploadFile') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" wire:model="uploadTitle" maxlength="250"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                @error('uploadTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                <select wire:model="uploadCategoryId"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="">No category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                <textarea wire:model="uploadDescription" rows="2" maxlength="1000"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></textarea>
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button wire:click="$set('showUploadForm', false)"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
            <button wire:click="upload"
                    class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Upload
            </button>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="flex items-center gap-3">
        <select wire:model.live="filterCategory"
                class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model.live="showArchived" class="rounded border-gray-300">
            Show archived
        </label>
    </div>

    {{-- Documents list --}}
    @if($documents->isEmpty())
        <div class="text-center py-12 text-gray-400 text-sm">No documents found.</div>
    @else
    <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
        @foreach($documents as $doc)
        <div class="px-5 py-4 flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 min-w-0">
                <div class="mt-0.5 shrink-0">
                    @if(str_contains($doc->mime_type ?? '', 'pdf'))
                        <span class="inline-block w-8 h-8 bg-red-100 text-red-700 rounded text-xs font-bold flex items-center justify-center">PDF</span>
                    @elseif(str_contains($doc->mime_type ?? '', 'image'))
                        <span class="inline-block w-8 h-8 bg-blue-100 text-blue-700 rounded text-xs font-bold flex items-center justify-center">IMG</span>
                    @else
                        <span class="inline-block w-8 h-8 bg-gray-100 text-gray-700 rounded text-xs font-bold flex items-center justify-center">DOC</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="font-medium text-gray-900 text-sm truncate">{{ $doc->title }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">
                        {{ $doc->original_filename }}
                        &middot; {{ $doc->file_size_label }}
                        @if($doc->category) &middot; <span class="text-blue-600">{{ $doc->category->name }}</span> @endif
                        &middot; {{ $doc->created_at->format('d M Y') }}
                        @if($doc->uploadedBy) &middot; {{ $doc->uploadedBy->name }} @endif
                    </div>
                    @if($doc->description)
                        <div class="text-xs text-gray-500 mt-1">{{ $doc->description }}</div>
                    @endif
                    @if($doc->is_archived)
                        <span class="inline-block mt-1 px-1.5 py-0.5 text-xs bg-gray-100 text-gray-500 rounded">Archived</span>
                    @endif
                </div>
            </div>
            <div class="shrink-0">
                <a href="{{ route('tenant.documents.download', $doc->id) }}"
                   class="text-xs font-medium text-blue-600 hover:text-blue-800">Download</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
