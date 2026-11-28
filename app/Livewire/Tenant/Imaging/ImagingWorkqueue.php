<?php

namespace App\Livewire\Tenant\Imaging;

use App\Models\Tenant\Branch;
use App\Models\Tenant\ImagingOrder;
use App\Models\Tenant\ImagingReport;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app-workspace')]
class ImagingWorkqueue extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $statusFilter  = '';
    public string $modalityFilter = '';
    public string $branchFilter  = '';
    public string $dateFrom      = '';
    public string $dateTo        = '';
    public string $sourceFilter  = '';
    public bool   $criticalOnly  = false;

    // Review panel
    public ?int   $reviewingOrderId = null;
    public string $reviewNotes      = '';

    public string $successMessage = '';
    public string $errorMessage   = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingModalityFilter(): void { $this->resetPage(); }
    public function updatingCriticalOnly(): void { $this->resetPage(); }

    public function markReviewed(int $orderId): void
    {
        $this->checkPermission('imaging.review');

        $order = ImagingOrder::on('tenant')->findOrFail($orderId);
        $order->update([
            'status'      => 'report_reviewed',
            'reviewed_by' => Auth::guard('tenant')->id(),
            'reviewed_at' => now(),
        ]);

        if ($order->report) {
            $order->report->update([
                'reviewed_by' => Auth::guard('tenant')->id(),
                'reviewed_at' => now(),
            ]);
        }

        // Update inbound log entry if one exists
        \App\Models\Tenant\DocumentInboundLog::on('tenant')
            ->where('reference_type', ImagingReport::class)
            ->where('reference_id', $order->report?->id)
            ->whereNotIn('status', ['reviewed'])
            ->update([
                'status'      => 'reviewed',
                'reviewed_by' => Auth::guard('tenant')->id(),
                'reviewed_at' => now(),
            ]);

        app(AuditService::class)->logTenant(
            action:       'imaging.report.reviewed',
            resourceType: 'imaging_order',
            resourceId:   $order->id,
            newValues:    ['patient_id' => $order->patient_id],
            description:  "Imaging order #{$order->order_number} marked reviewed",
        );

        $this->reviewingOrderId = null;
        $this->reviewNotes = '';
        $this->successMessage = 'Marked as reviewed.';
    }

    public function flagCritical(int $reportId, bool $critical): void
    {
        $this->checkPermission('imaging.review');

        $report = ImagingReport::on('tenant')
            ->where('id', $reportId)
            ->firstOrFail();

        $report->update(['is_critical' => $critical]);

        \App\Models\Tenant\DocumentInboundLog::on('tenant')
            ->where('reference_type', ImagingReport::class)
            ->where('reference_id', $reportId)
            ->update([
                'is_critical' => $critical,
                'status'      => $critical ? 'critical' : ($report->order?->status === 'report_reviewed' ? 'reviewed' : 'received'),
            ]);

        $this->successMessage = $critical ? 'Flagged as critical.' : 'Critical flag removed.';
    }

    public function render()
    {
        $orders = ImagingOrder::on('tenant')
            ->with(['patient', 'report', 'branch', 'orderedBy'])
            ->when($this->search, function ($q) {
                $q->whereHas('patient', fn ($p) => $p->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('patient_number', 'like', "%{$this->search}%"))
                  ->orWhere('order_number', 'like', "%{$this->search}%")
                  ->orWhere('accession_number', 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->modalityFilter, fn ($q) => $q->where('modality', $this->modalityFilter))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('ordered_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('ordered_at', '<=', $this->dateTo))
            ->when($this->sourceFilter, fn ($q) => $q->whereHas('report', fn ($r) => $r->where('source', $this->sourceFilter)))
            ->when($this->criticalOnly, fn ($q) => $q->whereHas('report', fn ($r) => $r->where('is_critical', true)))
            ->orderByRaw("FIELD(status, 'report_available', 'report_preliminary', 'in_progress', 'sent_to_ris', 'scheduled', 'pending', 'report_final', 'report_reviewed', 'cancelled')")
            ->orderByDesc('ordered_at')
            ->paginate(30);

        $branches  = Branch::on('tenant')->where('is_active', true)->get(['id', 'name']);
        $statuses  = [
            'pending'              => 'Pending',
            'sent_to_ris'          => 'Sent to RIS',
            'scheduled'            => 'Scheduled',
            'in_progress'          => 'In Progress',
            'report_available'     => 'Report Available',
            'report_preliminary'   => 'Preliminary Report',
            'report_final'         => 'Final Report',
            'report_reviewed'      => 'Reviewed',
            'cancelled'            => 'Cancelled',
        ];

        $criticalCount  = ImagingOrder::on('tenant')
            ->whereHas('report', fn ($r) => $r->where('is_critical', true)->where('reviewed_at', null))
            ->count();

        $pendingReviewCount = ImagingOrder::on('tenant')
            ->whereIn('status', ['report_available', 'report_preliminary', 'report_final'])
            ->count();

        return view('livewire.tenant.imaging.imaging-workqueue', compact(
            'orders', 'branches', 'statuses', 'criticalCount', 'pendingReviewCount'
        ));
    }

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }
}
