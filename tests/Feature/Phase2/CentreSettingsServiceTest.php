<?php

namespace Tests\Feature\Phase2;

use App\Services\CentreSettingsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CentreSettingsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('tenant')->statement('CREATE TABLE IF NOT EXISTS centre_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            branch_id INTEGER NULL,
            key VARCHAR(100) NOT NULL,
            value TEXT NULL,
            type VARCHAR(20) NOT NULL DEFAULT "string",
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(branch_id, key)
        )');
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->statement('DROP TABLE IF EXISTS centre_settings');
        parent::tearDown();
    }

    private function svc(): CentreSettingsService
    {
        return new CentreSettingsService();
    }

    public function test_set_and_get_string_value(): void
    {
        $svc = $this->svc();
        $svc->set('centre.name', 'Test Clinic');

        $this->assertSame('Test Clinic', $svc->get('centre.name'));
    }

    public function test_set_and_get_boolean_value(): void
    {
        $svc = $this->svc();
        $svc->set('tax.enabled', true, 'boolean');

        $this->assertTrue($svc->get('tax.enabled'));
    }

    public function test_set_and_get_integer_value(): void
    {
        $svc = $this->svc();
        $svc->set('tax.rate', 1250, 'integer');

        $this->assertSame(1250, $svc->get('tax.rate'));
    }

    public function test_returns_default_when_key_missing(): void
    {
        $svc = $this->svc();

        $this->assertSame('fallback', $svc->get('nonexistent.key', 'fallback'));
        $this->assertNull($svc->get('nonexistent.key2'));
    }

    public function test_set_many_stores_multiple_values(): void
    {
        $svc = $this->svc();
        $svc->setMany([
            'general.phone'   => '868-123-4567',
            'general.address' => '1 Main St, POS',
        ]);

        $result = $svc->getMany(['general.phone', 'general.address']);

        $this->assertSame('868-123-4567', $result['general.phone']);
        $this->assertSame('1 Main St, POS', $result['general.address']);
    }

    public function test_set_encrypted_does_not_store_plaintext(): void
    {
        $svc = $this->svc();
        $svc->set('smtp.password', 'super_secret', 'encrypted');

        $raw = DB::connection('tenant')
            ->table('centre_settings')
            ->where('key', 'smtp.password')
            ->value('value');

        $this->assertNotSame('super_secret', $raw);
        $this->assertNotNull($raw);
    }

    public function test_in_memory_cache_returns_same_value(): void
    {
        $svc = $this->svc();
        $svc->set('cache.test', 'alpha');

        $this->assertSame('alpha', $svc->get('cache.test'));
        $this->assertSame('alpha', $svc->get('cache.test'));
    }
}
