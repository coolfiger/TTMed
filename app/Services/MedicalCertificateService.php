<?php

namespace App\Services;

use App\Models\Tenant\MedicalCertificate;
use App\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class MedicalCertificateService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditService  $auditService,
    ) {}

    public function create(
        int     $patientId,
        int     $doctorId,
        int     $issuedBy,
        string  $certificateType,
        string  $fromDate,
        ?string $toDate          = null,
        ?string $diagnosisText   = null,
        ?string $notes           = null,
        ?string $instructions    = null,
        ?int    $encounterId     = null,
        ?int    $branchId        = null,
    ): MedicalCertificate {
        return DB::connection('tenant')->transaction(function () use (
            $patientId, $doctorId, $issuedBy, $certificateType,
            $fromDate, $toDate, $diagnosisText, $notes, $instructions,
            $encounterId, $branchId
        ) {
            $cert = MedicalCertificate::on('tenant')->create([
                'certificate_number' => $this->nextCertificateNumber(),
                'patient_id'         => $patientId,
                'doctor_id'          => $doctorId,
                'branch_id'          => $branchId,
                'encounter_id'       => $encounterId,
                'certificate_type'   => $certificateType,
                'from_date'          => $fromDate,
                'to_date'            => $toDate,
                'diagnosis_text'     => $diagnosisText,
                'notes'              => $notes,
                'instructions'       => $instructions,
                'issued_by'          => $issuedBy,
                'issued_at'          => now(),
            ]);

            $pdfPath = $this->generatePdf($cert);
            $cert->update(['pdf_path' => $pdfPath]);

            $this->auditService->logTenant(
                'issue',
                'medical_certificate',
                $cert->id,
                null,
                ['certificate_number' => $cert->certificate_number],
                "Medical certificate {$cert->certificate_number} issued",
            );

            return $cert->fresh();
        });
    }

    private function nextCertificateNumber(): string
    {
        $dateKey = now()->format('Y-m-d');

        $seq = DB::connection('tenant')
            ->table('certificate_number_sequences')
            ->where('date_key', $dateKey)
            ->lockForUpdate()
            ->first();

        $next = $seq ? ($seq->last_number + 1) : 1;

        DB::connection('tenant')->table('certificate_number_sequences')->upsert(
            ['date_key' => $dateKey, 'last_number' => $next],
            ['date_key'],
            ['last_number']
        );

        return 'MC-' . now()->format('Ymd') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function generatePdf(MedicalCertificate $cert): string
    {
        $cert->load(['patient', 'doctor.staffProfile']);

        $html = view('pdf.medical-certificate', ['certificate' => $cert])->render();
        $pdf  = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $dir = $this->tenantContext->storagePath('certificates');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $cert->certificate_number . '.pdf';
        $path     = $dir . '/' . $filename;

        $pdf->save($path);

        return $path;
    }
}
