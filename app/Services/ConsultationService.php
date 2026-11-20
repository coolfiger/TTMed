<?php

namespace App\Services;

use App\Models\Tenant\ConsultationNote;
use App\Models\Tenant\Diagnosis;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\Icd10Code;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\Referral;
use Illuminate\Support\Facades\DB;

class ConsultationService
{
    public function __construct(
        private readonly QueueService  $queueService,
        private readonly AuditService  $auditService,
    ) {}

    // -------------------------------------------------------------------------
    // Consultation note
    // -------------------------------------------------------------------------

    public function getOrCreateNote(int $encounterId, int $doctorId): ConsultationNote
    {
        $encounter = Encounter::on('tenant')->findOrFail($encounterId);

        return ConsultationNote::on('tenant')->firstOrCreate(
            ['encounter_id' => $encounterId],
            [
                'patient_id' => $encounter->patient_id,
                'doctor_id'  => $doctorId,
            ]
        );
    }

    public function updateNote(ConsultationNote $note, array $data): ConsultationNote
    {
        if ($note->isFinalized()) {
            throw new \LogicException('Cannot edit a finalized consultation note.');
        }

        $allowed = [
            'subjective', 'objective', 'assessment', 'plan',
            'examination_findings', 'investigations_ordered',
            'follow_up_instructions', 'follow_up_days',
        ];

        $note->update(array_intersect_key($data, array_flip($allowed)));

        return $note->fresh();
    }

    public function finalizeNote(ConsultationNote $note, int $userId): ConsultationNote
    {
        if ($note->isFinalized()) {
            throw new \LogicException('Consultation note is already finalized.');
        }

        if (!$note->hasContent()) {
            throw new \InvalidArgumentException('Cannot finalize an empty consultation note.');
        }

        return DB::connection('tenant')->transaction(function () use ($note, $userId) {
            $hash = $note->computeContentHash();

            $note->update([
                'is_finalized'  => true,
                'finalized_by'  => $userId,
                'finalized_at'  => now(),
                'content_hash'  => $hash,
            ]);

            $this->auditService->logTenant(
                'finalize',
                'consultation_note',
                $note->id,
                null,
                ['content_hash' => $hash],
                "Consultation note finalized for encounter #{$note->encounter_id}",
            );

            return $note->fresh();
        });
    }

    // -------------------------------------------------------------------------
    // Diagnoses
    // -------------------------------------------------------------------------

    public function addDiagnosis(
        int     $encounterId,
        int     $patientId,
        int     $diagnosedBy,
        string  $diagnosisType,
        string  $certainty,
        string  $diagnosisText,
        ?int    $icd10CodeId   = null,
        ?string $icd10Code     = null,
        ?string $notes         = null,
    ): Diagnosis {
        if ($icd10CodeId && !$icd10Code) {
            $icd = Icd10Code::on('tenant')->find($icd10CodeId);
            $icd10Code = $icd?->code;
        }

        return Diagnosis::on('tenant')->create([
            'encounter_id'   => $encounterId,
            'patient_id'     => $patientId,
            'icd10_code_id'  => $icd10CodeId,
            'icd10_code'     => $icd10Code,
            'diagnosis_text' => $diagnosisText,
            'diagnosis_type' => $diagnosisType,
            'certainty'      => $certainty,
            'notes'          => $notes,
            'diagnosed_by'   => $diagnosedBy,
        ]);
    }

    public function removeDiagnosis(Diagnosis $diagnosis): void
    {
        $diagnosis->delete();
    }

    // -------------------------------------------------------------------------
    // Referrals
    // -------------------------------------------------------------------------

    public function addReferral(
        int     $encounterId,
        int     $patientId,
        int     $referredBy,
        string  $referredToName,
        string  $urgency        = 'routine',
        string  $reason         = '',
        ?string $specialty      = null,
        ?string $facility       = null,
        ?string $clinicalSummary = null,
        ?string $instructions   = null,
    ): Referral {
        return Referral::on('tenant')->create([
            'encounter_id'            => $encounterId,
            'patient_id'              => $patientId,
            'referred_by'             => $referredBy,
            'referred_to_name'        => $referredToName,
            'referred_to_specialty'   => $specialty,
            'referred_to_facility'    => $facility,
            'urgency'                 => $urgency,
            'reason'                  => $reason,
            'clinical_summary'        => $clinicalSummary,
            'instructions_to_patient' => $instructions,
            'status'                  => 'pending',
        ]);
    }

    // -------------------------------------------------------------------------
    // Complete consultation → queue to 'completed'
    // -------------------------------------------------------------------------

    public function completeConsultationAndTransition(
        ConsultationNote $note,
        QueueEntry       $queueEntry,
        int              $userId,
        ?string          $transitionNotes = null,
    ): QueueEntry {
        return DB::connection('tenant')->transaction(function () use ($note, $queueEntry, $userId, $transitionNotes) {
            if (!$note->isFinalized()) {
                throw new \LogicException('Consultation note must be finalized before completing the encounter.');
            }

            $entry = $this->queueService->transition($queueEntry, 'awaiting_checkout', $userId, $transitionNotes);

            Encounter::on('tenant')
                ->where('id', $note->encounter_id)
                ->update(['status' => 'completed', 'ended_at' => now()]);

            return $entry;
        });
    }
}
