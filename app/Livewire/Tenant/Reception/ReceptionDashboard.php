<?php

namespace App\Livewire\Tenant\Reception;

use App\Events\QueueUpdated;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Doctor;
use App\Models\Tenant\DoctorAvailability;
use App\Models\Tenant\Patient;
use App\Models\Tenant\QueueEntry;
use App\Services\AuditService;
use App\Services\QueueService;
use App\Tenancy\TenantContext;
use Livewire\Attributes\On;
use Livewire\Component;

class ReceptionDashboard extends Component
{
    public int    $branchId     = 0;
    public string $patientSearch = '';
    public array  $searchResults = [];
    public bool   $showCheckinForm = false;

    // Check-in form state
    public int    $selectedPatientId = 0;
    public string $selectedPatientName = '';
    public int    $selectedDoctorId  = 0;
    public string $visitType         = 'walk_in';
    public string $chiefComplaint    = '';
    public bool   $isPriority        = false;
    public string $priorityReason    = '';

    // Queue display
    public array  $queueEntries      = [];
    public array  $doctorStatuses    = [];

    public function mount(): void
    {
        $user = auth()->guard('tenant')->user();
        $this->branchId = $user?->staffProfile?->branch_id ?? Branch::on('tenant')->where('is_main', true)->value('id') ?? 0;
        $this->loadQueue();
        $this->loadDoctorStatuses();
    }

    public function updatedPatientSearch(): void
    {
        if (strlen($this->patientSearch) < 2) {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = Patient::on('tenant')
            ->search($this->patientSearch)
            ->where('is_active', true)
            ->limit(8)
            ->get(['id', 'patient_number', 'first_name', 'last_name', 'date_of_birth', 'phone_primary'])
            ->map(fn ($p) => [
                'id'            => $p->id,
                'display_name'  => $p->display_name,
                'patient_number' => $p->patient_number,
                'dob'           => $p->date_of_birth?->format('d/m/Y'),
                'phone'         => $p->phone_primary,
            ])
            ->toArray();
    }

    public function selectPatient(int $id): void
    {
        $patient = Patient::on('tenant')->find($id);

        if (!$patient) {
            return;
        }

        $this->selectedPatientId   = $patient->id;
        $this->selectedPatientName = $patient->display_name . ' (' . $patient->patient_number . ')';
        $this->patientSearch       = '';
        $this->searchResults       = [];
        $this->showCheckinForm     = true;
    }

    public function checkIn(): void
    {
        $this->checkPermission('queue.checkin');

        $this->validate([
            'selectedPatientId' => 'required|integer|min:1',
            'visitType'         => 'required|in:walk_in,appointment,emergency,follow_up',
            'isPriority'        => 'boolean',
            'priorityReason'    => 'nullable|string|max:200',
            'chiefComplaint'    => 'nullable|string|max:1000',
        ]);

        $patient = Patient::on('tenant')->find($this->selectedPatientId);

        if (!$patient) {
            $this->addError('selectedPatientId', 'Patient not found.');
            return;
        }

        $userId = auth()->guard('tenant')->id();

        $entry = app(QueueService::class)->checkIn(
            patient:         $patient,
            branchId:        $this->branchId,
            userId:          $userId,
            doctorId:        $this->selectedDoctorId ?: null,
            visitType:       $this->visitType,
            chiefComplaint:  $this->chiefComplaint ?: null,
            isPriority:      $this->isPriority,
            priorityReason:  $this->priorityReason ?: null,
        );

        app(AuditService::class)->log(
            'queue.checkin',
            "Patient {$patient->display_name} checked in — Queue #{$entry->queue_number}",
            ['queue_entry_id' => $entry->id, 'patient_id' => $patient->id]
        );

        $this->resetCheckinForm();
        $this->loadQueue();
        session()->flash('success', "Patient {$patient->display_name} checked in as Queue #{$entry->queue_number}.");
    }

    public function cancelCheckin(): void
    {
        $this->resetCheckinForm();
    }

    private function resetCheckinForm(): void
    {
        $this->selectedPatientId   = 0;
        $this->selectedPatientName = '';
        $this->selectedDoctorId    = 0;
        $this->visitType           = 'walk_in';
        $this->chiefComplaint      = '';
        $this->isPriority          = false;
        $this->priorityReason      = '';
        $this->showCheckinForm     = false;
    }

    public function loadQueue(): void
    {
        $this->queueEntries = QueueEntry::on('tenant')
            ->forBranchToday($this->branchId)
            ->active()
            ->with(['patient', 'doctor.staffProfile'])
            ->orderBy('is_priority', 'desc')
            ->orderBy('queue_number')
            ->get()
            ->map(fn ($e) => [
                'id'          => $e->id,
                'number'      => $e->queue_number,
                'patientName' => $e->patient?->display_name ?? '',
                'doctorName'  => $e->doctor?->staffProfile?->full_name ?? 'Unassigned',
                'doctorId'    => $e->doctor_id,
                'status'      => $e->status,
                'statusLabel' => $e->status_label,
                'isPriority'  => $e->is_priority,
                'visitType'   => $e->visit_type,
            ])
            ->toArray();
    }

    public function loadDoctorStatuses(): void
    {
        $this->doctorStatuses = Doctor::on('tenant')
            ->with(['staffProfile', 'staffProfile.branch'])
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId)->where('is_active', true))
            ->get()
            ->map(function ($doc) {
                $avail = DoctorAvailability::on('tenant')
                    ->where('doctor_id', $doc->id)
                    ->where('branch_id', $this->branchId)
                    ->first();

                return [
                    'id'          => $doc->id,
                    'name'        => $doc->staffProfile?->full_name ?? '',
                    'specialty'   => $doc->specialty,
                    'status'      => $avail?->status ?? 'available',
                    'statusLabel' => $avail?->status_label ?? 'Available',
                    'statusNote'  => $avail?->status_note ?? '',
                    'queueCount'  => QueueEntry::on('tenant')
                        ->where('doctor_id', $doc->id)
                        ->forBranchToday($this->branchId)
                        ->active()
                        ->count(),
                ];
            })
            ->toArray();
    }

    public function switchBranch(int $id): void
    {
        $branch = Branch::on('tenant')->find($id);
        if ($branch) {
            $this->branchId = $id;
            $this->loadQueue();
            $this->loadDoctorStatuses();
        }
    }

    private function checkPermission(string $perm): void
    {
        $user = auth()->guard('tenant')->user();
        if (!$user?->hasPermission($perm)) {
            abort(403);
        }
    }

    public function render()
    {
        $branches      = Branch::on('tenant')->where('is_active', true)->get(['id', 'name']);
        $activeDoctors = Doctor::on('tenant')
            ->with('staffProfile')
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId)->where('is_active', true))
            ->get();

        return view('livewire.tenant.reception.reception-dashboard', [
            'branches'      => $branches,
            'activeDoctors' => $activeDoctors,
        ])->layout('layouts.app-workspace');
    }
}
