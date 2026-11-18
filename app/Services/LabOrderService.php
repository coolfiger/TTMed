<?php

namespace App\Services;

use App\Models\Tenant\IntegrationConfig;
use App\Models\Tenant\LabOrder;
use App\Models\Tenant\LabOrderItem;
use App\Models\Tenant\LabResult;
use App\Models\Tenant\Patient;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabOrderService
{
    public function __construct(
        private readonly Hl7MessageService $hl7,
        private readonly TenantContext     $tenantContext,
    ) {}

    // -------------------------------------------------------------------------
    // Order creation
    // -------------------------------------------------------------------------

    public function createOrder(
        Patient $patient,
        int     $orderedBy,
        array   $testIds,
        array   $panelIds = [],
        ?int    $encounterId  = null,
        ?int    $branchId     = null,
        string  $orderType    = 'routine',
        ?string $clinicalNotes = null,
    ): LabOrder {
        return DB::connection('tenant')->transaction(function () use (
            $patient, $orderedBy, $testIds, $panelIds,
            $encounterId, $branchId, $orderType, $clinicalNotes
        ) {
            $orderNumber = $this->nextOrderNumber();

            $order = LabOrder::on('tenant')->create([
                'order_number'    => $orderNumber,
                'patient_id'      => $patient->id,
                'encounter_id'    => $encounterId,
                'ordered_by'      => $orderedBy,
                'branch_id'       => $branchId,
                'status'          => LabOrder::STATUS_PENDING,
                'order_type'      => $orderType,
                'clinical_notes'  => $clinicalNotes,
                'ordered_at'      => now(),
            ]);

            foreach ($testIds as $testId) {
                $test = DB::connection('tenant')->table('lab_tests')->find($testId);
                if ($test) {
                    LabOrderItem::on('tenant')->create([
                        'lab_order_id' => $order->id,
                        'lab_test_id'  => $testId,
                        'test_name'    => $test->name,
                        'test_code'    => $test->code,
                        'loinc_code'   => $test->loinc_code,
                    ]);
                }
            }

            foreach ($panelIds as $panelId) {
                $panel = DB::connection('tenant')->table('lab_panels')->find($panelId);
                if ($panel) {
                    LabOrderItem::on('tenant')->create([
                        'lab_order_id' => $order->id,
                        'lab_panel_id' => $panelId,
                        'test_name'    => $panel->name,
                        'test_code'    => $panel->code,
                    ]);
                }
            }

            // If LIS integration is active and configured to send orders, dispatch now
            $this->maybeSendToLis($order);

            return $order->load('items');
        });
    }

    public function markSpecimenCollected(LabOrder $order, int $collectedBy): LabOrder
    {
        $order->update([
            'status'       => LabOrder::STATUS_SPECIMEN_COLLECTED,
            'collected_at' => now(),
            'collected_by' => $collectedBy,
        ]);

        // If not already sent to LIS, send now on collection
        $this->maybeSendToLis($order->fresh());

        return $order->fresh();
    }

    public function cancelOrder(LabOrder $order, int $cancelledBy): LabOrder
    {
        if (in_array($order->status, [LabOrder::STATUS_RESULTS_RECEIVED, LabOrder::STATUS_RESULTS_REVIEWED])) {
            throw new \InvalidArgumentException('Cannot cancel an order that already has results.');
        }

        $order->update(['status' => LabOrder::STATUS_CANCELLED]);
        return $order;
    }

    // -------------------------------------------------------------------------
    // Result ingestion (native / HL7 / FHIR)
    // -------------------------------------------------------------------------

    /**
     * Receive and store an HL7 v2 ORU^R01 message.
     * Called from LabInboundController after signature verification.
     */
    public function receiveHl7Result(string $rawMessage, string $tenantUuid): bool
    {
        try {
            $parsed = $this->hl7->parseOruR01($rawMessage);
            return $this->storeResults(
                placerOrderNumber: $parsed['order']['placer_order_number'],
                fillerOrderNumber: $parsed['order']['filler_order_number'],
                results:           $parsed['results'],
                performingLab:     null,
                source:            'hl7_v2',
                rawMessage:        $rawMessage,
            );
        } catch (\Throwable $e) {
            Log::channel('single')->error('HL7 lab result parse failure', [
                'tenant' => $tenantUuid,
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Receive a FHIR R4 DiagnosticReport payload.
     */
    public function receiveFhirDiagnosticReport(array $fhir, string $tenantUuid): bool
    {
        try {
            $placerRef = $this->extractFhirPlacerRef($fhir);
            $results   = $this->extractFhirResults($fhir);
            $rawJson   = json_encode($fhir);

            return $this->storeResults(
                placerOrderNumber: $placerRef,
                fillerOrderNumber: $fhir['id'] ?? null,
                results:           $results,
                performingLab:     $fhir['performer'][0]['display'] ?? null,
                source:            'fhir_r4',
                rawMessage:        $rawJson,
            );
        } catch (\Throwable $e) {
            Log::channel('single')->error('FHIR lab result parse failure', [
                'tenant' => $tenantUuid,
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Store results manually (native lab entry — called from UI, not integration).
     */
    public function storeManualResult(
        LabOrder $order,
        int      $orderItemId,
        string   $value,
        string   $flag,
        ?string  $unit           = null,
        ?string  $referenceRange = null,
        ?string  $performingLab  = null,
        bool     $isCritical     = false,
    ): LabResult {
        $item = LabOrderItem::on('tenant')
            ->where('lab_order_id', $order->id)
            ->findOrFail($orderItemId);

        $result = LabResult::on('tenant')->create([
            'lab_order_id'      => $order->id,
            'lab_order_item_id' => $item->id,
            'patient_id'        => $order->patient_id,
            'lab_test_id'       => $item->lab_test_id,
            'test_name'         => $item->test_name,
            'test_code'         => $item->test_code,
            'loinc_code'        => $item->loinc_code,
            'value'             => $value,
            'value_unit'        => $unit,
            'reference_range'   => $referenceRange,
            'flag'              => $flag,
            'is_critical'       => $isCritical,
            'performing_lab'    => $performingLab,
            'result_datetime'   => now(),
            'source'            => 'manual',
        ]);

        $this->updateOrderStatusAfterResult($order);

        return $result;
    }

    private function storeResults(
        ?string $placerOrderNumber,
        ?string $fillerOrderNumber,
        array   $results,
        ?string $performingLab,
        string  $source,
        ?string $rawMessage,
    ): bool {
        $order = null;

        if ($placerOrderNumber) {
            $order = LabOrder::on('tenant')
                ->where('lis_placer_order_number', $placerOrderNumber)
                ->orWhere('order_number', $placerOrderNumber)
                ->first();
        }

        if (!$order && $fillerOrderNumber) {
            $order = LabOrder::on('tenant')
                ->where('lis_filler_order_number', $fillerOrderNumber)
                ->first();
        }

        if (!$order) {
            Log::warning('Lab result received for unknown order', compact('placerOrderNumber', 'fillerOrderNumber'));
            return false;
        }

        if ($fillerOrderNumber) {
            $order->update(['lis_filler_order_number' => $fillerOrderNumber]);
        }

        DB::connection('tenant')->transaction(function () use ($order, $results, $performingLab, $source, $rawMessage) {
            foreach ($results as $r) {
                LabResult::on('tenant')->create([
                    'lab_order_id'    => $order->id,
                    'patient_id'      => $order->patient_id,
                    'test_name'       => $r['test_name'],
                    'test_code'       => $r['test_code'] ?? null,
                    'loinc_code'      => $r['loinc_code'] ?? null,
                    'value'           => $r['value'],
                    'value_unit'      => $r['unit'] ?? null,
                    'reference_range' => $r['reference_range'] ?? null,
                    'flag'            => $r['flag'] ?? 'normal',
                    'is_critical'     => $r['is_critical'] ?? false,
                    'performing_lab'  => $performingLab,
                    'result_datetime' => $r['result_datetime'] ?? now(),
                    'source'          => $source,
                    'raw_message'     => $rawMessage,
                ]);
            }

            $this->updateOrderStatusAfterResult($order);
        });

        return true;
    }

    private function updateOrderStatusAfterResult(LabOrder $order): void
    {
        $resultCount = $order->results()->count();
        $itemCount   = $order->items()->count();

        $newStatus = $resultCount >= $itemCount
            ? LabOrder::STATUS_RESULTS_RECEIVED
            : LabOrder::STATUS_PARTIAL_RESULTS;

        $order->update([
            'status'              => $newStatus,
            'results_received_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // LIS outbound
    // -------------------------------------------------------------------------

    private function maybeSendToLis(LabOrder $order): void
    {
        $config = IntegrationConfig::labLis();

        if (!$config || !$config->send_orders) {
            return;
        }

        $placerNumber = 'TTM-' . $order->id;
        $order->update(['lis_placer_order_number' => $placerNumber, 'integration_type' => $config->protocol]);

        if ($config->protocol === 'hl7_v2') {
            $this->sendHl7Order($order, $config, $placerNumber);
        }
        // FHIR R4 order dispatch would go here
    }

    private function sendHl7Order(LabOrder $order, IntegrationConfig $config, string $placerNumber): void
    {
        $patient  = $order->patient;
        $items    = $order->items;

        $message = $this->hl7->buildOrmO01(
            sendingApp:     $config->sending_application ?? 'TTMED',
            sendingFacility: $config->sending_facility ?? 'TTMED',
            receivingApp:   $config->receiving_application ?? 'LIS',
            receivingFacility: $config->receiving_facility ?? 'LIS',
            patientData: [
                'external_id' => $patient->patient_number,
                'last_name'   => $patient->last_name,
                'first_name'  => $patient->first_name,
                'dob'         => $patient->date_of_birth?->format('Y-m-d'),
                'gender'      => $patient->gender,
            ],
            orderData: [
                'placer_order_number' => $placerNumber,
                'priority'            => $order->order_type,
            ],
            orderItems: $items->map(fn ($i) => [
                'code'       => $i->test_code,
                'name'       => $i->test_name,
                'loinc_code' => $i->loinc_code,
            ])->toArray(),
        );

        if ($config->mllp_host && $config->mllp_port) {
            $this->sendViaMllp($config->mllp_host, $config->mllp_port, $message);
        } elseif ($config->endpoint_url) {
            $this->sendViaHttp($config->endpoint_url, $message, $config);
        }

        $order->update(['status' => LabOrder::STATUS_SENT_TO_LAB, 'sent_to_lab_at' => now()]);
    }

    private function sendViaMllp(string $host, int $port, string $message): void
    {
        // MLLP framing: 0x0B + message + 0x1C + 0x0D
        $framed = "\x0B" . $message . "\x1C\x0D";

        $socket = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$socket) {
            Log::error("MLLP connection failed to {$host}:{$port} — {$errstr}");
            return;
        }

        fwrite($socket, $framed);
        $response = fread($socket, 4096);
        fclose($socket);

        Log::info("MLLP HL7 order sent to {$host}:{$port}");
    }

    private function sendViaHttp(string $url, string $message, IntegrationConfig $config): void
    {
        $apiKey = $config->getApiKey();

        $headers = ['Content-Type: application/hl7-v2+er7'];
        if ($apiKey) {
            $headers[] = "Authorization: Bearer {$apiKey}";
        }

        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $message,
            'timeout' => 10,
        ]]);

        @file_get_contents($url, false, $context);
    }

    // -------------------------------------------------------------------------
    // FHIR helpers
    // -------------------------------------------------------------------------

    private function extractFhirPlacerRef(array $fhir): ?string
    {
        foreach ($fhir['basedOn'] ?? [] as $ref) {
            if (isset($ref['identifier']['value'])) {
                return $ref['identifier']['value'];
            }
        }
        return null;
    }

    private function extractFhirResults(array $fhir): array
    {
        $results = [];

        foreach ($fhir['result'] ?? [] as $resultRef) {
            // In a full FHIR implementation each result ref would be resolved.
            // For bundled responses (common in push scenarios), the Observation
            // is embedded inline in the diagnostic report bundle.
        }

        // Handle contained Observation resources (common FHIR push pattern)
        foreach ($fhir['contained'] ?? [] as $resource) {
            if (($resource['resourceType'] ?? '') !== 'Observation') {
                continue;
            }

            $coding   = $resource['code']['coding'][0] ?? [];
            $flag     = 'normal';
            $critical = false;

            foreach ($resource['interpretation'] ?? [] as $interp) {
                $code = $interp['coding'][0]['code'] ?? '';
                $flag = match ($code) {
                    'L'  => 'low',
                    'H'  => 'high',
                    'LL' => 'critical_low',
                    'HH' => 'critical_high',
                    'A'  => 'abnormal',
                    default => 'normal',
                };
                $critical = in_array($code, ['LL', 'HH']);
            }

            $value = match ($resource['valueQuantity'] ?? null) {
                null => $resource['valueString'] ?? $resource['valueCodeableConcept']['text'] ?? '',
                default => ($resource['valueQuantity']['value'] ?? '')
                            . ' ' . ($resource['valueQuantity']['unit'] ?? ''),
            };

            $results[] = [
                'test_code'       => $coding['code'] ?? '',
                'loinc_code'      => ($coding['system'] ?? '') === 'http://loinc.org' ? $coding['code'] : '',
                'test_name'       => $coding['display'] ?? $resource['code']['text'] ?? '',
                'value'           => is_array($value) ? json_encode($value) : trim((string) $value),
                'unit'            => $resource['valueQuantity']['unit'] ?? null,
                'reference_range' => $resource['referenceRange'][0]['text'] ?? null,
                'flag'            => $flag,
                'is_critical'     => $critical,
                'result_datetime' => $resource['effectiveDateTime'] ?? null,
            ];
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // Sequence
    // -------------------------------------------------------------------------

    private function nextOrderNumber(): string
    {
        return DB::connection('tenant')->transaction(function () {
            $today = today()->toDateString();
            $seq   = DB::connection('tenant')
                ->table('lab_order_number_sequences')
                ->where('order_date', $today)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::connection('tenant')->table('lab_order_number_sequences')->insert([
                    'order_date'  => $today,
                    'last_number' => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                $next = 1;
            } else {
                $next = $seq->last_number + 1;
                DB::connection('tenant')->table('lab_order_number_sequences')
                    ->where('order_date', $today)
                    ->update(['last_number' => $next, 'updated_at' => now()]);
            }

            return 'LAB-' . today()->format('Ymd') . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
        });
    }
}
