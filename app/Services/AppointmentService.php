<?php

namespace App\Services;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\DoctorSchedule;
use App\Models\Tenant\DoctorUnavailability;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\FollowUp;
use App\Models\Tenant\Patient;
use App\Models\Tenant\QueueEntry;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        private readonly AuditService  $auditService,
        private readonly QueueService  $queueService,
    ) {}

    // -------------------------------------------------------------------------
    // Schedule management
    // -------------------------------------------------------------------------

    /**
     * Upsert a full 7-day schedule for a doctor+branch.
     * $days is an array keyed 0–6, each with:
     *   is_working_day, start_time, end_time, slot_duration_minutes,
     *   max_patients_per_slot, max_patients_per_day
     */
    public function saveSchedule(int $doctorId, int $branchId, array $days): void
    {
        foreach ($days as $dayOfWeek => $config) {
            DoctorSchedule::on('tenant')->updateOrCreate(
                ['doctor_id' => $doctorId, 'branch_id' => $branchId, 'day_of_week' => $dayOfWeek],
                [
                    'is_working_day'        => $config['is_working_day'] ?? false,
                    'start_time'            => $config['start_time'] ?? null,
                    'end_time'              => $config['end_time'] ?? null,
                    'slot_duration_minutes' => $config['slot_duration_minutes'] ?? 15,
                    'max_patients_per_slot' => $config['max_patients_per_slot'] ?? 1,
                    'max_patients_per_day'  => $config['max_patients_per_day'] ?? null,
                ]
            );
        }
    }

    // -------------------------------------------------------------------------
    // Slot availability
    // -------------------------------------------------------------------------

    /**
     * Returns available time slots for a doctor on a given date.
     * Each slot: ['time' => 'HH:MM', 'available' => bool, 'booked' => int, 'capacity' => int]
     */
    public function getAvailableSlots(int $doctorId, int $branchId, string $date): array
    {
        $carbonDate = CarbonImmutable::parse($date);
        $dayOfWeek  = (int) $carbonDate->dayOfWeek;

        $schedule = DoctorSchedule::on('tenant')
            ->where('doctor_id', $doctorId)
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule || !$schedule->is_working_day || !$schedule->start_time || !$schedule->end_time) {
            return [];
        }

        // Whole-day unavailability
        $unavailable = DoctorUnavailability::on('tenant')
            ->where('doctor_id', $doctorId)
            ->whereDate('unavailable_date', $date)
            ->get();

        if ($unavailable->where('from_time', null)->isNotEmpty()) {
            return [];
        }

        // Existing bookings for the day
        $bookings = Appointment::on('tenant')
            ->where('doctor_id', $doctorId)
            ->where('branch_id', $branchId)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['scheduled', 'confirmed', 'arrived', 'in_progress'])
            ->get()
            ->groupBy('appointment_time');

        $slots        = [];
        $slotDuration = $schedule->slot_duration_minutes;
        $current      = Carbon::parse($date . ' ' . $schedule->start_time);
        $end          = Carbon::parse($date . ' ' . $schedule->end_time);
        $totalBooked  = 0;

        while ($current->copy()->addMinutes($slotDuration)->lte($end)) {
            $timeKey     = $current->format('H:i');
            $bookedCount = isset($bookings[$timeKey]) ? $bookings[$timeKey]->count() : 0;

            // Check partial-day unavailability
            $blocked = $unavailable->first(function ($u) use ($current) {
                if (!$u->from_time) return false;
                $from = Carbon::parse($u->unavailable_date->format('Y-m-d') . ' ' . $u->from_time);
                $to   = Carbon::parse($u->unavailable_date->format('Y-m-d') . ' ' . $u->to_time);
                return $current->between($from, $to);
            });

            $capacity  = $blocked ? 0 : $schedule->max_patients_per_slot;
            $available = !$blocked && $bookedCount < $capacity;

            $slots[] = [
                'time'      => $timeKey,
                'available' => $available,
                'booked'    => $bookedCount,
                'capacity'  => $capacity,
            ];

            $totalBooked += $bookedCount;
            $current->addMinutes($slotDuration);
        }

        // Apply max_patients_per_day
        if ($schedule->max_patients_per_day && $totalBooked >= $schedule->max_patients_per_day) {
            return array_map(fn ($s) => array_merge($s, ['available' => false]), $slots);
        }

        return $slots;
    }

    // -------------------------------------------------------------------------
    // Booking
    // -------------------------------------------------------------------------

    public function book(
        int    $patientId,
        int    $doctorId,
        int    $branchId,
        string $date,
        string $time,
        string $appointmentType = 'scheduled',
        int    $bookedBy = 0,
        ?string $reason = null,
        ?string $notes  = null,
    ): Appointment {
        return DB::connection('tenant')->transaction(function () use (
            $patientId, $doctorId, $branchId, $date, $time, $appointmentType, $bookedBy, $reason, $notes
        ) {
            // Re-check availability inside the transaction to prevent double-booking
            $this->assertSlotAvailable($doctorId, $branchId, $date, $time);

            $schedule = DoctorSchedule::on('tenant')
                ->where('doctor_id', $doctorId)
                ->where('branch_id', $branchId)
                ->where('day_of_week', (int) CarbonImmutable::parse($date)->dayOfWeek)
                ->first();

            $duration = $schedule?->slot_duration_minutes ?? 15;

            $appointment = Appointment::on('tenant')->create([
                'appointment_number' => $this->nextAppointmentNumber(),
                'patient_id'         => $patientId,
                'doctor_id'          => $doctorId,
                'branch_id'          => $branchId,
                'appointment_date'   => $date,
                'appointment_time'   => $time,
                'duration_minutes'   => $duration,
                'appointment_type'   => $appointmentType,
                'status'             => 'scheduled',
                'reason'             => $reason,
                'notes'              => $notes,
                'booked_by'          => $bookedBy ?: null,
            ]);

            $this->auditService->logTenant(
                'create', 'appointment', $appointment->id, null,
                ['appointment_number' => $appointment->appointment_number, 'date' => $date, 'time' => $time],
                "Appointment {$appointment->appointment_number} booked for {$date} at {$time}",
            );

            return $appointment;
        });
    }

    // -------------------------------------------------------------------------
    // Workflows
    // -------------------------------------------------------------------------

    public function confirm(Appointment $appointment): Appointment
    {
        $this->assertStatus($appointment, ['scheduled'], 'confirm');
        $appointment->update(['status' => 'confirmed']);
        return $appointment->fresh();
    }

    public function markArrived(Appointment $appointment): Appointment
    {
        $this->assertStatus($appointment, ['scheduled', 'confirmed'], 'mark arrived');
        $appointment->update(['status' => 'arrived', 'arrived_at' => now()]);
        return $appointment->fresh();
    }

    /**
     * Convert an arrived appointment into a queue entry.
     */
    public function convertToQueueEntry(Appointment $appointment, int $userId): QueueEntry
    {
        if (!in_array($appointment->status, ['arrived', 'confirmed', 'scheduled'])) {
            throw new \LogicException('Only active appointments can be added to the queue.');
        }

        $queueEntry = $this->queueService->checkIn(
            patientId: $appointment->patient_id,
            branchId:  $appointment->branch_id,
            doctorId:  $appointment->doctor_id,
            userId:    $userId,
            isUrgent:  $appointment->appointment_type === 'urgent',
        );

        $appointment->update(['status' => 'in_progress']);

        return $queueEntry;
    }

    public function complete(Appointment $appointment): Appointment
    {
        $this->assertStatus($appointment, ['arrived', 'in_progress', 'scheduled', 'confirmed'], 'complete');
        $appointment->update(['status' => 'completed']);
        return $appointment->fresh();
    }

    public function cancel(Appointment $appointment, string $reason, int $userId): Appointment
    {
        if ($appointment->isCancelled()) {
            throw new \LogicException('Appointment is already cancelled.');
        }
        if ($appointment->isCompleted()) {
            throw new \LogicException('Cannot cancel a completed appointment.');
        }

        $appointment->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_by'        => $userId,
            'cancelled_at'        => now(),
        ]);

        return $appointment->fresh();
    }

    public function markNoShow(Appointment $appointment): Appointment
    {
        $this->assertStatus($appointment, ['scheduled', 'confirmed'], 'mark no-show');
        $appointment->update(['status' => 'no_show']);
        return $appointment->fresh();
    }

    public function reschedule(
        Appointment $old,
        string      $newDate,
        string      $newTime,
        int         $userId,
        ?string     $reason = null,
    ): Appointment {
        if (!$old->isActive() && !$old->isScheduled() && !$old->isConfirmed()) {
            throw new \LogicException('Only active or scheduled appointments can be rescheduled.');
        }

        return DB::connection('tenant')->transaction(function () use ($old, $newDate, $newTime, $userId, $reason) {
            $this->assertSlotAvailable($old->doctor_id, $old->branch_id, $newDate, $newTime);

            $new = $this->book(
                patientId:       $old->patient_id,
                doctorId:        $old->doctor_id,
                branchId:        $old->branch_id,
                date:            $newDate,
                time:            $newTime,
                appointmentType: $old->appointment_type,
                bookedBy:        $userId,
                reason:          $old->reason,
                notes:           $reason,
            );

            $old->update([
                'status'         => 'rescheduled',
                'rescheduled_to' => $new->id,
            ]);

            $new->update(['rescheduled_from' => $old->id]);

            return $new;
        });
    }

    // -------------------------------------------------------------------------
    // Follow-ups
    // -------------------------------------------------------------------------

    public function scheduleFollowUp(
        int    $encounterId,
        int    $patientId,
        int    $doctorId,
        int    $branchId,
        int    $createdBy,
        ?int   $followUpDays  = null,
        ?string $targetDate   = null,
        ?string $reason       = null,
        ?string $instructions = null,
    ): FollowUp {
        return FollowUp::on('tenant')->create([
            'encounter_id'   => $encounterId,
            'patient_id'     => $patientId,
            'doctor_id'      => $doctorId,
            'branch_id'      => $branchId,
            'follow_up_days' => $followUpDays,
            'target_date'    => $targetDate ?? ($followUpDays ? now()->addDays($followUpDays)->toDateString() : null),
            'reason'         => $reason,
            'instructions'   => $instructions,
            'status'         => 'pending',
            'created_by'     => $createdBy,
        ]);
    }

    public function bookFollowUp(
        FollowUp $followUp,
        string   $date,
        string   $time,
        int      $userId,
    ): Appointment {
        if (!$followUp->isPending()) {
            throw new \LogicException('Follow-up is already booked or completed.');
        }

        $appointment = $this->book(
            patientId:       $followUp->patient_id,
            doctorId:        $followUp->doctor_id,
            branchId:        $followUp->branch_id,
            date:            $date,
            time:            $time,
            appointmentType: 'follow_up',
            bookedBy:        $userId,
            reason:          $followUp->reason,
        );

        $followUp->update([
            'status'         => 'booked',
            'appointment_id' => $appointment->id,
        ]);

        return $appointment;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function assertSlotAvailable(int $doctorId, int $branchId, string $date, string $time): void
    {
        $schedule = DoctorSchedule::on('tenant')
            ->where('doctor_id', $doctorId)
            ->where('branch_id', $branchId)
            ->where('day_of_week', (int) CarbonImmutable::parse($date)->dayOfWeek)
            ->first();

        if (!$schedule || !$schedule->is_working_day) {
            throw new \InvalidArgumentException('Doctor does not work on the selected day.');
        }

        // Whole-day unavailability
        $wholeDay = DoctorUnavailability::on('tenant')
            ->where('doctor_id', $doctorId)
            ->whereDate('unavailable_date', $date)
            ->whereNull('from_time')
            ->exists();

        if ($wholeDay) {
            throw new \InvalidArgumentException('Doctor is unavailable on the selected date.');
        }

        // Count bookings for this exact slot using lockForUpdate to block concurrent double-booking
        $count = Appointment::on('tenant')
            ->where('doctor_id', $doctorId)
            ->where('branch_id', $branchId)
            ->whereDate('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', ['scheduled', 'confirmed', 'arrived', 'in_progress'])
            ->lockForUpdate()
            ->count();

        if ($count >= $schedule->max_patients_per_slot) {
            throw new \InvalidArgumentException('The selected time slot is fully booked.');
        }

        // Per-day cap
        if ($schedule->max_patients_per_day) {
            $dayCount = Appointment::on('tenant')
                ->where('doctor_id', $doctorId)
                ->where('branch_id', $branchId)
                ->whereDate('appointment_date', $date)
                ->whereIn('status', ['scheduled', 'confirmed', 'arrived', 'in_progress'])
                ->count();

            if ($dayCount >= $schedule->max_patients_per_day) {
                throw new \InvalidArgumentException('Doctor has reached the maximum appointments for this day.');
            }
        }
    }

    private function nextAppointmentNumber(): string
    {
        $dateKey = now()->format('Y-m-d');

        $seq = DB::connection('tenant')
            ->table('appointment_number_sequences')
            ->where('date_key', $dateKey)
            ->lockForUpdate()
            ->first();

        $next = $seq ? ($seq->last_number + 1) : 1;

        DB::connection('tenant')->table('appointment_number_sequences')->upsert(
            ['date_key' => $dateKey, 'last_number' => $next],
            ['date_key'],
            ['last_number']
        );

        return 'APT-' . now()->format('Ymd') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function assertStatus(Appointment $appointment, array $allowed, string $action): void
    {
        if (!in_array($appointment->status, $allowed)) {
            throw new \LogicException("Cannot {$action} an appointment with status '{$appointment->status}'.");
        }
    }
}
