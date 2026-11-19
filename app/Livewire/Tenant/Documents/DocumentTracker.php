<?php

namespace App\Livewire\Tenant\Documents;

use App\Models\Tenant\DocumentInboundLog;
use App\Models\Tenant\Branch;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app-workspace')]
class DocumentTracker extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $typeFilter   = '';
    public string $sourceFilter = '';
    public string $statusFilter = '';
    public string $branchFilter = '';
    public string $dateFrom     = '';
    public string $dateTo       = '';
    public bool   $criticalOnly = false;

    public string $successMessage = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingCriticalOnly(): void { $this->resetPage(); }

    public function markReviewed(int $id): void
    {
        $this->checkPermission('documents.view');

        $entry = DocumentInboundLog::on('tenant')->findOrFail($id);
        $entry->markReviewed(Auth::guard('tenant')->id());

        app(AuditService::class)->log(
            'document.tracker.reviewed',
            "Inbound document log #{$id} marked reviewed",
            ['log_id' => $id, 'type' => $entry->type]
        );

        $this->successMessage = 'Marked as reviewed.';
    }

    public function render()
    {
        $items = DocumentInboundLog::on('tenant')
            ->with(['patient', 'reviewedBy', 'branch'])
            ->when($this->search, function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhereHas('patient', fn ($p) =>
                      $p->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('patient_number', 'like', "%{$this->search}%")
                  );
            })
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->sourceFilter, fn ($q) => $q->where('source', $this->sourceFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->branchFilter, fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->criticalOnly, fn ($q) => $q->where('is_critical', true))
            ->orderByRaw("FIELD(status, 'critical', 'received', 'reviewed', 'error')")
            ->orderByDesc('created_at')
            ->paginate(40);

        $branches = Branch::on('tenant')->where('is_active', true)->get(['id', 'name']);

        $stats = [
            'total_today'    => DocumentInboundLog::on('tenant')->whereDate('created_at', today())->count(),
            'pending_review' => DocumentInboundLog::on('tenant')->where('status', 'received')->count(),
            'critical'       => DocumentInboundLog::on('tenant')->where('status', 'critical')->count(),
            'reviewed_today' => DocumentInboundLog::on('tenant')->whereDate('reviewed_at', today())->count(),
        ];

        return view('livewire.tenant.documents.document-tracker', compact('items', 'branches', 'stats'));
    }

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }
}
