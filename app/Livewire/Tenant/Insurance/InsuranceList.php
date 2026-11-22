<?php

namespace App\Livewire\Tenant\Insurance;

use App\Models\Tenant\InsuranceFormTemplate;
use App\Models\Tenant\InsuranceProvider;
use App\Services\AuditService;
use App\Tenancy\TenantContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app-workspace')]
class InsuranceList extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';

    // Template slide-over state
    public ?int    $selectedProviderId   = null;
    public ?string $selectedProviderName = null;
    public bool    $showTemplates        = false;

    // Upload form
    public        $templateFile        = null;
    public string $templateName        = '';
    public string $templateDescription = '';
    public bool   $showUploadForm      = false;
    public ?string $uploadError        = null;

    public function updatingSearch(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $provider = InsuranceProvider::findOrFail($id);
        $provider->update(['is_active' => !$provider->is_active]);
    }

    public function openTemplates(int $providerId): void
    {
        $provider = InsuranceProvider::findOrFail($providerId);
        $this->selectedProviderId   = $providerId;
        $this->selectedProviderName = $provider->name;
        $this->showTemplates        = true;
        $this->showUploadForm       = false;
        $this->resetUploadForm();
    }

    public function closeTemplates(): void
    {
        $this->showTemplates      = false;
        $this->selectedProviderId = null;
        $this->resetUploadForm();
    }

    public function uploadTemplate(): void
    {
        $user = auth()->guard('tenant')->user();
        if (!$user || !$user->hasPermission('insurance.manage')) {
            abort(403);
        }

        $this->validate([
            'templateFile'        => 'required|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240',
            'templateName'        => 'required|string|max:150',
            'templateDescription' => 'nullable|string|max:300',
        ]);

        $tenantCode  = app(TenantContext::class)->get()?->code ?? 'default';
        $file        = $this->templateFile;
        $stored      = $file->storeAs(
            "tenants/{$tenantCode}/insurance/{$this->selectedProviderId}",
            $file->getClientOriginalName(),
            'local'
        );

        InsuranceFormTemplate::create([
            'insurance_provider_id' => $this->selectedProviderId,
            'name'                  => $this->templateName,
            'description'           => $this->templateDescription,
            'file_path'             => $stored,
            'original_name'         => $file->getClientOriginalName(),
            'mime_type'             => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'             => $file->getSize(),
            'uploaded_by'           => auth()->guard('tenant')->id(),
        ]);

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            'insurance.template.uploaded',
            'InsuranceProvider',
            $this->selectedProviderId
        );

        $this->resetUploadForm();
        $this->showUploadForm = false;
    }

    public function deleteTemplate(int $templateId): void
    {
        $user = auth()->guard('tenant')->user();
        if (!$user || !$user->hasPermission('insurance.manage')) {
            abort(403);
        }

        $template = InsuranceFormTemplate::findOrFail($templateId);

        if ($template->insurance_provider_id !== $this->selectedProviderId) {
            abort(403);
        }

        $path = storage_path('app/' . $template->file_path);
        if (file_exists($path)) {
            @unlink($path);
        }

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            'insurance.template.deleted',
            'InsuranceProvider',
            $template->insurance_provider_id
        );

        $template->delete();
    }

    private function resetUploadForm(): void
    {
        $this->templateFile        = null;
        $this->templateName        = '';
        $this->templateDescription = '';
        $this->uploadError         = null;
    }

    public function render(): \Illuminate\View\View
    {
        $providers = InsuranceProvider::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(25);

        $templates = $this->selectedProviderId
            ? InsuranceFormTemplate::where('insurance_provider_id', $this->selectedProviderId)
                ->orderBy('name')
                ->get()
            : collect();

        $canManage = auth()->guard('tenant')->user()?->hasPermission('insurance.manage') ?? false;

        return view('livewire.tenant.insurance.insurance-list', compact('providers', 'templates', 'canManage'));
    }
}
