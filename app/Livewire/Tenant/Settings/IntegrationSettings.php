<?php

namespace App\Livewire\Tenant\Settings;

use App\Models\Tenant\IntegrationConfig;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app-workspace')]
class IntegrationSettings extends Component
{
    // ── Lab LIS ──────────────────────────────────────────────────────────
    public bool   $lisEnabled         = false;
    public string $lisProvider        = '';
    public string $lisProtocol        = 'hl7_v2';
    public string $lisEndpointUrl     = '';
    public string $lisMllpHost        = '';
    public string $lisMllpPort        = '2575';
    public string $lisSendingApp      = 'TTMED';
    public string $lisSendingFacility = 'TTMED';
    public string $lisReceivingApp    = '';
    public string $lisReceivingFacility = '';
    public string $lisApiKey          = '';
    public string $lisInboundSecret   = '';
    public bool   $lisSendOrders      = false;
    public bool   $lisReceiveResults  = true;
    public string $lisNotes           = '';

    // ── Imaging RIS ───────────────────────────────────────────────────────
    public bool   $risEnabled          = false;
    public string $risProvider         = '';
    public string $risProtocol         = 'hl7_v2';
    public string $risEndpointUrl      = '';
    public string $risMllpHost         = '';
    public string $risMllpPort         = '2575';
    public string $risSendingApp       = 'TTMED';
    public string $risSendingFacility  = 'TTMED';
    public string $risReceivingApp     = '';
    public string $risReceivingFacility = '';
    public string $risApiKey           = '';
    public string $risInboundSecret    = '';
    public bool   $risSendOrders       = false;
    public bool   $risReceiveResults   = true;
    public string $risNotes            = '';

    // ── Imaging PACS ─────────────────────────────────────────────────────
    public bool   $pacsEnabled         = false;
    public string $pacsProvider        = '';
    public string $pacsProtocol        = 'vepro';
    public string $pacsEndpointUrl     = '';
    public string $pacsApiKey          = '';
    public string $pacsInboundSecret   = '';
    public bool   $pacsReceiveResults  = true;
    public string $pacsNotes           = '';

    // ── UI state ─────────────────────────────────────────────────────────
    public string $activeTab      = 'lis';
    public string $successMessage = '';
    public string $errorMessage   = '';
    public string $testResult     = '';

    public function mount(): void
    {
        $this->checkPermission('settings.manage');
        $this->loadLis();
        $this->loadRis();
        $this->loadPacs();
    }

    private function loadLis(): void
    {
        $cfg = IntegrationConfig::on('tenant')->where('type', 'lab_lis')->first();
        if (!$cfg) return;
        $this->lisEnabled          = $cfg->is_active;
        $this->lisProvider         = $cfg->provider ?? '';
        $this->lisProtocol         = $cfg->protocol ?? 'hl7_v2';
        $this->lisEndpointUrl      = $cfg->endpoint_url ?? '';
        $this->lisMllpHost         = $cfg->mllp_host ?? '';
        $this->lisMllpPort         = (string) ($cfg->mllp_port ?? '2575');
        $this->lisSendingApp       = $cfg->sending_application ?? 'TTMED';
        $this->lisSendingFacility  = $cfg->sending_facility ?? 'TTMED';
        $this->lisReceivingApp     = $cfg->receiving_application ?? '';
        $this->lisReceivingFacility = $cfg->receiving_facility ?? '';
        $this->lisSendOrders       = $cfg->send_orders;
        $this->lisReceiveResults   = $cfg->receive_results;
        $this->lisNotes            = $cfg->notes ?? '';
        // Keys are never re-displayed after save; left blank intentionally
    }

    private function loadRis(): void
    {
        $cfg = IntegrationConfig::on('tenant')->where('type', 'imaging_ris')->first();
        if (!$cfg) return;
        $this->risEnabled           = $cfg->is_active;
        $this->risProvider          = $cfg->provider ?? '';
        $this->risProtocol          = $cfg->protocol ?? 'hl7_v2';
        $this->risEndpointUrl       = $cfg->endpoint_url ?? '';
        $this->risMllpHost          = $cfg->mllp_host ?? '';
        $this->risMllpPort          = (string) ($cfg->mllp_port ?? '2575');
        $this->risSendingApp        = $cfg->sending_application ?? 'TTMED';
        $this->risSendingFacility   = $cfg->sending_facility ?? 'TTMED';
        $this->risReceivingApp      = $cfg->receiving_application ?? '';
        $this->risReceivingFacility = $cfg->receiving_facility ?? '';
        $this->risSendOrders        = $cfg->send_orders;
        $this->risReceiveResults    = $cfg->receive_results;
        $this->risNotes             = $cfg->notes ?? '';
    }

    private function loadPacs(): void
    {
        $cfg = IntegrationConfig::on('tenant')->where('type', 'imaging_pacs')->first();
        if (!$cfg) return;
        $this->pacsEnabled        = $cfg->is_active;
        $this->pacsProvider       = $cfg->provider ?? '';
        $this->pacsProtocol       = $cfg->protocol ?? 'vepro';
        $this->pacsEndpointUrl    = $cfg->endpoint_url ?? '';
        $this->pacsReceiveResults = $cfg->receive_results;
        $this->pacsNotes          = $cfg->notes ?? '';
    }

    // ── Save ──────────────────────────────────────────────────────────────

    public function saveLis(): void
    {
        $this->checkPermission('settings.manage');
        $this->validate([
            'lisEndpointUrl' => 'nullable|url|max:500',
            'lisMllpPort'    => 'nullable|integer|min:1|max:65535',
        ]);

        $cfg = IntegrationConfig::on('tenant')->firstOrNew(['type' => 'lab_lis']);
        $cfg->fill([
            'type'                   => 'lab_lis',
            'provider'               => $this->lisProvider ?: null,
            'protocol'               => $this->lisProtocol,
            'endpoint_url'           => $this->lisEndpointUrl ?: null,
            'mllp_host'              => $this->lisMllpHost ?: null,
            'mllp_port'              => $this->lisMllpPort ?: null,
            'sending_application'    => $this->lisSendingApp ?: 'TTMED',
            'sending_facility'       => $this->lisSendingFacility ?: 'TTMED',
            'receiving_application'  => $this->lisReceivingApp ?: null,
            'receiving_facility'     => $this->lisReceivingFacility ?: null,
            'is_active'              => $this->lisEnabled,
            'send_orders'            => $this->lisSendOrders,
            'receive_results'        => $this->lisReceiveResults,
            'notes'                  => $this->lisNotes ?: null,
        ]);

        if ($this->lisApiKey !== '') {
            $cfg->setApiKey($this->lisApiKey);
            $this->lisApiKey = '';
        }
        if ($this->lisInboundSecret !== '') {
            $cfg->setInboundSecret($this->lisInboundSecret);
            $this->lisInboundSecret = '';
        }

        $cfg->save();

        app(AuditService::class)->log('settings.integration.saved', 'Lab LIS integration settings updated', ['type' => 'lab_lis']);
        $this->successMessage = 'Lab LIS settings saved.';
    }

    public function saveRis(): void
    {
        $this->checkPermission('settings.manage');
        $this->validate([
            'risEndpointUrl' => 'nullable|url|max:500',
            'risMllpPort'    => 'nullable|integer|min:1|max:65535',
        ]);

        $cfg = IntegrationConfig::on('tenant')->firstOrNew(['type' => 'imaging_ris']);
        $cfg->fill([
            'type'                   => 'imaging_ris',
            'provider'               => $this->risProvider ?: null,
            'protocol'               => $this->risProtocol,
            'endpoint_url'           => $this->risEndpointUrl ?: null,
            'mllp_host'              => $this->risMllpHost ?: null,
            'mllp_port'              => $this->risMllpPort ?: null,
            'sending_application'    => $this->risSendingApp ?: 'TTMED',
            'sending_facility'       => $this->risSendingFacility ?: 'TTMED',
            'receiving_application'  => $this->risReceivingApp ?: null,
            'receiving_facility'     => $this->risReceivingFacility ?: null,
            'is_active'              => $this->risEnabled,
            'send_orders'            => $this->risSendOrders,
            'receive_results'        => $this->risReceiveResults,
            'notes'                  => $this->risNotes ?: null,
        ]);

        if ($this->risApiKey !== '') {
            $cfg->setApiKey($this->risApiKey);
            $this->risApiKey = '';
        }
        if ($this->risInboundSecret !== '') {
            $cfg->setInboundSecret($this->risInboundSecret);
            $this->risInboundSecret = '';
        }

        $cfg->save();

        app(AuditService::class)->log('settings.integration.saved', 'Imaging RIS integration settings updated', ['type' => 'imaging_ris']);
        $this->successMessage = 'Imaging RIS settings saved.';
    }

    public function savePacs(): void
    {
        $this->checkPermission('settings.manage');
        $this->validate([
            'pacsEndpointUrl' => 'nullable|url|max:500',
        ]);

        $cfg = IntegrationConfig::on('tenant')->firstOrNew(['type' => 'imaging_pacs']);
        $cfg->fill([
            'type'            => 'imaging_pacs',
            'provider'        => $this->pacsProvider ?: null,
            'protocol'        => $this->pacsProtocol,
            'endpoint_url'    => $this->pacsEndpointUrl ?: null,
            'is_active'       => $this->pacsEnabled,
            'send_orders'     => false,
            'receive_results' => $this->pacsReceiveResults,
            'notes'           => $this->pacsNotes ?: null,
        ]);

        if ($this->pacsApiKey !== '') {
            $cfg->setApiKey($this->pacsApiKey);
            $this->pacsApiKey = '';
        }
        if ($this->pacsInboundSecret !== '') {
            $cfg->setInboundSecret($this->pacsInboundSecret);
            $this->pacsInboundSecret = '';
        }

        $cfg->save();

        app(AuditService::class)->log('settings.integration.saved', 'Imaging PACS integration settings updated', ['type' => 'imaging_pacs']);
        $this->successMessage = 'Imaging PACS settings saved.';
    }

    // ── Test connection ───────────────────────────────────────────────────

    public function testLisConnection(): void
    {
        $this->checkPermission('settings.manage');
        $cfg = IntegrationConfig::on('tenant')->where('type', 'lab_lis')->first();
        $this->testResult = $this->pingConfig($cfg);
    }

    public function testRisConnection(): void
    {
        $this->checkPermission('settings.manage');
        $cfg = IntegrationConfig::on('tenant')->where('type', 'imaging_ris')->first();
        $this->testResult = $this->pingConfig($cfg);
    }

    public function testPacsConnection(): void
    {
        $this->checkPermission('settings.manage');
        $cfg = IntegrationConfig::on('tenant')->where('type', 'imaging_pacs')->first();
        $this->testResult = $this->pingConfig($cfg);
    }

    private function pingConfig(?IntegrationConfig $cfg): string
    {
        if (!$cfg || !$cfg->is_active) {
            return 'error:Integration is not configured or not enabled.';
        }

        if ($cfg->mllp_host && $cfg->mllp_port) {
            $socket = @fsockopen($cfg->mllp_host, (int) $cfg->mllp_port, $errno, $errstr, 5);
            if ($socket) {
                fclose($socket);
                return 'ok:MLLP connection to ' . $cfg->mllp_host . ':' . $cfg->mllp_port . ' succeeded.';
            }
            return 'error:MLLP connection to ' . $cfg->mllp_host . ':' . $cfg->mllp_port . ' failed — ' . $errstr;
        }

        if ($cfg->endpoint_url) {
            $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5, 'ignore_errors' => true]]);
            $result  = @file_get_contents($cfg->endpoint_url . '/ping', false, $context);
            $code    = isset($http_response_header[0]) ? (int) substr($http_response_header[0], 9, 3) : 0;
            if ($code && $code < 500) {
                return 'ok:HTTP endpoint responded with ' . $code . '.';
            }
            return 'error:HTTP endpoint ' . $cfg->endpoint_url . ' did not respond (' . ($code ?: 'no response') . ').';
        }

        return 'error:No endpoint configured to test.';
    }

    public function render()
    {
        return view('livewire.tenant.settings.integration-settings');
    }

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }
}
