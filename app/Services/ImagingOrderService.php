<?php

namespace App\Services;

use App\Models\Tenant\DocumentInboundLog;
use App\Models\Tenant\ImagingOrder;
use App\Models\Tenant\ImagingReport;
use App\Models\Tenant\ImagingStudy;
use App\Models\Tenant\IntegrationConfig;
use App\Models\Tenant\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImagingOrderService
{
    public function __construct(
        private readonly Hl7MessageService $hl7,
    ) {}

    // -------------------------------------------------------------------------
    // Order creation
    // -------------------------------------------------------------------------

    public function createOrder(
        Patient $patient,
        int     $orderedBy,
        string  $modality,
        string  $bodyPart,
        string  $clinicalIndication,
        ?int    $encounterId = null,
        ?int    $branchId    = null,
        string  $priority    = 'routine',
        ?string $notes       = null,
    ): ImagingOrder {
        return DB::connection('tenant')->transaction(function () use (
            $patient, $orderedBy, $modality, $bodyPart, $clinicalIndication,
            $encounterId, $branchId, $priority, $notes
        ) {
            $orderNumber    = $this->nextOrderNumber();
            $accessionNumber = $this->generateAccessionNumber($modality);

            $order = ImagingOrder::on('tenant')->create([
                'order_number'        => $orderNumber,
                'accession_number'    => $accessionNumber,
                'patient_id'          => $patient->id,
                'encounter_id'        => $encounterId,
                'ordered_by'          => $orderedBy,
                'branch_id'           => $branchId,
                'modality'            => $modality,
                'body_part'           => $bodyPart,
                'clinical_indication' => $clinicalIndication,
                'priority'            => $priority,
                'notes'               => $notes,
                'status'              => 'pending',
                'ordered_at'          => now(),
            ]);

            $this->maybeSendToRis($order);

            return $order;
        });
    }

    public function cancelOrder(ImagingOrder $order): ImagingOrder
    {
        if (in_array($order->status, ['completed', 'report_final'])) {
            throw new \InvalidArgumentException('Cannot cancel a completed or finalized imaging order.');
        }

        $order->update(['status' => 'cancelled']);
        return $order;
    }

    // -------------------------------------------------------------------------
    // Result ingestion
    // -------------------------------------------------------------------------

    /**
     * Receive an HL7 v2 ORU^R01 from a RIS/PACS (imaging results follow same format).
     */
    public function receiveHl7Report(string $rawMessage, string $tenantUuid): bool
    {
        try {
            $parsed = $this->hl7->parseOruR01($rawMessage);
            $order  = $this->resolveOrder(
                placerNumber: $parsed['order']['placer_order_number'],
                fillerNumber: $parsed['order']['filler_order_number'],
            );

            if (!$order) {
                Log::warning('Imaging report received for unknown order', [
                    'placer' => $parsed['order']['placer_order_number'],
                    'filler' => $parsed['order']['filler_order_number'],
                ]);
                return false;
            }

            $findings   = '';
            $impression = '';

            foreach ($parsed['results'] as $r) {
                if (str_contains(strtolower($r['test_name'] ?? ''), 'impression')) {
                    $impression .= $r['value'] . "\n";
                } else {
                    $findings .= ($r['test_name'] ? $r['test_name'] . ': ' : '') . $r['value'] . "\n";
                }
            }

            $status      = ($parsed['order']['report_status'] === 'F') ? 'final' : 'preliminary';
            $isCritical  = collect($parsed['results'])->contains('is_critical', true);

            DB::connection('tenant')->transaction(function () use ($order, $findings, $impression, $status, $isCritical, $rawMessage, $parsed) {
                $report = ImagingReport::on('tenant')->create([
                    'imaging_order_id' => $order->id,
                    'patient_id'       => $order->patient_id,
                    'findings'         => trim($findings),
                    'impression'       => trim($impression),
                    'report_status'    => $status,
                    'is_critical'      => $isCritical,
                    'source'           => 'hl7_v2',
                    'raw_message'      => $rawMessage,
                    'report_datetime'  => $parsed['order']['observation_datetime'] ?? now(),
                ]);

                $order->update([
                    'status'              => $status === 'final' ? 'report_final' : 'report_preliminary',
                    'results_received_at' => now(),
                ]);

                DocumentInboundLog::on('tenant')->create([
                    'type'           => 'imaging_report',
                    'source'         => 'hl7_v2',
                    'reference_type' => ImagingReport::class,
                    'reference_id'   => $report->id,
                    'patient_id'     => $order->patient_id,
                    'branch_id'      => $order->branch_id,
                    'description'    => ($order->modality_label ?? $order->modality) . ' — ' . $order->body_part . ' (' . $status . ')',
                    'status'         => $isCritical ? 'critical' : 'received',
                    'is_critical'    => $isCritical,
                    'critical_note'  => $isCritical ? collect($parsed['results'])->where('is_critical', true)->pluck('value')->join('; ') : null,
                ]);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Imaging HL7 report parse failure', ['tenant' => $tenantUuid, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Receive a VEPRO-specific report payload (JSON over HTTP).
     * VEPRO sends a structured JSON body with study and report details.
     */
    public function receiveVeproReport(array $payload, string $tenantUuid): bool
    {
        try {
            $accession = $payload['accessionNumber'] ?? $payload['accession_number'] ?? null;

            $order = $accession
                ? ImagingOrder::on('tenant')->where('accession_number', $accession)->first()
                : null;

            if (!$order) {
                Log::warning('VEPRO report for unknown accession', ['accession' => $accession]);
                return false;
            }

            DB::connection('tenant')->transaction(function () use ($order, $payload) {
                // Store or update study details if VEPRO includes them
                if (!empty($payload['studyInstanceUID'])) {
                    ImagingStudy::on('tenant')->updateOrCreate(
                        ['study_instance_uid' => $payload['studyInstanceUID']],
                        [
                            'imaging_order_id'    => $order->id,
                            'patient_id'          => $order->patient_id,
                            'modality'            => $order->modality,
                            'study_description'   => $payload['studyDescription'] ?? null,
                            'number_of_series'    => $payload['numberOfSeries'] ?? null,
                            'number_of_instances' => $payload['numberOfInstances'] ?? null,
                            'pacs_study_url'      => $payload['studyUrl'] ?? null,
                            'pacs_system'         => 'vepro',
                            'study_datetime'      => $payload['studyDate'] ?? now(),
                        ]
                    );
                }

                $status    = ($payload['reportStatus'] ?? 'preliminary') === 'FINAL' ? 'final' : 'preliminary';
                $critical  = !empty($payload['criticalFinding']);

                $report = ImagingReport::on('tenant')->create([
                    'imaging_order_id' => $order->id,
                    'patient_id'       => $order->patient_id,
                    'radiologist_name' => $payload['radiologistName'] ?? null,
                    'findings'         => $payload['findings'] ?? '',
                    'impression'       => $payload['impression'] ?? '',
                    'recommendations'  => $payload['recommendations'] ?? null,
                    'report_status'    => $status,
                    'is_critical'      => $critical,
                    'critical_finding' => $critical ? ($payload['criticalFinding'] ?? null) : null,
                    'source'           => 'vepro',
                    'raw_message'      => json_encode($payload),
                    'report_datetime'  => $payload['reportDate'] ?? now(),
                ]);

                $order->update([
                    'status'              => $status === 'final' ? 'report_final' : 'report_preliminary',
                    'ris_order_id'        => $payload['risOrderId'] ?? $order->ris_order_id,
                    'results_received_at' => now(),
                ]);

                DocumentInboundLog::on('tenant')->create([
                    'type'           => 'imaging_report',
                    'source'         => 'vepro',
                    'reference_type' => ImagingReport::class,
                    'reference_id'   => $report->id,
                    'patient_id'     => $order->patient_id,
                    'branch_id'      => $order->branch_id,
                    'description'    => ($order->modality_label ?? $order->modality) . ' — ' . $order->body_part . ' (' . $status . ')',
                    'status'         => $critical ? 'critical' : 'received',
                    'is_critical'    => $critical,
                    'critical_note'  => $critical ? ($payload['criticalFinding'] ?? null) : null,
                ]);
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('VEPRO report processing failure', ['tenant' => $tenantUuid, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Store a manually entered imaging report (for centres without PACS integration).
     */
    public function storeManualReport(
        ImagingOrder $order,
        string       $findings,
        string       $impression,
        string       $status,
        bool         $isCritical      = false,
        ?string      $criticalFinding = null,
        ?string      $radiologist     = null,
        ?string      $pdfPath         = null,
    ): ImagingReport {
        $report = ImagingReport::on('tenant')->create([
            'imaging_order_id' => $order->id,
            'patient_id'       => $order->patient_id,
            'radiologist_name' => $radiologist,
            'findings'         => $findings,
            'impression'       => $impression,
            'report_status'    => $status,
            'is_critical'      => $isCritical,
            'critical_finding' => $isCritical ? $criticalFinding : null,
            'report_pdf_path'  => $pdfPath,
            'source'           => 'manual',
            'report_datetime'  => now(),
        ]);

        $order->update(['status' => $status === 'final' ? 'report_final' : 'report_preliminary']);

        return $report;
    }

    // -------------------------------------------------------------------------
    // RIS outbound
    // -------------------------------------------------------------------------

    private function maybeSendToRis(ImagingOrder $order): void
    {
        $config = IntegrationConfig::imagingRis();

        if (!$config || !$config->send_orders) {
            return;
        }

        $placerNumber = 'TTM-IMG-' . $order->id;
        $order->update([
            'hl7_placer_order_number' => $placerNumber,
            'integration_type'        => $config->protocol,
        ]);

        if ($config->protocol === 'hl7_v2') {
            $this->sendHl7ImagingOrder($order, $config, $placerNumber);
        } elseif ($config->protocol === 'vepro') {
            $this->sendVeproOrder($order, $config);
        }
    }

    private function sendHl7ImagingOrder(ImagingOrder $order, IntegrationConfig $config, string $placerNumber): void
    {
        $patient = $order->patient;

        $message = $this->hl7->buildOrmO01(
            sendingApp:        $config->sending_application ?? 'TTMED',
            sendingFacility:   $config->sending_facility ?? 'TTMED',
            receivingApp:      $config->receiving_application ?? 'RIS',
            receivingFacility: $config->receiving_facility ?? 'RIS',
            patientData: [
                'external_id' => $patient->patient_number,
                'last_name'   => $patient->last_name,
                'first_name'  => $patient->first_name,
                'dob'         => $patient->date_of_birth?->format('Y-m-d'),
                'gender'      => $patient->gender,
            ],
            orderData: [
                'placer_order_number' => $placerNumber,
                'priority'            => $order->priority,
            ],
            orderItems: [[
                'code'       => $order->modality,
                'name'       => $order->body_part . ' ' . $order->modality,
                'loinc_code' => '',
            ]],
        );

        if ($config->mllp_host && $config->mllp_port) {
            $framed = "\x0B" . $message . "\x1C\x0D";
            $socket = @fsockopen($config->mllp_host, $config->mllp_port, $errno, $errstr, 5);
            if ($socket) {
                fwrite($socket, $framed);
                fclose($socket);
            }
        }

        $order->update(['status' => 'sent_to_ris']);
    }

    private function sendVeproOrder(ImagingOrder $order, IntegrationConfig $config): void
    {
        $apiKey = $config->getApiKey();
        if (!$apiKey || !$config->endpoint_url) {
            return;
        }

        $patient = $order->patient;
        $payload = json_encode([
            'accessionNumber'   => $order->accession_number,
            'patientId'         => $patient->patient_number,
            'patientLastName'   => $patient->last_name,
            'patientFirstName'  => $patient->first_name,
            'patientDob'        => $patient->date_of_birth?->format('Ymd'),
            'patientSex'        => strtoupper(substr($patient->gender ?? 'U', 0, 1)),
            'modality'          => $order->modality,
            'bodyPart'          => $order->body_part,
            'clinicalHistory'   => $order->clinical_indication,
            'priority'          => strtoupper($order->priority),
            'requestingDoctor'  => $order->orderedBy?->full_name,
            'orderDate'         => $order->ordered_at?->format('Ymd'),
        ]);

        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}",
            'content' => $payload,
            'timeout' => 10,
        ]]);

        @file_get_contents($config->endpoint_url . '/orders', false, $context);

        $order->update(['status' => 'sent_to_ris']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveOrder(?string $placer, ?string $filler): ?ImagingOrder
    {
        if ($placer) {
            $order = ImagingOrder::on('tenant')
                ->where('hl7_placer_order_number', $placer)
                ->orWhere('order_number', $placer)
                ->first();
            if ($order) return $order;
        }

        if ($filler) {
            return ImagingOrder::on('tenant')
                ->where('ris_order_id', $filler)
                ->orWhere('accession_number', $filler)
                ->first();
        }

        return null;
    }

    private function nextOrderNumber(): string
    {
        return DB::connection('tenant')->transaction(function () {
            $today = today()->toDateString();
            $seq   = DB::connection('tenant')
                ->table('imaging_order_number_sequences')
                ->where('order_date', $today)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::connection('tenant')->table('imaging_order_number_sequences')->insert([
                    'order_date'  => $today,
                    'last_number' => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                $next = 1;
            } else {
                $next = $seq->last_number + 1;
                DB::connection('tenant')->table('imaging_order_number_sequences')
                    ->where('order_date', $today)
                    ->update(['last_number' => $next, 'updated_at' => now()]);
            }

            return 'IMG-' . today()->format('Ymd') . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
        });
    }

    private function generateAccessionNumber(string $modality): string
    {
        return strtoupper($modality) . '-' . today()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
