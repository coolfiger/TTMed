<?php

namespace App\Jobs;

use App\Models\Tenant\Prescription;
use App\Models\Tenant\PrescriptionDeliveryLog;
use App\Services\CentreSettingsService;
use App\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPrescriptionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int    $prescriptionId,
        private readonly int    $deliveryLogId,
        private readonly string $recipientEmail,
        private readonly string $tenantUuid,
    ) {}

    public function handle(TenantContext $tenantContext, CentreSettingsService $settings): void
    {
        $log = PrescriptionDeliveryLog::on('tenant')->find($this->deliveryLogId);

        if (!$log) {
            return;
        }

        $prescription = Prescription::on('tenant')
            ->with(['patient', 'doctor.staffProfile', 'items'])
            ->find($this->prescriptionId);

        if (!$prescription || !$prescription->isIssued()) {
            $log->update(['status' => 'failed', 'error_message' => 'Prescription not found or not issued.']);
            return;
        }

        // Configure mailer from tenant SMTP2GO credentials
        $host     = $settings->get('smtp_host', 'mail.smtp2go.com');
        $port     = (int) $settings->get('smtp_port', 2525);
        $username = $settings->get('smtp_username', '');
        $password = $settings->get('smtp_password', null, null); // encrypted, resolved by CentreSettingsService
        $fromName = $settings->get('email_from_name', 'TTMed');
        $fromAddr = $settings->get('email_from_address', 'noreply@ttmed.app');

        config([
            'mail.mailers.smtp.host'       => $host,
            'mail.mailers.smtp.port'       => $port,
            'mail.mailers.smtp.username'   => $username,
            'mail.mailers.smtp.password'   => $password,
            'mail.mailers.smtp.encryption' => 'tls',
            'mail.from.address'            => $fromAddr,
            'mail.from.name'               => $fromName,
        ]);

        $log->update(['status' => 'sent', 'dispatched_at' => now()]);

        try {
            Mail::send([], [], function ($message) use ($prescription, $fromAddr, $fromName) {
                $message
                    ->to($this->recipientEmail)
                    ->from($fromAddr, $fromName)
                    ->subject("Prescription {$prescription->prescription_number}")
                    ->attach($prescription->pdf_path, [
                        'as'   => "Prescription_{$prescription->prescription_number}.pdf",
                        'mime' => 'application/pdf',
                    ])
                    ->html(
                        "Dear {$prescription->patient->full_name},<br><br>" .
                        "Please find your prescription attached.<br><br>" .
                        "Regards,<br>{$fromName}"
                    );
            });

            $log->update(['status' => 'sent', 'dispatched_at' => now()]);
        } catch (\Exception $e) {
            Log::error("SendPrescriptionEmailJob failed: {$e->getMessage()}");
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $e): void
    {
        $log = PrescriptionDeliveryLog::on('tenant')->find($this->deliveryLogId);
        $log?->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
    }
}
