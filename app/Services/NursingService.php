<?php

namespace App\Services;

use App\Models\Tenant\Encounter;
use App\Models\Tenant\NursingAssessment;
use App\Models\Tenant\NursingProcedure;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\VitalSign;
use Illuminate\Support\Facades\DB;

class NursingService
{
    public function __construct(
        private readonly QueueService $queueService,
    ) {}

    // -------------------------------------------------------------------------
    // Vitals
    // -------------------------------------------------------------------------

    public function recordVitals(array $data, int $recordedBy): VitalSign
    {
        // Update today's existing record for this encounter so the doctor always
        // sees one complete vitals row, not a separate row per auto-saved field.
        $existing = VitalSign::on('tenant')
            ->where('encounter_id', $data['encounter_id'])
            ->whereDate('recorded_at', today())
            ->orderByDesc('recorded_at')
            ->first();

        if ($existing) {
            $existing->update(array_merge($data, [
                'recorded_by' => $recordedBy,
                'recorded_at' => now(),
            ]));
            return $existing->fresh();
        }

        return VitalSign::on('tenant')->create(array_merge($data, [
            'recorded_by' => $recordedBy,
            'recorded_at' => now(),
        ]));
    }

    // -------------------------------------------------------------------------
    // Assessment
    // -------------------------------------------------------------------------

    public function getOrCreateAssessment(int $encounterId, ?int $patientId = null): NursingAssessment
    {
        $defaults = [
            'allergies_reviewed'   => false,
            'medications_reviewed' => false,
            'status'               => 'in_progress',
        ];

        if ($patientId) {
            $defaults['patient_id'] = $patientId;
        }

        return NursingAssessment::on('tenant')->firstOrCreate(
            ['encounter_id' => $encounterId],
            $defaults
        );
    }

    public function updateAssessment(NursingAssessment $assessment, array $data): NursingAssessment
    {
        $assessment->update($data);

        if ($assessment->fresh()->isComplete()) {
            $assessment->update(['status' => 'completed']);
        }

        return $assessment->fresh();
    }

    // -------------------------------------------------------------------------
    // Procedures
    // -------------------------------------------------------------------------

    public function addProcedure(
        int     $encounterId,
        string  $procedureType,
        string  $procedureName,
        int     $performedBy,
        ?string $details     = null,
        ?string $outcome     = null,
        ?string $site        = null,
        string  $role        = 'nurse',
    ): NursingProcedure {
        $patientId = Encounter::on('tenant')->where('id', $encounterId)->value('patient_id');

        return NursingProcedure::on('tenant')->create([
            'encounter_id'      => $encounterId,
            'patient_id'        => $patientId,
            'procedure_type'    => $procedureType,
            'procedure_name'    => $procedureName,
            'performed_by'      => $performedBy,
            'performed_by_role' => $role,
            'details'           => $details,
            'outcome'           => $outcome,
            'site'              => $site,
            'performed_at'      => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Queue transition: nursing complete → awaiting_doctor
    // -------------------------------------------------------------------------

    public function completeNursingAndTransition(
        NursingAssessment $assessment,
        QueueEntry        $queueEntry,
        int               $userId,
        ?string           $notes = null,
    ): QueueEntry {
        return DB::connection('tenant')->transaction(function () use ($assessment, $queueEntry, $userId, $notes) {
            if (!$assessment->isComplete()) {
                throw new \InvalidArgumentException('Nursing assessment is not complete.');
            }

            $assessment->update(['status' => 'completed']);

            return $this->queueService->transition($queueEntry, 'awaiting_doctor', $userId, $notes);
        });
    }
}
