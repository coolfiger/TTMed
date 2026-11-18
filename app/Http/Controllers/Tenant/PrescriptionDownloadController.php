<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrescriptionDownloadController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('prescriptions.view')) {
            abort(403);
        }

        $prescription = Prescription::on('tenant')
            ->where('status', 'issued')
            ->findOrFail($id);

        if (!$prescription->pdf_path || !file_exists($prescription->pdf_path)) {
            abort(404, 'Prescription PDF not found.');
        }

        $filename = "Prescription_{$prescription->prescription_number}.pdf";

        return response()->streamDownload(function () use ($prescription) {
            $handle = fopen($prescription->pdf_path, 'rb');
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
