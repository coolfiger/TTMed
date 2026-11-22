<?php

namespace App\Livewire\Tenant\Dashboard;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\Payment;
use App\Models\Tenant\QueueEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app-workspace')]
class TenantDashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        $today    = now()->toDateString();
        $branchId = auth()->guard('tenant')->user()?->staffProfile?->branch_id
            ?? Branch::on('tenant')->where('is_main', true)->value('id');

        $patientsToday = Encounter::on('tenant')
            ->where('encounter_date', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $queueActive = QueueEntry::on('tenant')
            ->where('queue_date', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['completed', 'cancelled', 'no_show'])
            ->count();

        $appointmentsToday = Appointment::on('tenant')
            ->where('appointment_date', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $revenueToday = Payment::on('tenant')
            ->whereDate('created_at', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $queueEntries = QueueEntry::on('tenant')
            ->with(['patient', 'doctor.staffProfile'])
            ->where('queue_date', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['completed', 'cancelled', 'no_show'])
            ->orderByDesc('is_priority')
            ->orderBy('queue_number')
            ->limit(10)
            ->get();

        $upcomingAppointments = Appointment::on('tenant')
            ->with(['patient', 'doctor.staffProfile'])
            ->where('appointment_date', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('appointment_time')
            ->limit(10)
            ->get();

        return view('livewire.tenant.dashboard.index', [
            'patientsToday'        => $patientsToday,
            'queueActive'          => $queueActive,
            'appointmentsToday'    => $appointmentsToday,
            'revenueToday'         => $revenueToday,
            'queueEntries'         => $queueEntries,
            'upcomingAppointments' => $upcomingAppointments,
        ]);
    }
}
