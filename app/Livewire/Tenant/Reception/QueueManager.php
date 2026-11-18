<?php

namespace App\Livewire\Tenant\Reception;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Doctor;
use App\Models\Tenant\DoctorAvailability;
use App\Models\Tenant\QueueEntry;
use App\Services\AuditService;
use App\Services\QueueService;
use Livewire\Component;

class QueueManager extends Component
{
    public int    $branchId       = 0;
    public string $filterStatus   = '';
    public int    $filterDoctorId = 0;

    // Transfer form
    public bool   $showTransferForm = false;
    public int    $transferEntryId  = 0;
    public int    $transferDoctorId = 0;

    // Doctor availability form
    public bool   $showAvailForm    = false;
    public int    $availDoctorId    = 0;
    public string $availStatus      = 'available';
    public string $availNote        = '';

    // Priority form
    public bool   $showPriorityForm  = false;
    public int    $priorityEntryId   = 0;
    public bool   $priorityValue     = true;
    public string $priorityReason    = '';

    public function mount(): void
    {
        $user = auth()->guard('tenant')->user();
        $this->branchId = $user?->staffProfile?->branch_id ?? Branch::on('tenant')->where('is_main', true)->value('id') ?? 0;
    }

    public function advance(int $entryId, string $toStatus): void
    {
        $this->checkPermission('queue.manage');

        $entry = QueueEntry::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($entryId);

        app(QueueService::class)->transition($entry, $toStatus, auth()->guard('tenant')->id());

        app(AuditService::class)->log(
            'queue.advance',
            "Queue #{$entry->queue_number} advanced to {$toStatus}",
            ['queue_entry_id' => $entryId, 'to_status' => $toStatus]
        );

        session()->flash('success', "Queue #{$entry->queue_number} updated.");
    }

    public function markNoShow(int $entryId): void
    {
        $this->advance($entryId, QueueEntry::STATUS_NO_SHOW);
    }

    public function cancel(int $entryId): void
    {
        $this->advance($entryId, QueueEntry::STATUS_CANCELLED);
    }

    public function openTransfer(int $entryId): void
    {
        $this->transferEntryId  = $entryId;
        $this->transferDoctorId = 0;
        $this->showTransferForm = true;
    }

    public function confirmTransfer(): void
    {
        $this->checkPermission('queue.manage');

        $this->validate([
            'transferDoctorId' => 'required|integer|min:1',
        ]);

        $entry = QueueEntry::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($this->transferEntryId);

        app(QueueService::class)->transferToDoctor(
            $entry,
            $this->transferDoctorId,
            auth()->guard('tenant')->id()
        );

        $this->showTransferForm = false;
        $this->transferEntryId  = 0;
        $this->transferDoctorId = 0;

        session()->flash('success', 'Patient transferred.');
    }

    public function cancelTransfer(): void
    {
        $this->showTransferForm = false;
        $this->transferEntryId  = 0;
        $this->transferDoctorId = 0;
    }

    public function openPriorityForm(int $entryId, bool $current): void
    {
        $this->priorityEntryId  = $entryId;
        $this->priorityValue    = !$current;
        $this->priorityReason   = '';
        $this->showPriorityForm = true;
    }

    public function savePriority(): void
    {
        $this->checkPermission('queue.manage');

        $this->validate([
            'priorityReason' => 'nullable|string|max:200',
        ]);

        $entry = QueueEntry::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($this->priorityEntryId);

        app(QueueService::class)->setPriority(
            $entry,
            $this->priorityValue,
            $this->priorityReason ?: null,
            auth()->guard('tenant')->id()
        );

        $this->showPriorityForm = false;
        $this->priorityEntryId  = 0;
    }

    public function cancelPriorityForm(): void
    {
        $this->showPriorityForm = false;
        $this->priorityEntryId  = 0;
    }

    public function openAvailForm(int $doctorId, string $currentStatus): void
    {
        $this->availDoctorId = $doctorId;
        $this->availStatus   = $currentStatus;
        $this->availNote     = '';
        $this->showAvailForm = true;
    }

    public function saveAvailability(): void
    {
        $this->checkPermission('queue.manage');

        $this->validate([
            'availStatus' => 'required|in:available,busy,on_break,off_duty,not_accepting',
            'availNote'   => 'nullable|string|max:200',
        ]);

        app(QueueService::class)->updateDoctorAvailability(
            $this->availDoctorId,
            $this->branchId,
            $this->availStatus,
            auth()->guard('tenant')->id(),
            $this->availNote ?: null,
        );

        $this->showAvailForm = false;
        session()->flash('success', 'Doctor status updated.');
    }

    public function cancelAvailForm(): void
    {
        $this->showAvailForm = false;
        $this->availDoctorId = 0;
    }

    private function checkPermission(string $perm): void
    {
        if (!auth()->guard('tenant')->user()?->hasPermission($perm)) {
            abort(403);
        }
    }

    public function render()
    {
        $query = QueueEntry::on('tenant')
            ->forBranchToday($this->branchId)
            ->with(['patient', 'doctor.staffProfile'])
            ->orderBy('is_priority', 'desc')
            ->orderBy('queue_number');

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterDoctorId > 0) {
            $query->where('doctor_id', $this->filterDoctorId);
        }

        $entries = $query->get();

        $doctors = Doctor::on('tenant')
            ->with(['staffProfile'])
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId)->where('is_active', true))
            ->get()
            ->map(function ($doc) {
                $avail = DoctorAvailability::on('tenant')
                    ->where('doctor_id', $doc->id)
                    ->where('branch_id', $this->branchId)
                    ->first();

                return (object) [
                    'id'          => $doc->id,
                    'name'        => $doc->staffProfile?->full_name ?? '',
                    'status'      => $avail?->status ?? 'available',
                    'statusLabel' => $avail?->status_label ?? 'Available',
                    'statusColor' => $avail?->status_color ?? 'green',
                    'statusNote'  => $avail?->status_note ?? '',
                    'queueCount'  => QueueEntry::on('tenant')
                        ->where('doctor_id', $doc->id)
                        ->forBranchToday($this->branchId)
                        ->active()
                        ->count(),
                ];
            });

        $transferDoctors = Doctor::on('tenant')
            ->with('staffProfile')
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId)->where('is_active', true))
            ->get();

        $branches = Branch::on('tenant')->where('is_active', true)->get(['id', 'name']);

        return view('livewire.tenant.reception.queue-manager', [
            'entries'         => $entries,
            'doctors'         => $doctors,
            'transferDoctors' => $transferDoctors,
            'branches'        => $branches,
        ])->layout('layouts.app');
    }
}
