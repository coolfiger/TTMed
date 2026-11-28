<?php

namespace App\Services;

use App\Events\QueueUpdated;
use App\Models\Tenant\DoctorAvailability;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\ObservationEntry;
use App\Models\Tenant\ObservationOrder;
use App\Models\Tenant\ObservationRoom;
use App\Models\Tenant\Patient;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\QueueEvent;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class QueueService
{
    public function __construct(
        private readonly QueueNumberService $numberService,
        private readonly TenantContext      $tenantContext,
    ) {}

    public function checkIn(
        Patient $patient,
        int     $branchId,
        int     $userId,
        ?int    $doctorId     = null,
        string  $visitType    = 'walk_in',
        ?string $chiefComplaint = null,
        bool    $isPriority   = false,
        ?string $priorityReason = null,
    ): QueueEntry {
        $existing = QueueEntry::on('tenant')
            ->forBranchToday($branchId)
            ->active()
            ->where('patient_id', $patient->id)
            ->first();

        if ($existing) {
            throw new \RuntimeException(
                "{$patient->display_name} is already in today's queue (#{$existing->queue_number})."
            );
        }

        return DB::connection('tenant')->transaction(function () use (
            $patient, $branchId, $userId, $doctorId, $visitType, $chiefComplaint,
            $isPriority, $priorityReason
        ) {
            $encounter = Encounter::on('tenant')->create([
                'patient_id'      => $patient->id,
                'branch_id'       => $branchId,
                'doctor_id'       => $doctorId,
                'visit_type'      => $visitType,
                'status'          => 'checked_in',
                'encounter_date'  => today(),
                'checked_in_at'   => now(),
                'checked_in_by'   => $userId,
                'chief_complaint' => $chiefComplaint,
            ]);

            $queueNumber = $this->numberService->next($branchId);

            $entry = QueueEntry::on('tenant')->create([
                'encounter_id'    => $encounter->id,
                'patient_id'      => $patient->id,
                'branch_id'       => $branchId,
                'doctor_id'       => $doctorId,
                'queue_number'    => $queueNumber,
                'queue_date'      => today(),
                'visit_type'      => $visitType,
                'status'          => QueueEntry::STATUS_WAITING,
                'is_priority'     => $isPriority,
                'priority_reason' => $priorityReason,
                'checked_in_by'   => $userId,
            ]);

            $encounter->update(['queue_entry_id' => $entry->id]);

            $this->recordEvent($entry, 'checked_in', null, QueueEntry::STATUS_WAITING, $userId);
            $this->broadcast($branchId, 'entry_added', QueueUpdated::entryPayload($entry->load('patient')));

            return $entry;
        });
    }

    public function transition(QueueEntry $entry, string $newStatus, int $userId, ?string $notes = null): QueueEntry
    {
        if (!$entry->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from [{$entry->status}] to [{$newStatus}]."
            );
        }

        $oldStatus = $entry->status;

        DB::connection('tenant')->transaction(function () use ($entry, $newStatus, $oldStatus, $userId, $notes) {
            $entry->status = $newStatus;

            if ($newStatus === QueueEntry::STATUS_WITH_DOCTOR) {
                $entry->called_at = now();
                $entry->encounter?->update(['seen_by_doctor_at' => now()]);
            }

            if ($newStatus === QueueEntry::STATUS_WITH_NURSE) {
                $entry->called_at = now();
                $entry->encounter?->update(['seen_by_nurse_at' => now()]);
            }

            if (in_array($newStatus, [QueueEntry::STATUS_COMPLETED, QueueEntry::STATUS_CANCELLED, QueueEntry::STATUS_NO_SHOW])) {
                $entry->encounter?->update([
                    'status'       => $newStatus === QueueEntry::STATUS_COMPLETED ? 'completed' : $newStatus,
                    'completed_at' => now(),
                ]);
            }

            $entry->save();

            $this->recordEvent($entry, 'status_changed', $oldStatus, $newStatus, $userId, $notes);
        });

        $this->broadcast($entry->branch_id, 'entry_updated', QueueUpdated::entryPayload($entry->load('patient')));

        return $entry;
    }

    public function assignDoctor(QueueEntry $entry, int $doctorId, int $userId): QueueEntry
    {
        $old = $entry->doctor_id;
        $entry->doctor_id = $doctorId;
        $entry->save();

        $entry->encounter?->update(['doctor_id' => $doctorId]);

        $this->recordEvent($entry, 'doctor_assigned', null, null, $userId,
            "Doctor changed from #{$old} to #{$doctorId}"
        );
        $this->broadcast($entry->branch_id, 'entry_updated', QueueUpdated::entryPayload($entry->load('patient')));

        return $entry;
    }

    public function transferToDoctor(QueueEntry $entry, int $newDoctorId, int $userId, ?string $notes = null): QueueEntry
    {
        return $this->assignDoctor($entry, $newDoctorId, $userId);
    }

    public function setPriority(QueueEntry $entry, bool $isPriority, ?string $reason, int $userId): QueueEntry
    {
        $entry->is_priority     = $isPriority;
        $entry->priority_reason = $isPriority ? $reason : null;
        $entry->save();

        $this->recordEvent($entry, $isPriority ? 'marked_priority' : 'removed_priority', null, null, $userId, $reason);
        $this->broadcast($entry->branch_id, 'entry_updated', QueueUpdated::entryPayload($entry->load('patient')));

        return $entry;
    }

    public function updateDoctorAvailability(int $doctorId, int $branchId, string $status, int $userId, ?string $note = null): DoctorAvailability
    {
        $availability = DoctorAvailability::on('tenant')->updateOrCreate(
            ['doctor_id' => $doctorId, 'branch_id' => $branchId],
            ['status' => $status, 'status_note' => $note, 'updated_by' => $userId]
        );

        $this->broadcast($branchId, 'doctor_availability_changed', [
            'doctorId' => $doctorId,
            'status'   => $status,
        ]);

        return $availability;
    }

    // ── Observation ──────────────────────────────────────────────────────

    public function dispatchToObservation(
        QueueEntry $entry,
        int        $userId,
        string     $reason,
        ?string    $treatmentNotes = null,
        ?int       $roomId         = null,
    ): ObservationOrder {
        if (!$entry->canTransitionTo(QueueEntry::STATUS_IN_OBSERVATION)) {
            throw new \InvalidArgumentException(
                "Cannot dispatch to observation from status [{$entry->status}]."
            );
        }

        if ($roomId) {
            $room = ObservationRoom::on('tenant')
                ->where('branch_id', $entry->branch_id)
                ->where('id', $roomId)
                ->where('is_active', true)
                ->first();

            if (!$room) {
                throw new \InvalidArgumentException('Selected room not found.');
            }

            if ($room->status === ObservationRoom::STATUS_OCCUPIED) {
                throw new \InvalidArgumentException("Room \"{$room->name}\" is already occupied.");
            }
        }

        return DB::connection('tenant')->transaction(function () use ($entry, $userId, $reason, $treatmentNotes, $roomId) {
            $room = $roomId
                ? ObservationRoom::on('tenant')->find($roomId)
                : null;

            $order = ObservationOrder::on('tenant')->create([
                'encounter_id'    => $entry->encounter_id,
                'patient_id'      => $entry->patient_id,
                'branch_id'       => $entry->branch_id,
                'room_id'         => $roomId,
                'ordered_by'      => $userId,
                'reason'          => $reason,
                'treatment_notes' => $treatmentNotes,
                'room_name'       => $room?->name,
                'status'          => ObservationOrder::STATUS_ACTIVE,
                'started_at'      => now(),
            ]);

            if ($room) {
                $room->status = ObservationRoom::STATUS_OCCUPIED;
                $room->save();
            }

            $oldStatus = $entry->status;
            $entry->status    = QueueEntry::STATUS_IN_OBSERVATION;
            $entry->called_at = now();
            $entry->save();

            $this->recordEvent($entry, 'dispatched_to_observation', $oldStatus, QueueEntry::STATUS_IN_OBSERVATION, $userId, $reason);
            $this->broadcast($entry->branch_id, 'entry_updated', QueueUpdated::entryPayload($entry->load('patient')));

            return $order;
        });
    }

    public function addObservationEntry(
        ObservationOrder $order,
        int              $userId,
        ?string          $notes       = null,
        ?int             $bpSystolic  = null,
        ?int             $bpDiastolic = null,
        ?int             $pulse       = null,
        ?float           $tempCelsius = null,
        ?int             $spo2        = null,
    ): ObservationEntry {
        return ObservationEntry::on('tenant')->create([
            'observation_order_id' => $order->id,
            'encounter_id'         => $order->encounter_id,
            'patient_id'           => $order->patient_id,
            'recorded_by'          => $userId,
            'notes'                => $notes,
            'bp_systolic'          => $bpSystolic,
            'bp_diastolic'         => $bpDiastolic,
            'pulse'                => $pulse,
            'temp_celsius'         => $tempCelsius,
            'spo2'                 => $spo2,
            'occurred_at'          => now(),
        ]);
    }

    public function markReadyForObservationReview(QueueEntry $order_entry, int $userId, ?string $notes = null): QueueEntry
    {
        if (!$order_entry->canTransitionTo(QueueEntry::STATUS_OBSERVATION_REVIEW)) {
            throw new \InvalidArgumentException(
                "Cannot mark for review from status [{$order_entry->status}]."
            );
        }

        $old = $order_entry->status;
        $order_entry->status = QueueEntry::STATUS_OBSERVATION_REVIEW;
        $order_entry->save();

        // Free the room when patient leaves for doctor review
        $this->freeRoomForEncounter($order_entry->encounter_id);

        $this->recordEvent($order_entry, 'observation_ready_for_review', $old, QueueEntry::STATUS_OBSERVATION_REVIEW, $userId, $notes);
        $this->broadcast($order_entry->branch_id, 'entry_updated', QueueUpdated::entryPayload($order_entry->load('patient')));

        return $order_entry;
    }

    public function completeObservation(ObservationOrder $order, int $userId): void
    {
        $order->status       = ObservationOrder::STATUS_COMPLETED;
        $order->completed_at = now();
        $order->completed_by = $userId;
        $order->save();

        $this->freeRoom($order);
    }

    private function freeRoomForEncounter(int $encounterId): void
    {
        $order = ObservationOrder::on('tenant')
            ->where('encounter_id', $encounterId)
            ->where('status', ObservationOrder::STATUS_ACTIVE)
            ->first();

        if ($order) {
            $this->freeRoom($order);
        }
    }

    private function freeRoom(ObservationOrder $order): void
    {
        if ($order->room_id) {
            ObservationRoom::on('tenant')
                ->where('id', $order->room_id)
                ->update(['status' => ObservationRoom::STATUS_VACANT]);
        }
    }

    private function recordEvent(
        QueueEntry $entry,
        string     $eventType,
        ?string    $fromStatus,
        ?string    $toStatus,
        int        $performedBy,
        ?string    $notes = null,
    ): void {
        QueueEvent::on('tenant')->create([
            'queue_entry_id' => $entry->id,
            'event_type'     => $eventType,
            'from_status'    => $fromStatus,
            'to_status'      => $toStatus,
            'performed_by'   => $performedBy,
            'notes'          => $notes,
            'occurred_at'    => now(),
        ]);
    }

    private function broadcast(int $branchId, string $action, array $payload): void
    {
        $tenantUuid = $this->tenantContext->get()?->uuid;

        if ($tenantUuid) {
            broadcast(new QueueUpdated($tenantUuid, $branchId, $action, $payload));
        }
    }
}
