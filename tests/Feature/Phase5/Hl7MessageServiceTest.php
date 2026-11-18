<?php

namespace Tests\Feature\Phase5;

use App\Services\Hl7MessageService;
use PHPUnit\Framework\TestCase;

class Hl7MessageServiceTest extends TestCase
{
    private Hl7MessageService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new Hl7MessageService();
    }

    // -------------------------------------------------------------------------
    // Parsing
    // -------------------------------------------------------------------------

    public function test_parse_splits_segments(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101120000||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P12345||Smith^John||19800101|M\r"
             . "OBR|1|TTM-100|LIS-200|CBC^Complete Blood Count^L\r"
             . "OBX|1|NM|WBC^White Blood Cells^L||4.5|10^9/L|4.0-11.0|N|||F\r";

        $result = $this->svc->parse($raw);

        $this->assertArrayHasKey('MSH', $result);
        $this->assertArrayHasKey('PID', $result);
        $this->assertArrayHasKey('OBR', $result);
        $this->assertArrayHasKey('OBX', $result);
    }

    public function test_parse_handles_lf_line_endings(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101||ORU^R01|MSG001|P|2.5\n"
             . "PID|1||P001||Doe^Jane\n";

        $result = $this->svc->parse($raw);

        $this->assertArrayHasKey('MSH', $result);
        $this->assertArrayHasKey('PID', $result);
    }

    public function test_parse_oru_r01_extracts_patient(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101120000||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P12345||Smith^John||19800115|M\r"
             . "OBR|1|TTM-100|LIS-200|CBC^Complete Blood Count^L|||20240101120000\r"
             . "OBX|1|NM|WBC^White Blood Cells^L^^4567-8^^LN||4.5|10^9/L|4.0-11.0|N|||F\r";

        $parsed = $this->svc->parseOruR01($raw);

        $this->assertEquals('P12345', $parsed['patient']['external_id']);
        $this->assertEquals('Smith', $parsed['patient']['last_name']);
        $this->assertEquals('John', $parsed['patient']['first_name']);
        $this->assertEquals('1980-01-15', $parsed['patient']['dob']);
        $this->assertEquals('m', $parsed['patient']['gender']);
    }

    public function test_parse_oru_r01_extracts_order(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101120000||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane\r"
             . "OBR|1|TTM-100|LIS-200|CBC^Complete Blood Count^L|||20240101120000||||||||||||||||||F\r"
             . "OBX|1|NM|GLU^Glucose^LN||5.0|mmol/L|3.9-7.1|N|||F\r";

        $parsed = $this->svc->parseOruR01($raw);

        $this->assertEquals('TTM-100', $parsed['order']['placer_order_number']);
        $this->assertEquals('LIS-200', $parsed['order']['filler_order_number']);
        $this->assertEquals('F', $parsed['order']['report_status']);
    }

    public function test_parse_oru_r01_extracts_results(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101120000||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane\r"
             . "OBR|1|TTM-100|LIS-200|PANEL^Test Panel^L\r"
             . "OBX|1|NM|WBC^WBC Count^L^^777-3^^LN||12.5|10^9/L|4.0-11.0|H|||F\r"
             . "OBX|2|NM|HGB^Hemoglobin^L^^718-7^^LN||8.0|g/dL|12.0-16.0|LL|||F\r";

        $parsed = $this->svc->parseOruR01($raw);

        $this->assertCount(2, $parsed['results']);

        $this->assertEquals('WBC', $parsed['results'][0]['test_code']);
        $this->assertEquals('high', $parsed['results'][0]['flag']);
        $this->assertFalse($parsed['results'][0]['is_critical']);

        $this->assertEquals('HGB', $parsed['results'][1]['test_code']);
        $this->assertEquals('critical_low', $parsed['results'][1]['flag']);
        $this->assertTrue($parsed['results'][1]['is_critical']);
    }

    public function test_parse_loinc_code_extracted_from_obx(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101120000||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane\r"
             . "OBR|1|TTM-100||PANEL^Panel^L\r"
             . "OBX|1|NM|GLU^Glucose^L^^2345-7^^LN||5.0|mmol/L|3.9-7.1|N|||F\r";

        $parsed = $this->svc->parseOruR01($raw);

        $this->assertEquals('2345-7', $parsed['results'][0]['loinc_code']);
    }

    // -------------------------------------------------------------------------
    // Building
    // -------------------------------------------------------------------------

    public function test_build_orm_o01_contains_msh_segment(): void
    {
        $msg = $this->svc->buildOrmO01(
            sendingApp:        'TTMED',
            sendingFacility:   'CLINIC1',
            receivingApp:      'LIS',
            receivingFacility: 'LAB',
            patientData: [
                'external_id' => 'P001',
                'last_name'   => 'Doe',
                'first_name'  => 'Jane',
                'dob'         => '1990-05-20',
                'gender'      => 'female',
            ],
            orderData: [
                'placer_order_number' => 'TTM-001',
                'priority'            => 'routine',
            ],
            orderItems: [
                ['code' => 'CBC', 'name' => 'Complete Blood Count', 'loinc_code' => '58410-2'],
            ],
        );

        $this->assertStringContainsString('MSH|', $msg);
        $this->assertStringContainsString('ORM^O01', $msg);
        $this->assertStringContainsString('PID|', $msg);
        $this->assertStringContainsString('ORC|NW|TTM-001', $msg);
        $this->assertStringContainsString('OBR|1|TTM-001', $msg);
    }

    public function test_build_orm_o01_stat_priority(): void
    {
        $msg = $this->svc->buildOrmO01(
            sendingApp: 'A', sendingFacility: 'B', receivingApp: 'C', receivingFacility: 'D',
            patientData: ['external_id' => 'P002', 'last_name' => 'X', 'first_name' => 'Y', 'gender' => 'male'],
            orderData: ['placer_order_number' => 'TTM-002', 'priority' => 'stat'],
            orderItems: [['code' => 'TT', 'name' => 'Test', 'loinc_code' => '']],
        );

        $this->assertStringContainsString('|S|', $msg);
    }

    public function test_build_ack_aa(): void
    {
        $ack = $this->svc->buildAck('MSG001', 'AA');

        $this->assertStringContainsString('MSH|', $ack);
        $this->assertStringContainsString('MSA|AA|MSG001', $ack);
    }

    public function test_build_ack_ae(): void
    {
        $ack = $this->svc->buildAck('MSG002', 'AE', 'Parse error');

        $this->assertStringContainsString('MSA|AE|MSG002', $ack);
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function test_empty_message_returns_empty_array(): void
    {
        $result = $this->svc->parse('');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_oru_r01_with_no_results(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane\r"
             . "OBR|1|TTM-100||PANEL^Panel^L\r";

        $parsed = $this->svc->parseOruR01($raw);

        $this->assertEmpty($parsed['results']);
    }

    public function test_hl7_date_parsing_from_8digit(): void
    {
        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane||19950320|F\r"
             . "OBR|1|TTM-100||PANEL^Panel^L\r";

        $parsed = $this->svc->parseOruR01($raw);

        $this->assertEquals('1995-03-20', $parsed['patient']['dob']);
    }

    public function test_multiple_obx_segments_all_captured(): void
    {
        $obxLines = '';
        for ($i = 1; $i <= 5; $i++) {
            $obxLines .= "OBX|{$i}|NM|TEST{$i}^Test {$i}^L||{$i}.0|unit|0-10|N|||F\r";
        }

        $raw = "MSH|^~\\&|LIS|LAB|TTMED|TTMED|20240101||ORU^R01|MSG001|P|2.5\r"
             . "PID|1||P001||Doe^Jane\r"
             . "OBR|1|TTM-100||PANEL^Panel^L\r"
             . $obxLines;

        $parsed = $this->svc->parseOruR01($raw);

        $this->assertCount(5, $parsed['results']);
    }
}
