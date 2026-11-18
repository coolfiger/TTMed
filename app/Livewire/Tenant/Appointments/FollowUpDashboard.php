<?php

namespace App\Livewire\Tenant\Appointments;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\FollowUp;
use App\Services\AppointmentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FollowUpDashboard extends Component
{
    public int    $branchId       = 0;
    public string $filterStatus   = 'pending';
    public int    $filterDoctorId = 0;

    // Book slot form
    public bool   $showBookForm   = false;
    public int    $activeFollowUpId = 0;
    public string $bookDate       = '';
    public string $bookTime       = '';
    public array  $availableSlots = [];

    // UI
    public string $successMessage = '';
    public string $errorMessage   = '';

    protected AppointmentService $appointmentService;

    public function boot(AppointmentService $appointmentService): void
    {
        $this->appointmentService = $appointmentService;
    }

    public function mount(): void
    {
        $this->checkPermission('appointments.view');
        $this->branchId       = session('branch_id', 0);
        $this->filterDoctorId = Auth::guard('tenant')->user()?->id ?? 0;
    }

    // -------------------------------------------------------------------------
    // Load slots for follow-up booking
    // -------------------------------------------------------------------------

    public function openBookForm(int $followUpId): void
    {
        $this->checkPermission('appointments.create');
        $this->activeFollowUpId = $followUpId;
        $this->bookDate         = now()->addDay()->toDateString();
        $this->availableSlots   = [];
        $this->showBookForm     = true;
    }

    public function loadSlots(): void
    {
        $followUp = FollowUp::on('tenant')->find($this->activeFollowUpId);
        if (!$followUp || !$this->bookDate) {
            $this->availableSlots = [];
            return;
        }

        $this->availableSlots = $this->appointmentService->getAvailableSlots(
            $followUp->doctor_id,
            $followUp->branch_id,
            $this->bookDate,
        );
    }

    public function bookFollowUp(): void
    {
        $this->checkPermission('appointments.create');

        $this->validate([
            'bookDate' => 'required|date|after_or_equal:today',
            'bookTime' => 'required|string',
        ]);

        $followUp = FollowUp::on('tenant')
            ->where('doctor_id', $this->filterDoctorId ?: null)
            ->findOrFail($this->activeFollowUpId);

        try {
            $this->appointmentService->bookFollowUp(
                $followUp,
                $this->bookDate,
                $this->bookTime,
                Auth::guard('tenant')->id(),
            );
            $this->successMessage = 'Follow-up appointment booked.';
            $this->showBookForm   = false;
        } catch (\InvalidArgumentException|\LogicException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render()
    {
        $query = FollowUp::on('tenant')
            ->with(['patient', 'doctor.staffProfile', 'appointment'])
            ->where('branch_id', $this->branchId);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterDoctorId) {
            $query->where('doctor_id', $this->filterDoctorId);
        }

        $followUps = $query->orderBy('target_date')->get();

        return view('livewire.tenant.appointments.follow-up-dashboard', [
            'followUps' => $followUps,
        ])->layout('layouts.app');
    }

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }
}
