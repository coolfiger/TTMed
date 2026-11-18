<?php

namespace App\Services;

/**
 * HL7 v2.x message parser and builder.
 *
 * Covers the segments TTMed needs:
 *   Inbound:  ORU^R01 (lab/imaging results)
 *   Outbound: ORM^O01 (lab/imaging orders), ACK
 *
 * Does NOT implement full HL7 compliance — handles the specific segment
 * profiles used by common LIS/RIS systems (Sunquest, Meditech, VEPRO).
 */
class Hl7MessageService
{
    private const SEGMENT_SEP  = "\r";
    private const FIELD_SEP    = '|';
    private const COMPONENT_SEP = '^';
    private const REPEAT_SEP   = '~';
    private const ESCAPE        = '\\';
    private const SUBCOMPONENT  = '&';

    // -------------------------------------------------------------------------
    // Parsing
    // -------------------------------------------------------------------------

    public function parse(string $raw): array
    {
        $raw      = str_replace("\r\n", "\r", str_replace("\n", "\r", $raw));
        $segments = array_filter(array_map('trim', explode(self::SEGMENT_SEP, $raw)));
        $parsed   = [];

        foreach ($segments as $seg) {
            $fields = explode(self::FIELD_SEP, $seg);
            $name   = array_shift($fields);

            $parsed[$name][] = array_map(function ($field) {
                if (str_contains($field, self::COMPONENT_SEP)) {
                    return explode(self::COMPONENT_SEP, $field);
                }
                return $field;
            }, $fields);
        }

        return $parsed;
    }

    /**
     * Extract structured data from an ORU^R01 message.
     * Returns an array with: patient, order, results[].
     */
    public function parseOruR01(string $raw): array
    {
        $msg = $this->parse($raw);

        $patient = $this->extractPatientFromPid($msg['PID'][0] ?? []);
        $order   = $this->extractOrderFromObr($msg['OBR'][0] ?? []);
        $results = [];

        foreach ($msg['OBX'] ?? [] as $obx) {
            $results[] = $this->extractResultFromObx($obx);
        }

        return compact('patient', 'order', 'results');
    }

    private function extractPatientFromPid(array $fields): array
    {
        // PID-3: patient_id_list, PID-5: patient_name, PID-7: DOB, PID-8: sex
        $name = $fields[4] ?? '';
        if (is_array($name)) {
            [$family, $given] = [$name[0] ?? '', $name[1] ?? ''];
        } else {
            $parts  = explode('^', $name);
            $family = $parts[0] ?? '';
            $given  = $parts[1] ?? '';
        }

        return [
            'external_id' => $this->fieldValue($fields[2] ?? ''),
            'last_name'   => $family,
            'first_name'  => $given,
            'dob'         => $this->parseHl7Date($this->fieldValue($fields[6] ?? '')),
            'gender'      => strtolower($this->fieldValue($fields[7] ?? '')),
        ];
    }

    private function extractOrderFromObr(array $fields): array
    {
        return [
            'placer_order_number' => $this->fieldValue($fields[1] ?? ''),
            'filler_order_number' => $this->fieldValue($fields[2] ?? ''),
            'test_code'           => $this->fieldValue($fields[3] ?? ''),
            'test_name'           => $this->fieldComponent($fields[3] ?? '', 1),
            'observation_datetime' => $this->parseHl7DateTime($this->fieldValue($fields[6] ?? '')),
            'report_status'       => $this->fieldValue($fields[24] ?? ''), // F=final, P=preliminary
        ];
    }

    private function extractResultFromObx(array $fields): array
    {
        $valueType = $this->fieldValue($fields[1] ?? '');
        $testId    = $fields[2] ?? '';

        $flag   = strtoupper($this->fieldValue($fields[7] ?? ''));
        $status = match ($flag) {
            'L'  => 'low',
            'H'  => 'high',
            'LL' => 'critical_low',
            'HH' => 'critical_high',
            'A', 'AA' => 'abnormal',
            default   => 'normal',
        };

        return [
            'test_code'       => $this->fieldComponent($testId, 0),
            'loinc_code'      => $this->extractLoincFromCwe($testId),
            'test_name'       => $this->fieldComponent($testId, 1),
            'value'           => $this->fieldValue($fields[4] ?? ''),
            'unit'            => $this->fieldValue($fields[5] ?? ''),
            'reference_range' => $this->fieldValue($fields[6] ?? ''),
            'flag'            => $status,
            'is_critical'     => in_array($flag, ['LL', 'HH', 'AA']),
            'result_datetime' => $this->parseHl7DateTime($this->fieldValue($fields[13] ?? '')),
            'observation_status' => $this->fieldValue($fields[10] ?? ''), // F=final
        ];
    }

    // -------------------------------------------------------------------------
    // Building
    // -------------------------------------------------------------------------

    /**
     * Build an ORM^O01 lab order message.
     */
    public function buildOrmO01(
        string $sendingApp,
        string $sendingFacility,
        string $receivingApp,
        string $receivingFacility,
        array  $patientData,
        array  $orderData,
        array  $orderItems,
    ): string {
        $msgId    = uniqid('TTM', true);
        $datetime = now()->format('YmdHis');

        $segments = [];

        // MSH
        $segments[] = implode(self::FIELD_SEP, [
            'MSH',
            '^~\&',
            $sendingApp,
            $sendingFacility,
            $receivingApp,
            $receivingFacility,
            $datetime,
            '',
            'ORM^O01',
            $msgId,
            'P',
            '2.5',
        ]);

        // PID
        $segments[] = implode(self::FIELD_SEP, [
            'PID',
            '1',
            '',
            $patientData['external_id'] ?? '',
            '',
            implode(self::COMPONENT_SEP, [
                $patientData['last_name'] ?? '',
                $patientData['first_name'] ?? '',
            ]),
            '',
            isset($patientData['dob']) ? date('Ymd', strtotime($patientData['dob'])) : '',
            strtoupper(substr($patientData['gender'] ?? 'U', 0, 1)),
        ]);

        // ORC (common order)
        $segments[] = implode(self::FIELD_SEP, [
            'ORC',
            'NW',                                // NW = new order
            $orderData['placer_order_number'],
            '',                                  // filler order number (assigned by LIS)
            '',
            '',
            '',
            '',
            '',
            $datetime,
        ]);

        // One OBR per order item
        foreach ($orderItems as $i => $item) {
            $segments[] = implode(self::FIELD_SEP, [
                'OBR',
                (string) ($i + 1),
                $orderData['placer_order_number'],
                '',
                implode(self::COMPONENT_SEP, [
                    $item['code'] ?? '',
                    $item['name'] ?? '',
                    'L',                         // L = local code
                    $item['loinc_code'] ?? '',
                    '',
                    'LN',                        // LN = LOINC
                ]),
                $orderData['priority'] === 'stat' ? 'S' : 'R',  // S=stat, R=routine
                $datetime,
            ]);
        }

        return implode(self::SEGMENT_SEP, $segments) . self::SEGMENT_SEP;
    }

    /**
     * Build an ACK acknowledgement.
     */
    public function buildAck(string $originalMsgId, string $code = 'AA', string $text = ''): string
    {
        $datetime = now()->format('YmdHis');
        $ackId    = uniqid('ACK', true);

        $segments = [
            implode(self::FIELD_SEP, [
                'MSH', '^~\&', 'TTMED', 'TTMED', '', '', $datetime, '', 'ACK', $ackId, 'P', '2.5',
            ]),
            implode(self::FIELD_SEP, [
                'MSA', $code, $originalMsgId, $text,
            ]),
        ];

        return implode(self::SEGMENT_SEP, $segments) . self::SEGMENT_SEP;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Extract LOINC code from a CWE-typed OBX-3 field.
     * Handles two common patterns:
     *   "LOINC^Name^LN"            — code IS the LOINC (component[2] = 'LN')
     *   "LOCAL^Name^L^^LOINC^^LN"  — LOINC in alternate position (component[6] = 'LN')
     */
    private function extractLoincFromCwe(mixed $field): string
    {
        $parts = is_array($field) ? $field : explode(self::COMPONENT_SEP, (string) $field);

        $codingSystem    = $parts[2] ?? '';
        $altCodingSystem = $parts[6] ?? '';

        if (strtoupper($codingSystem) === 'LN') {
            return $parts[0] ?? '';
        }

        if (strtoupper($altCodingSystem) === 'LN') {
            return $parts[4] ?? '';
        }

        return '';
    }

    private function fieldValue(mixed $field): string
    {
        return is_array($field) ? ($field[0] ?? '') : (string) $field;
    }

    private function fieldComponent(mixed $field, int $index): string
    {
        if (is_array($field)) {
            return $field[$index] ?? '';
        }
        $parts = explode(self::COMPONENT_SEP, (string) $field);
        return $parts[$index] ?? '';
    }

    private function parseHl7Date(string $hl7date): ?string
    {
        $d = preg_replace('/\D/', '', $hl7date);
        if (strlen($d) >= 8) {
            return substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2);
        }
        return null;
    }

    private function parseHl7DateTime(string $hl7dt): ?string
    {
        $d = preg_replace('/\D/', '', $hl7dt);
        if (strlen($d) >= 14) {
            return substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2)
                . ' ' . substr($d, 8, 2) . ':' . substr($d, 10, 2) . ':' . substr($d, 12, 2);
        }
        if (strlen($d) >= 8) {
            return $this->parseHl7Date($d);
        }
        return null;
    }
}
