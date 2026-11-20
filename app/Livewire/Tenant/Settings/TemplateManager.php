<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant\Branch;
use App\Models\Tenant\DocumentTemplate;
use App\Services\AuditService;
use App\Support\DefaultTemplates;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app-workspace')]
class TemplateManager extends Component
{
    use WithFileUploads;

    public string $activeTypeTab = 'prescription';

    public bool   $editing   = false;
    public ?int   $editingId = null;

    // Metadata fields
    public string $name            = '';
    public string $type            = 'prescription';
    public string $branchId        = '';
    public bool   $isDefault       = false;
    public bool   $isActive        = true;
    public string $primaryColor    = '#1e40af';
    public string $fontFamily      = 'DejaVu Sans';
    public string $pageSize        = 'A4';
    public string $orientation     = 'portrait';
    public string $marginTop       = '20';
    public string $marginRight     = '20';
    public string $marginBottom    = '20';
    public string $marginLeft      = '20';
    public bool   $watermarkEnabled = false;
    public string $watermarkText    = '';
    public string $watermarkOpacity = '0.08';
    public bool   $showBranchHeader = true;
    public bool   $showBranchFooter = false;

    // Legacy HTML fields (kept for compatibility)
    public string $headerHtml = '';
    public string $footerHtml = '';
    public string $customCss  = '';

    // GrapesJS editor
    public string $bodyHtml = '';
    public string $gjsData  = ''; // JSON string

    public bool $isSystemTemplate = false; // true when editing a seeded default

    public        $logoFile = null;

    public string $successMessage = '';
    public string $errorMessage   = '';

    public function mount(): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || (!$user->hasPermission('settings.manage') && !$user->hasPermission('settings.view'))) {
            abort(403);
        }
    }

    public function newTemplate(): void
    {
        $this->resetForm();
        $this->type    = $this->activeTypeTab;
        $this->name    = 'New ' . (DocumentTemplate::TYPES[$this->activeTypeTab] ?? 'Template');
        $this->editing = true;
        $this->editingId = null;
        $this->bodyHtml = DefaultTemplates::forType($this->type);
        $this->gjsData  = '';

        $this->dispatch('gjs:load', gjsData: null, bodyHtml: $this->bodyHtml);
    }

    public function editTemplate(int $id): void
    {
        $template = DocumentTemplate::on('tenant')->findOrFail($id);

        $this->editingId        = $template->id;
        $this->name             = $template->name;
        $this->type             = $template->type;
        $this->branchId         = (string) ($template->branch_id ?? '');
        $this->isDefault        = $template->is_default;
        $this->isActive         = $template->is_active;
        $this->primaryColor     = $template->primary_color;
        $this->fontFamily       = $template->font_family;
        $this->headerHtml       = $template->header_html ?? '';
        $this->footerHtml       = $template->footer_html ?? '';
        $this->customCss        = $template->custom_css ?? '';
        $this->watermarkEnabled = $template->watermark_enabled;
        $this->watermarkText    = $template->watermark_text ?? '';
        $this->watermarkOpacity = (string) $template->watermark_opacity;
        $this->showBranchHeader = $template->show_branch_header;
        $this->showBranchFooter = $template->show_branch_footer;
        $this->pageSize         = $template->page_size;
        $this->orientation      = $template->orientation;
        $this->marginTop        = (string) $template->margin_top;
        $this->marginRight      = (string) $template->margin_right;
        $this->marginBottom     = (string) $template->margin_bottom;
        $this->marginLeft       = (string) $template->margin_left;
        $this->editing          = true;
        $this->isSystemTemplate = $template->is_default && is_null($template->created_by);

        $this->bodyHtml = $template->body_html
            ?? DefaultTemplates::forType($template->type);

        $gjsArr = $template->gjs_data;
        $this->gjsData = ($gjsArr && count($gjsArr)) ? json_encode($gjsArr) : '';

        $this->dispatch('gjs:load',
            gjsData:  $gjsArr ?: null,
            bodyHtml: $this->bodyHtml
        );
    }

    /**
     * Called from Alpine.js inside the GrapesJS editor when the user clicks Save.
     */
    public function saveWithBody(string $html, string $css, string $gjsJson): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('settings.manage')) {
            abort(403);
        }

        // If editing a system default (seeded, no owner), fork it rather than overwrite
        if ($this->editingId) {
            $original = DocumentTemplate::on('tenant')->find($this->editingId);
            if ($original && $original->is_default && is_null($original->created_by)) {
                $this->editingId = null;
                if (!str_ends_with(trim($this->name), ' Custom')) {
                    $this->name = trim($this->name) . ' Custom';
                }
                $this->isDefault = false;
            }
        }

        $this->validate([
            'name'            => 'required|string|max:150',
            'type'            => 'required|in:' . implode(',', array_keys(DocumentTemplate::TYPES)),
            'branchId'        => 'nullable|exists:branches,id',
            'primaryColor'    => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'watermarkOpacity'=> 'nullable|numeric|min:0.01|max:0.5',
            'marginTop'       => 'nullable|integer|min:5|max:60',
            'marginRight'     => 'nullable|integer|min:5|max:60',
            'marginBottom'    => 'nullable|integer|min:5|max:60',
            'marginLeft'      => 'nullable|integer|min:5|max:60',
        ]);

        // Build self-contained body HTML (CSS embedded)
        $fullBodyHtml = $css ? "<style>\n{$css}\n</style>\n{$html}" : $html;

        $gjsArr = [];
        if ($gjsJson) {
            $decoded = json_decode($gjsJson, true);
            if (is_array($decoded)) {
                $gjsArr = $decoded;
            }
        }

        // Handle logo upload
        $logoPath = null;
        if ($this->logoFile) {
            $tenantCode = app(TenantContext::class)->get()?->code;
            $ext        = $this->logoFile->getClientOriginalExtension();
            $logoPath   = $this->logoFile->storeAs(
                "public/tenants/{$tenantCode}/templates",
                'logo_' . time() . '.' . $ext
            );
        }

        if ($this->isDefault) {
            DocumentTemplate::on('tenant')
                ->where('type', $this->type)
                ->where('branch_id', $this->branchId ?: null)
                ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
                ->update(['is_default' => false]);
        }

        $data = [
            'name'              => $this->name,
            'type'              => $this->type,
            'branch_id'         => $this->branchId ?: null,
            'is_default'        => $this->isDefault,
            'is_active'         => $this->isActive,
            'primary_color'     => $this->primaryColor ?: '#1e40af',
            'font_family'       => $this->fontFamily ?: 'DejaVu Sans',
            'body_html'         => $fullBodyHtml,
            'gjs_data'          => $gjsArr ?: null,
            'watermark_enabled' => $this->watermarkEnabled,
            'watermark_text'    => $this->watermarkText ?: null,
            'watermark_opacity' => (float) ($this->watermarkOpacity ?: 0.08),
            'show_branch_header'=> $this->showBranchHeader,
            'show_branch_footer'=> $this->showBranchFooter,
            'page_size'         => $this->pageSize,
            'orientation'       => $this->orientation,
            'margin_top'        => (int) ($this->marginTop ?: 20),
            'margin_right'      => (int) ($this->marginRight ?: 20),
            'margin_bottom'     => (int) ($this->marginBottom ?: 20),
            'margin_left'       => (int) ($this->marginLeft ?: 20),
            'created_by'        => $user->id,
        ];

        if ($logoPath) {
            $data['logo_path'] = $logoPath;
        }

        if ($this->editingId) {
            DocumentTemplate::on('tenant')->findOrFail($this->editingId)->update($data);
            $this->successMessage = 'Template saved.';
        } else {
            DocumentTemplate::on('tenant')->create($data);
            $this->successMessage = 'Template created.';
        }

        app(AuditService::class)->logTenant(
            $user->id,
            $this->editingId ? 'template.updated' : 'template.created',
            'DocumentTemplate',
            $this->editingId
        );

        $this->editing   = false;
        $this->editingId = null;
        $this->resetForm();
    }

    public function deleteTemplate(int $id): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('settings.manage')) {
            abort(403);
        }

        DocumentTemplate::on('tenant')->findOrFail($id)->delete();
        $this->successMessage = 'Template deleted.';
    }

    public function cancelEdit(): void
    {
        $this->editing   = false;
        $this->editingId = null;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->name             = '';
        $this->type             = $this->activeTypeTab;
        $this->branchId         = '';
        $this->isDefault        = false;
        $this->isActive         = true;
        $this->primaryColor     = '#1e40af';
        $this->fontFamily       = 'DejaVu Sans';
        $this->headerHtml       = '';
        $this->footerHtml       = '';
        $this->customCss        = '';
        $this->bodyHtml         = '';
        $this->gjsData          = '';
        $this->watermarkEnabled = false;
        $this->watermarkText    = '';
        $this->watermarkOpacity = '0.08';
        $this->showBranchHeader = true;
        $this->showBranchFooter = false;
        $this->pageSize         = 'A4';
        $this->orientation      = 'portrait';
        $this->marginTop        = '20';
        $this->marginRight      = '20';
        $this->marginBottom     = '20';
        $this->marginLeft       = '20';
        $this->logoFile          = null;
        $this->isSystemTemplate  = false;
        $this->errorMessage      = '';
    }

    public function render(): \Illuminate\View\View
    {
        $templates = DocumentTemplate::on('tenant')
            ->where('type', $this->activeTypeTab)
            ->with('branch')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('livewire.tenant.settings.template-manager', compact('templates', 'branches'));
    }
}
