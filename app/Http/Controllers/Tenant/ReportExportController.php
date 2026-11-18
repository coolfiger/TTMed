<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportExportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function __invoke(Request $request, string $report)
    {
        if (!auth()->guard('tenant')->user()?->hasPermission('reports.export')) {
            abort(403);
        }

        $from     = Carbon::parse($request->input('from', now()->startOfMonth()));
        $to       = Carbon::parse($request->input('to', now()));
        $branchId = $request->integer('branch_id') ?: null;

        [$csv, $filename] = match ($report) {
            'financial' => [
                $this->reportService->financialSummaryCsv(
                    $this->reportService->financialSummary($from, $to, $branchId)
                ),
                "financial-summary-{$from->toDateString()}-to-{$to->toDateString()}.csv",
            ],
            'aging' => [
                $this->reportService->invoiceAgingCsv(
                    $this->reportService->invoiceAging($branchId)['rows']
                ),
                "invoice-aging-" . now()->toDateString() . ".csv",
            ],
            'appointments' => [
                $this->reportService->appointmentVolumeCsv(
                    $this->reportService->appointmentVolume($from, $to, $branchId)
                ),
                "appointments-{$from->toDateString()}-to-{$to->toDateString()}.csv",
            ],
            'inventory' => [
                $this->reportService->inventoryStatusCsv(
                    $this->reportService->inventoryStatus($from, $to, $branchId)
                ),
                "inventory-low-stock-" . now()->toDateString() . ".csv",
            ],
            default => abort(404),
        };

        return response()->streamDownload(
            fn () => print($csv),
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }
}
