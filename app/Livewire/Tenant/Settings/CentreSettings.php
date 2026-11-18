<?php

namespace App\Livewire\Tenant\Settings;

use App\Jobs\RunTenantBackupJob;
use App\Services\AuditService;
use App\Services\BackupService;
use App\Services\CentreSettingsService;
use App\Tenancy\TenantContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CentreSettings extends Component
{
    // General
    public string $centreName = '';
    public string $centreAddress = '';
    public string $centrePhone = '';
    public string $centreEmail = '';
    public string $centreWebsite = '';
    public string $timezone = 'America/Port_of_Spain';
    public string $currency = 'TTD';
    public string $taxLabel = 'VAT';
    public string $taxRate = '12.5';
    public bool $taxEnabled = false;

    // Email (SMTP2GO)
    public string $smtpHost = 'mail.smtp2go.com';
    public string $smtpPort = '587';
    public string $smtpUsername = '';
    public string $smtpPassword = '';   // never displayed after save
    public string $smtpFromAddress = '';
    public string $smtpFromName = '';

    // Backup schedule
    public string $backupFrequency = 'daily';
    public int    $backupHour      = 2;
    public int    $backupDay       = 0;
    public int    $backupRetain    = 7;

    public string  $activeTab       = 'general';
    public string  $testEmailTo     = '';
    public ?string $testEmailStatus = null;
    public ?string $backupStatus    = null;

    public function mount(TenantContext $tenantContext): void
    {
        $settings = app(CentreSettingsService::class);

        $tenant              = $tenantContext->get();
        $this->backupFrequency = $tenant->backup_frequency ?? 'daily';
        $this->backupHour    = (int) ($tenant->backup_hour ?? 2);
        $this->backupDay     = (int) ($tenant->backup_day ?? 0);
        $this->backupRetain  = (int) ($tenant->backup_retain ?? 7);

        $this->centreName    = $settings->get('centre_name', '');
        $this->centreAddress = $settings->get('centre_address', '');
        $this->centrePhone   = $settings->get('centre_phone', '');
        $this->centreEmail   = $settings->get('centre_email', '');
        $this->centreWebsite = $settings->get('centre_website', '');
        $this->timezone      = $settings->get('timezone', 'America/Port_of_Spain');
        $this->currency      = $settings->get('currency', 'TTD');
        $this->taxLabel      = $settings->get('tax_label', 'VAT');
        $this->taxRate       = $settings->get('tax_rate', '12.5');
        $this->taxEnabled    = (bool) $settings->get('tax_enabled', false);

        $this->smtpHost        = $settings->get('smtp_host', 'mail.smtp2go.com');
        $this->smtpPort        = $settings->get('smtp_port', '587');
        $this->smtpUsername    = $settings->get('smtp_username', '');
        $this->smtpFromAddress = $settings->get('smtp_from_address', '');
        $this->smtpFromName    = $settings->get('smtp_from_name', '');
        // smtp_password is encrypted — never load into the component
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'centreName'  => 'required|string|max:200',
            'centreEmail' => 'nullable|email|max:150',
            'timezone'    => 'required|string|max:60',
            'taxRate'     => 'required|numeric|min:0|max:100',
        ]);

        $settings = app(CentreSettingsService::class);
        $settings->setMany([
            'centre_name'    => $this->centreName,
            'centre_address' => $this->centreAddress,
            'centre_phone'   => $this->centrePhone,
            'centre_email'   => $this->centreEmail,
            'centre_website' => $this->centreWebsite,
            'timezone'       => $this->timezone,
            'currency'       => $this->currency,
            'tax_label'      => $this->taxLabel,
            'tax_rate'       => $this->taxRate,
        ]);
        $settings->set('tax_enabled', $this->taxEnabled, 'boolean');

        app(AuditService::class)->logTenant(auth()->guard('tenant')->id(), 'settings.general.updated');

        session()->flash('message', 'General settings saved.');
    }

    public function saveEmail(): void
    {
        $this->validate([
            'smtpHost'        => 'required|string|max:100',
            'smtpPort'        => 'required|integer|between:1,65535',
            'smtpUsername'    => 'required|string|max:150',
            'smtpFromAddress' => 'required|email|max:150',
            'smtpFromName'    => 'required|string|max:100',
        ]);

        $settings = app(CentreSettingsService::class);
        $settings->setMany([
            'smtp_host'         => $this->smtpHost,
            'smtp_port'         => $this->smtpPort,
            'smtp_username'     => $this->smtpUsername,
            'smtp_from_address' => $this->smtpFromAddress,
            'smtp_from_name'    => $this->smtpFromName,
        ]);

        if ($this->smtpPassword) {
            $settings->set('smtp_password', $this->smtpPassword, 'encrypted');
            $this->smtpPassword = '';
        }

        app(AuditService::class)->logTenant(auth()->guard('tenant')->id(), 'settings.email.updated');

        session()->flash('message', 'Email settings saved.');
    }

    public function sendTestEmail(): void
    {
        $this->validate(['testEmailTo' => 'required|email']);

        try {
            $settings = app(CentreSettingsService::class);
            $password = $settings->get('smtp_password');

            if (!$password || !$this->smtpUsername) {
                $this->testEmailStatus = 'error:Email settings not configured.';
                return;
            }

            config([
                'mail.mailers.smtp.host'       => $this->smtpHost,
                'mail.mailers.smtp.port'       => $this->smtpPort,
                'mail.mailers.smtp.username'   => $this->smtpUsername,
                'mail.mailers.smtp.password'   => $password,
                'mail.from.address'            => $this->smtpFromAddress,
                'mail.from.name'               => $this->smtpFromName,
            ]);

            \Illuminate\Support\Facades\Mail::raw(
                'This is a test email from ' . ($this->centreName ?: 'TTMed') . '.',
                fn ($m) => $m->to($this->testEmailTo)->subject('TTMed Email Test')
            );

            $this->testEmailStatus = 'success';
        } catch (\Throwable $e) {
            $this->testEmailStatus = 'error:' . $e->getMessage();
        }
    }

    public function saveBackupSchedule(TenantContext $tenantContext): void
    {
        $this->checkPermission('settings.manage');

        $this->validate([
            'backupFrequency' => 'required|in:disabled,daily,weekly',
            'backupHour'      => 'required|integer|between:0,23',
            'backupDay'       => 'required|integer|between:0,6',
            'backupRetain'    => 'required|integer|between:1,30',
        ]);

        $tenantContext->get()->update([
            'backup_frequency' => $this->backupFrequency,
            'backup_hour'      => $this->backupHour,
            'backup_day'       => $this->backupDay,
            'backup_retain'    => $this->backupRetain,
        ]);

        app(AuditService::class)->logTenant('settings.backup.schedule_updated', 'Tenant', $tenantContext->get()->id);

        session()->flash('message', 'Backup schedule saved.');
    }

    public function triggerBackup(TenantContext $tenantContext): void
    {
        $this->checkPermission('settings.manage');

        RunTenantBackupJob::dispatch($tenantContext->get())->onQueue('backups');

        $this->backupStatus = 'queued';
    }

    public function render(TenantContext $tenantContext, BackupService $backupService): \Illuminate\View\View
    {
        $backups = $this->activeTab === 'backups'
            ? $backupService->listBackups($tenantContext->get())
            : [];

        return view('livewire.tenant.settings.centre-settings', [
            'timezones'      => \DateTimeZone::listIdentifiers(\DateTimeZone::AMERICA),
            'backups'        => $backups,
            'backupLastRun'  => $tenantContext->get()->backup_last_run_at,
        ]);
    }
}
