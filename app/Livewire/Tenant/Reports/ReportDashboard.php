<?php

namespace App\Livewire\Tenant\Reports;

use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ReportDashboard extends Component
{
    private ReportService $reportService;

    public string $activeTab  = 'financial';
    public string $dateFrom   = '';
    public string $dateTo     = '';
    public int    $branchId   = 0;

    public function boot(ReportService $reportService): void
    {
        $this->reportService = $reportService;
    }

    public function mount(): void
    {
        $this->checkPermission('reports.view');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->checkPermission('reports.view');
        $this->activeTab = $tab;
    }

    private function from(): Carbon
    {
        return Carbon::parse($this->dateFrom);
    }

    private function to(): Carbon
    {
        return Carbon::parse($this->dateTo);
    }

    private function branch(): ?int
    {
        return $this->branchId ?: null;
    }

    private function checkPermission(string $permission): void
    {
        if (!auth()->guard('tenant')->user()?->hasPermission($permission)) {
            abort(403);
        }
    }

    public function render()
    {
        $data = match ($this->activeTab) {
            'financial'   => $this->reportService->financialSummary($this->from(), $this->to(), $this->branch()),
            'aging'       => $this->reportService->invoiceAging($this->branch()),
            'appointments'=> $this->reportService->appointmentVolume($this->from(), $this->to(), $this->branch()),
            'inventory'   => $this->reportService->inventoryStatus($this->from(), $this->to(), $this->branch()),
            'patients'    => $this->reportService->patientActivity($this->from(), $this->to(), $this->branch()),
            default       => [],
        };

        $branches = \Illuminate\Support\Facades\DB::connection('tenant')
            ->table('branches')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.tenant.reports.report-dashboard', [
            'reportData' => $data,
            'branches'   => $branches,
        ]);
    }
}
