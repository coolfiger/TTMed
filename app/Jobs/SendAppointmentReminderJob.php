<?php

namespace App\Jobs;

use App\Models\Tenant\Appointment;
use App\Services\CentreSettingsService;
use App\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int    $appointmentId,
        private readonly string $recipientEmail,
        private readonly string $tenantUuid,
    ) {}

    public function handle(TenantContext $tenantContext, CentreSettingsService $settings): void
    {
        $appointment = Appointment::on('tenant')
            ->with(['patient', 'doctor.staffProfile', 'branch'])
            ->find($this->appointmentId);

        if (!$appointment || !$appointment->isActive()) {
            return;
        }

        // Configure mailer from tenant SMTP2GO credentials
        $host     = $settings->get('smtp_host', 'mail.smtp2go.com');
        $port     = (int) $settings->get('smtp_port', 2525);
        $username = $settings->get('smtp_username', '');
        $password = $settings->get('smtp_password');
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

        $dateFormatted = $appointment->appointment_date->format('l, d F Y');
        $doctorName    = 'Dr. ' . ($appointment->doctor->staffProfile->full_name ?? 'your doctor');
        $branch        = $appointment->branch->name ?? '';

        try {
            Mail::send([], [], function ($message) use (
                $appointment, $fromAddr, $fromName, $dateFormatted, $doctorName, $branch
            ) {
                $message
                    ->to($this->recipientEmail, $appointment->patient->full_name)
                    ->from($fromAddr, $fromName)
                    ->subject("Appointment Reminder — {$dateFormatted}")
                    ->html(
                        "Dear {$appointment->patient->full_name},<br><br>" .
                        "This is a reminder that you have an appointment tomorrow, <strong>{$dateFormatted}</strong> " .
                        "at <strong>{$appointment->appointment_time}</strong> with {$doctorName}" .
                        ($branch ? " at {$branch}" : '') . ".<br><br>" .
                        "Please arrive 10 minutes early. If you need to cancel, contact us as soon as possible.<br><br>" .
                        "Regards,<br>{$fromName}"
                    );
            });

            DB::connection('tenant')->table('appointment_reminders')->insert([
                'appointment_id' => $this->appointmentId,
                'recipient_email' => $this->recipientEmail,
                'status'          => 'sent',
                'sent_at'         => now(),
                'created_at'      => now(),
            ]);

            $appointment->update([
                'reminder_sent'    => true,
                'reminder_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("SendAppointmentReminderJob failed: {$e->getMessage()}");

            DB::connection('tenant')->table('appointment_reminders')->insert([
                'appointment_id'  => $this->appointmentId,
                'recipient_email' => $this->recipientEmail,
                'status'          => 'failed',
                'error_message'   => $e->getMessage(),
                'created_at'      => now(),
            ]);
        }
    }
}
