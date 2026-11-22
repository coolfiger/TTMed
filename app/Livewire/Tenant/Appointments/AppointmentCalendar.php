<?php

namespace App\Livewire\Tenant\Appointments;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Doctor;
use App\Models\Tenant\Patient;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AppointmentCalendar extends Component
{
    // Filters
    public string $selectedDate  = '';
    public int    $selectedDoctor = 0;
    public int    $branchId       = 0;

    // Available slots
    public array $slots = [];

    // Booking form
    public bool   $showBookingForm = false;
    public string $bookingTime     = '';
    public int    $bookingPatientId = 0;
    public string $patientSearch   = '';
    public array  $patientResults  = [];
    public string $bookingReason   = '';
    public string $bookingType     = 'scheduled';
    public string $bookingNotes    = '';

    // Cancel / reschedule
    public bool   $showCancelModal    = false;
    public bool   $showRescheduleModal = false;
    public int    $actionAppointmentId = 0;
    public string $cancelReason        = '';
    public string $rescheduleDate      = '';
    public string $rescheduleTime      = '';

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
        $this->selectedDate   = now()->toDateString();
        $this->branchId       = session('branch_id', 0);
    }

    // -------------------------------------------------------------------------
    // Slots
    // -------------------------------------------------------------------------

    public function loadSlots(): void
    {
        if (!$this->selectedDoctor || !$this->selectedDate) {
            $this->slots = [];
            return;
        }

        $this->slots = $this->appointmentService->getAvailableSlots(
            $this->selectedDoctor,
            $this->branchId,
            $this->selectedDate,
        );
    }

    public function selectSlot(string $time): void
    {
        $this->checkPermission('appointments.create');
        $this->bookingTime    = $time;
        $this->showBookingForm = true;
        $this->resetBookingForm(keepTime: true);
    }

    // -------------------------------------------------------------------------
    // Patient search
    // -------------------------------------------------------------------------

    public function searchPatients(): void
    {
        if (strlen(trim($this->patientSearch)) < 2) {
            $this->patientResults = [];
            return;
        }

        $this->patientResults = Patient::on('tenant')
            ->search($this->patientSearch)
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'id'   => $p->id,
                'name' => $p->full_name,
                'dob'  => $p->date_of_birth?->format('d M Y'),
                'number' => $p->patient_number,
            ])
            ->toArray();
    }

    public function selectPatient(int $id): void
    {
        $patient = Patient::on('tenant')->find($id);
        if (!$patient) return;
        $this->bookingPatientId = $patient->id;
        $this->patientSearch    = $patient->full_name;
        $this->patientResults   = [];
    }

    // -------------------------------------------------------------------------
    // Book
    // -------------------------------------------------------------------------

    public function book(): void
    {
        $this->checkPermission('appointments.create');

        $this->validate([
            'bookingPatientId' => 'required|integer|min:1',
            'bookingTime'      => 'required|string',
            'bookingType'      => 'required|string',
        ]);

        try {
            $this->appointmentService->book(
                patientId:       $this->bookingPatientId,
                doctorId:        $this->selectedDoctor,
                branchId:        $this->branchId,
                date:            $this->selectedDate,
                time:            $this->bookingTime,
                appointmentType: $this->bookingType,
                bookedBy:        Auth::guard('tenant')->id(),
                reason:          $this->bookingReason ?: null,
                notes:           $this->bookingNotes ?: null,
            );

            $this->successMessage  = 'Appointment booked.';
            $this->showBookingForm  = false;
            $this->resetBookingForm();
            $this->loadSlots();
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Cancel
    // -------------------------------------------------------------------------

    public function openCancelModal(int $id): void
    {
        $this->checkPermission('appointments.manage');
        $this->actionAppointmentId = $id;
        $this->cancelReason        = '';
        $this->showCancelModal     = true;
    }

    public function cancelAppointment(): void
    {
        $this->checkPermission('appointments.manage');

        $this->validate(['cancelReason' => 'required|string|min:3|max:500']);

        $appointment = Appointment::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($this->actionAppointmentId);

        try {
            $this->appointmentService->cancel(
                $appointment,
                $this->cancelReason,
                Auth::guard('tenant')->id(),
            );
            $this->successMessage  = 'Appointment cancelled.';
            $this->showCancelModal = false;
            $this->loadSlots();
        } catch (\LogicException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // No-show
    // -------------------------------------------------------------------------

    public function markNoShow(int $id): void
    {
        $this->checkPermission('appointments.manage');

        $appointment = Appointment::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($id);

        try {
            $this->appointmentService->markNoShow($appointment);
            $this->successMessage = 'Marked as no-show.';
            $this->loadSlots();
        } catch (\LogicException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Reschedule
    // -------------------------------------------------------------------------

    public function openRescheduleModal(int $id): void
    {
        $this->checkPermission('appointments.manage');
        $this->actionAppointmentId  = $id;
        $this->rescheduleDate       = '';
        $this->rescheduleTime       = '';
        $this->showRescheduleModal  = true;
    }

    public function reschedule(): void
    {
        $this->checkPermission('appointments.manage');

        $this->validate([
            'rescheduleDate' => 'required|date|after_or_equal:today',
            'rescheduleTime' => 'required|string',
        ]);

        $appointment = Appointment::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($this->actionAppointmentId);

        try {
            $this->appointmentService->reschedule(
                $appointment,
                $this->rescheduleDate,
                $this->rescheduleTime,
                Auth::guard('tenant')->id(),
            );
            $this->successMessage       = 'Appointment rescheduled.';
            $this->showRescheduleModal  = false;
            $this->loadSlots();
        } catch (\InvalidArgumentException|\LogicException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Convert to queue
    // -------------------------------------------------------------------------

    public function addToQueue(int $id): void
    {
        $this->checkPermission('queue.manage');

        $appointment = Appointment::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($id);

        try {
            $this->appointmentService->convertToQueueEntry($appointment, Auth::guard('tenant')->id());
            $this->successMessage = 'Patient added to queue.';
            $this->loadSlots();
        } catch (\LogicException|\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render()
    {
        $doctors = Doctor::on('tenant')
            ->with('staffProfile')
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId)->whereNull('deleted_at'))
            ->get();

        $appointments = collect();
        if ($this->selectedDoctor && $this->selectedDate) {
            $appointments = Appointment::on('tenant')
                ->with(['patient'])
                ->where('doctor_id', $this->selectedDoctor)
                ->where('branch_id', $this->branchId)
                ->whereDate('appointment_date', $this->selectedDate)
                ->orderBy('appointment_time')
                ->get();
        }

        return view('livewire.tenant.appointments.appointment-calendar', [
            'doctors'      => $doctors,
            'appointments' => $appointments,
        ])->layout('layouts.app-workspace');
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }

    private function resetBookingForm(bool $keepTime = false): void
    {
        $this->bookingPatientId = 0;
        $this->patientSearch    = '';
        $this->patientResults   = [];
        $this->bookingReason    = '';
        $this->bookingType      = 'scheduled';
        $this->bookingNotes     = '';
        if (!$keepTime) {
            $this->bookingTime = '';
        }
    }
}
