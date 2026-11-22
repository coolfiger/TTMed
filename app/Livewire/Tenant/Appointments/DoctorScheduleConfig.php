<?php

namespace App\Livewire\Tenant\Appointments;

use App\Models\Tenant\Doctor;
use App\Models\Tenant\DoctorSchedule;
use App\Models\Tenant\DoctorUnavailability;
use App\Services\AppointmentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DoctorScheduleConfig extends Component
{
    public int    $doctorId  = 0;
    public int    $branchId  = 0;
    public string $doctorName = '';

    // 7-day schedule form: keyed by day_of_week (0–6)
    public array $days = [];

    // Unavailability form
    public bool   $showUnavailForm  = false;
    public string $unavailDate      = '';
    public string $unavailFromTime  = '';
    public string $unavailToTime    = '';
    public string $unavailReason    = '';
    public bool   $unavailWholeDay  = true;

    // UI
    public string $successMessage = '';
    public string $errorMessage   = '';

    protected AppointmentService $appointmentService;

    public function boot(AppointmentService $appointmentService): void
    {
        $this->appointmentService = $appointmentService;
    }

    public function mount(int $doctorId): void
    {
        $this->checkPermission('appointments.manage');

        $this->doctorId  = $doctorId;
        $this->branchId  = session('branch_id', 0);

        $doctor = Doctor::on('tenant')->with('staffProfile')->findOrFail($doctorId);
        $this->doctorName = $doctor->staffProfile?->full_name ?? "Doctor #{$doctorId}";

        $this->loadSchedule();
    }

    private function loadSchedule(): void
    {
        $existing = DoctorSchedule::on('tenant')
            ->where('doctor_id', $this->doctorId)
            ->where('branch_id', $this->branchId)
            ->get()
            ->keyBy('day_of_week');

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $this->days = [];
        for ($i = 0; $i <= 6; $i++) {
            $row = $existing->get($i);
            $this->days[$i] = [
                'name'                  => $dayNames[$i],
                'is_working_day'        => $row?->is_working_day ?? ($i >= 1 && $i <= 5),
                'start_time'            => $row?->start_time ?? '08:00',
                'end_time'              => $row?->end_time ?? '17:00',
                'slot_duration_minutes' => $row?->slot_duration_minutes ?? 15,
                'max_patients_per_slot' => $row?->max_patients_per_slot ?? 1,
                'max_patients_per_day'  => $row?->max_patients_per_day ?? '',
            ];
        }
    }

    public function saveSchedule(): void
    {
        $this->checkPermission('appointments.manage');

        $this->appointmentService->saveSchedule($this->doctorId, $this->branchId, $this->days);
        $this->successMessage = 'Schedule saved.';
    }

    // -------------------------------------------------------------------------
    // Unavailability
    // -------------------------------------------------------------------------

    public function addUnavailability(): void
    {
        $this->checkPermission('appointments.manage');

        $this->validate([
            'unavailDate'     => 'required|date',
            'unavailFromTime' => 'nullable|required_if:unavailWholeDay,false',
            'unavailToTime'   => 'nullable|required_if:unavailWholeDay,false|after:unavailFromTime',
        ]);

        DoctorUnavailability::on('tenant')->create([
            'doctor_id'        => $this->doctorId,
            'branch_id'        => $this->branchId,
            'unavailable_date' => $this->unavailDate,
            'from_time'        => $this->unavailWholeDay ? null : $this->unavailFromTime,
            'to_time'          => $this->unavailWholeDay ? null : $this->unavailToTime,
            'reason'           => $this->unavailReason ?: null,
        ]);

        $this->successMessage   = 'Unavailability added.';
        $this->showUnavailForm  = false;
        $this->unavailDate      = '';
        $this->unavailFromTime  = '';
        $this->unavailToTime    = '';
        $this->unavailReason    = '';
        $this->unavailWholeDay  = true;
    }

    public function removeUnavailability(int $id): void
    {
        $this->checkPermission('appointments.manage');

        DoctorUnavailability::on('tenant')
            ->where('doctor_id', $this->doctorId)
            ->findOrFail($id)
            ->delete();

        $this->successMessage = 'Unavailability removed.';
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render()
    {
        $upcoming = DoctorUnavailability::on('tenant')
            ->where('doctor_id', $this->doctorId)
            ->where('branch_id', $this->branchId)
            ->where('unavailable_date', '>=', now()->toDateString())
            ->orderBy('unavailable_date')
            ->limit(20)
            ->get();

        return view('livewire.tenant.appointments.doctor-schedule-config', [
            'upcoming' => $upcoming,
        ])->layout('layouts.app-workspace');
    }

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }
}
