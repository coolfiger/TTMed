<?php

namespace App\Livewire\Tenant\Documents;

use App\Models\Tenant\DocumentCategory;
use App\Models\Tenant\PatientDocument;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class PatientDocuments extends Component
{
    use WithFileUploads;

    public int    $patientId   = 0;
    public string $patientName = '';
    public ?int   $encounterId = null;

    // Upload form
    public bool   $showUploadForm = false;
    public        $uploadFile     = null;
    public string $uploadTitle    = '';
    public string $uploadDescription = '';
    public ?int   $uploadCategoryId  = null;

    // Filter
    public string $filterCategory = '';
    public bool   $showArchived   = false;

    // UI
    public string $successMessage = '';
    public string $errorMessage   = '';

    protected DocumentService $documentService;

    public function boot(DocumentService $documentService): void
    {
        $this->documentService = $documentService;
    }

    public function mount(int $patientId, ?int $encounterId = null): void
    {
        $this->checkPermission('documents.view');

        $this->patientId   = $patientId;
        $this->encounterId = $encounterId;

        $patient = \App\Models\Tenant\Patient::on('tenant')->findOrFail($patientId);
        $this->patientName = $patient->full_name;
    }

    // -------------------------------------------------------------------------
    // Upload
    // -------------------------------------------------------------------------

    public function upload(): void
    {
        $this->checkPermission('documents.upload');

        $this->validate([
            'uploadFile'        => 'required|file|max:20480',
            'uploadTitle'       => 'required|string|max:250',
            'uploadDescription' => 'nullable|string|max:1000',
            'uploadCategoryId'  => 'nullable|integer|exists:tenant.document_categories,id',
        ]);

        try {
            $this->documentService->upload(
                file:        $this->uploadFile,
                patientId:   $this->patientId,
                uploadedBy:  Auth::guard('tenant')->id(),
                encounterId: $this->encounterId,
                categoryId:  $this->uploadCategoryId,
                title:       $this->uploadTitle,
                description: $this->uploadDescription ?: null,
            );

            $this->successMessage  = 'Document uploaded.';
            $this->showUploadForm  = false;
            $this->resetUploadForm();
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    public function render()
    {
        $query = PatientDocument::on('tenant')
            ->with(['uploadedBy', 'category'])
            ->where('patient_id', $this->patientId);

        if (!$this->showArchived) {
            $query->where('is_archived', false);
        }

        if ($this->filterCategory) {
            $query->where('document_category_id', $this->filterCategory);
        }

        $documents  = $query->orderByDesc('created_at')->get();
        $categories = DocumentCategory::on('tenant')->where('is_active', true)->orderBy('sort_order')->get();

        return view('livewire.tenant.documents.patient-documents', [
            'documents'  => $documents,
            'categories' => $categories,
        ])->layout('layouts.app-workspace');
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }

    private function resetUploadForm(): void
    {
        $this->uploadFile        = null;
        $this->uploadTitle       = '';
        $this->uploadDescription = '';
        $this->uploadCategoryId  = null;
    }
}
