<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationConfig extends Model
{
    protected $connection = 'tenant';

    protected $table = 'integration_configs';

    protected $fillable = [
        'type', 'provider', 'protocol',
        'endpoint_url', 'mllp_host', 'mllp_port',
        'sending_application', 'sending_facility',
        'receiving_application', 'receiving_facility',
        'api_key_encrypted', 'inbound_secret_encrypted',
        'is_active', 'send_orders', 'receive_results', 'notes',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'send_orders'     => 'boolean',
        'receive_results' => 'boolean',
    ];

    protected $hidden = ['api_key_encrypted', 'inbound_secret_encrypted'];

    public function setApiKey(string $plaintext): void
    {
        $this->api_key_encrypted = Crypt::encryptString($plaintext);
    }

    public function getApiKey(): ?string
    {
        return $this->api_key_encrypted ? Crypt::decryptString($this->api_key_encrypted) : null;
    }

    public function setInboundSecret(string $plaintext): void
    {
        $this->inbound_secret_encrypted = Crypt::encryptString($plaintext);
    }

    public function getInboundSecret(): ?string
    {
        return $this->inbound_secret_encrypted ? Crypt::decryptString($this->inbound_secret_encrypted) : null;
    }

    public function verifyHmac(string $payload, string $signature): bool
    {
        $secret = $this->getInboundSecret();
        if (!$secret) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha256', $payload, $secret),
            $signature
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function labLis(): ?self
    {
        return static::on('tenant')->where('type', 'lab_lis')->active()->first();
    }

    public static function imagingRis(): ?self
    {
        return static::on('tenant')->where('type', 'imaging_ris')->active()->first();
    }

    public static function imagingPacs(): ?self
    {
        return static::on('tenant')->where('type', 'imaging_pacs')->active()->first();
    }
}
