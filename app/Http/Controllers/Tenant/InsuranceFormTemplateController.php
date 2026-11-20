<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\InsuranceFormTemplate;
use App\Models\Tenant\InsuranceProvider;
use App\Services\AuditService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InsuranceFormTemplateController extends Controller
{
    public function upload(Request $request, int $providerId): \Illuminate\Http\JsonResponse
    {
        $this->checkPermission('insurance.manage');

        $provider = InsuranceProvider::findOrFail($providerId);

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:300',
        ]);

        $tenantCode = app(TenantContext::class)->get()?->code ?? 'default';
        $file       = $request->file('file');
        $ext        = $file->getClientOriginalExtension();
        $stored     = $file->store("tenants/{$tenantCode}/insurance/{$providerId}", 'local');

        $template = InsuranceFormTemplate::create([
            'insurance_provider_id' => $provider->id,
            'name'                  => $request->input('name'),
            'description'           => $request->input('description'),
            'file_path'             => $stored,
            'original_name'         => $file->getClientOriginalName(),
            'mime_type'             => $file->getMimeType(),
            'file_size'             => $file->getSize(),
            'uploaded_by'           => auth()->guard('tenant')->id(),
        ]);

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            'insurance.template.uploaded',
            'InsuranceProvider',
            $provider->id
        );

        return response()->json([
            'id'            => $template->id,
            'name'          => $template->name,
            'description'   => $template->description,
            'original_name' => $template->original_name,
            'file_size_human' => $template->file_size_human,
            'created_at'    => $template->created_at->format('d M Y'),
        ]);
    }

    public function download(int $templateId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->checkPermission('insurance.view');

        $template = InsuranceFormTemplate::findOrFail($templateId);

        $realPath    = storage_path('app/' . $template->file_path);
        $storageBase = realpath(storage_path('app'));
        $resolved    = realpath($realPath);

        if ($resolved === false || !str_starts_with($resolved, $storageBase . DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        if (!file_exists($resolved)) {
            abort(404);
        }

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            'insurance.template.downloaded',
            'InsuranceProvider',
            $template->insurance_provider_id
        );

        return response()->download($resolved, $template->original_name, [
            'Content-Type' => $template->mime_type,
        ]);
    }

    public function destroy(int $templateId): \Illuminate\Http\JsonResponse
    {
        $this->checkPermission('insurance.manage');

        $template = InsuranceFormTemplate::findOrFail($templateId);
        $path     = storage_path('app/' . $template->file_path);

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

        return response()->json(['ok' => true]);
    }

    private function checkPermission(string $permission): void
    {
        $user = auth()->guard('tenant')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403);
        }
    }
}
