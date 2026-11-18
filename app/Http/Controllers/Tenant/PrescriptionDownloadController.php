<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Prescription;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionDownloadController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function __invoke(Request $request, int $id)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('prescriptions.view')) {
            abort(403);
        }

        $prescription = Prescription::on('tenant')
            ->where('status', 'issued')
            ->findOrFail($id);

        if (!$prescription->pdf_path) {
            abort(404, 'Prescription PDF not found.');
        }

        // Resolve the real path and confirm it stays within this tenant's storage root.
        // This prevents path traversal if pdf_path were ever manipulated.
        $realPath    = realpath($prescription->pdf_path);
        $storageRoot = rtrim(realpath($this->tenantContext->storagePath()), '/');

        if ($realPath === false || !str_starts_with($realPath, $storageRoot . '/')) {
            abort(404, 'Prescription PDF not found.');
        }

        if (!file_exists($realPath)) {
            abort(404, 'Prescription PDF not found.');
        }

        $filename = "Prescription_{$prescription->prescription_number}.pdf";

        return response()->streamDownload(function () use ($realPath) {
            $handle = fopen($realPath, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
