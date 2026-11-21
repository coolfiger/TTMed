<?php

namespace App\Services;

use App\Models\Tenant\Doctor;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\Prescription;
use App\Models\Tenant\PrescriptionItem;
use App\Models\Tenant\PrescriptionDeliveryLog;
use App\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditService  $auditService,
    ) {}

    // -------------------------------------------------------------------------
    // Draft management
    // -------------------------------------------------------------------------

    public function getOrCreateDraft(int $encounterId, int $doctorId): Prescription
    {
        $encounter = Encounter::on('tenant')->findOrFail($encounterId);

        return Prescription::on('tenant')->firstOrCreate(
            ['encounter_id' => $encounterId, 'status' => Prescription::STATUS_DRAFT],
            [
                'prescription_number' => $this->nextPrescriptionNumber(),
                'patient_id'          => $encounter->patient_id,
                'doctor_id'           => $doctorId,
                'branch_id'           => $encounter->branch_id ?? null,
            ]
        );
    }

    public function addItem(Prescription $prescription, array $data): PrescriptionItem
    {
        if (!$prescription->isDraft()) {
            throw new \LogicException('Cannot add items to a non-draft prescription.');
        }

        $sortOrder = $prescription->items()->max('sort_order') + 1;

        return PrescriptionItem::on('tenant')->create(array_merge($data, [
            'prescription_id' => $prescription->id,
            'sort_order'      => $sortOrder,
        ]));
    }

    public function removeItem(PrescriptionItem $item): void
    {
        if (!$item->prescription->isDraft()) {
            throw new \LogicException('Cannot remove items from a non-draft prescription.');
        }

        $item->delete();
    }

    // -------------------------------------------------------------------------
    // Allergy cross-check
    // -------------------------------------------------------------------------

    /**
     * Returns medication names from the prescription that match a patient allergy.
     * Comparison is case-insensitive on medication name and generic_name.
     */
    public function checkAllergies(Prescription $prescription, array $patientAllergyNames): array
    {
        $warnings = [];

        foreach ($prescription->items as $item) {
            foreach ($patientAllergyNames as $allergy) {
                $allergy = strtolower(trim($allergy));
                $medName = strtolower($item->medication_name);
                $generic = strtolower($item->medication?->generic_name ?? '');

                if (str_contains($medName, $allergy) || ($generic && str_contains($generic, $allergy))) {
                    $warnings[] = $item->medication_name;
                }
            }
        }

        return array_unique($warnings);
    }

    // -------------------------------------------------------------------------
    // Issuance
    // -------------------------------------------------------------------------

    public function issuePrescription(Prescription $prescription, int $userId): Prescription
    {
        if (!$prescription->isDraft()) {
            throw new \LogicException('Only a draft prescription can be issued.');
        }

        if (!$prescription->hasItems()) {
            throw new \InvalidArgumentException('Cannot issue a prescription with no items.');
        }

        return DB::connection('tenant')->transaction(function () use ($prescription, $userId) {
            $issuedAt = now();

            // Set issued_at before generating the PDF so the blade can render it.
            $prescription->forceFill(['issued_at' => $issuedAt, 'issued_by' => $userId]);

            $pdfPath = $this->generatePdf($prescription);

            $prescription->update([
                'status'    => Prescription::STATUS_ISSUED,
                'pdf_path'  => $pdfPath,
                'issued_by' => $userId,
                'issued_at' => $issuedAt,
            ]);

            $this->auditService->logTenant(
                'issue',
                'prescription',
                $prescription->id,
                null,
                ['prescription_number' => $prescription->prescription_number],
                "Prescription {$prescription->prescription_number} issued",
            );

            return $prescription->fresh();
        });
    }

    public function cancelPrescription(Prescription $prescription, string $reason, int $userId): Prescription
    {
        if ($prescription->isCancelled()) {
            throw new \LogicException('Prescription is already cancelled.');
        }

        $prescription->update([
            'status'               => Prescription::STATUS_CANCELLED,
            'cancellation_reason'  => $reason,
            'cancelled_by'         => $userId,
            'cancelled_at'         => now(),
        ]);

        return $prescription->fresh();
    }

    // -------------------------------------------------------------------------
    // Email delivery
    // -------------------------------------------------------------------------

    public function queueEmailDelivery(Prescription $prescription, string $recipientEmail): PrescriptionDeliveryLog
    {
        if (!$prescription->isIssued()) {
            throw new \LogicException('Only issued prescriptions can be emailed.');
        }

        $log = PrescriptionDeliveryLog::on('tenant')->create([
            'prescription_id'  => $prescription->id,
            'delivery_type'    => 'email',
            'recipient_email'  => $recipientEmail,
            'status'           => 'pending',
        ]);

        // Dispatch the queued mail job
        dispatch(new \App\Jobs\SendPrescriptionEmailJob($prescription->id, $log->id, $recipientEmail));

        return $log;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function nextPrescriptionNumber(): string
    {
        $dateKey = now()->format('Y-m-d');

        $seq = DB::connection('tenant')
            ->table('prescription_number_sequences')
            ->where('date_key', $dateKey)
            ->lockForUpdate()
            ->first();

        $next = $seq ? ($seq->last_number + 1) : 1;

        DB::connection('tenant')->table('prescription_number_sequences')->upsert(
            ['date_key' => $dateKey, 'last_number' => $next],
            ['date_key'],
            ['last_number']
        );

        return 'RX-' . now()->format('Ymd') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function generatePdf(Prescription $prescription): string
    {
        $prescription->load(['patient', 'doctor.staffProfile', 'items']);

        $html = view('pdf.prescription', ['prescription' => $prescription])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $dir = $this->tenantContext->storagePath('prescriptions');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $prescription->prescription_number . '.pdf';
        $path     = $dir . '/' . $filename;

        $pdf->save($path);

        return $path;
    }
}
