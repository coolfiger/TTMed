<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ImagingReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImagingReportDownloadController extends Controller
{
    public function __invoke(int $id)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('encounters.view')) {
            abort(403);
        }

        $report = ImagingReport::on('tenant')->findOrFail($id);
        $path   = $report->report_pdf_path;

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $fileName = 'IMG-' . $report->id . '.pdf';

        return response()->stream(
            fn () => print(Storage::disk('local')->get($path)),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                'Cache-Control'       => 'private, no-store',
            ]
        );
    }
}
