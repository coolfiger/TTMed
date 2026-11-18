<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PrescriptionDeliveryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Smtp2goWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        // Verify SMTP2GO HMAC signature
        $secret = config('services.smtp2go.webhook_secret');
        if ($secret) {
            $signature = $request->header('X-Smtp2go-Signature');
            $expected  = hash_hmac('sha256', $request->getContent(), $secret);
            if (!$signature || !hash_equals($expected, $signature)) {
                return response('Forbidden', 403);
            }
        }

        $payload = $request->json()->all();
        $event   = $payload['event'] ?? null;
        $msgId   = $payload['message_id'] ?? null;

        if (!$msgId) {
            return response('OK', 200);
        }

        $log = PrescriptionDeliveryLog::on('tenant')
            ->where('smtp2go_message_id', $msgId)
            ->first();

        if (!$log) {
            Log::info("SMTP2GO webhook: no delivery log for message_id {$msgId}");
            return response('OK', 200);
        }

        $status = match ($event) {
            'delivered'   => 'delivered',
            'bounced'     => 'bounced',
            'failed'      => 'failed',
            default       => null,
        };

        if ($status) {
            $update = ['status' => $status];
            if ($status === 'delivered') {
                $update['delivered_at'] = now();
            }
            if (isset($payload['error_message'])) {
                $update['error_message'] = $payload['error_message'];
            }
            $log->update($update);
        }

        return response('OK', 200);
    }
}
