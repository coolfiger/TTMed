<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Patient;
use App\Models\Tenant\QueueEntry;
use App\Services\CentreSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class QueueScreenController extends Controller
{
    private function resolveBranch(string $code): Branch
    {
        return Branch::on('tenant')
            ->where('is_active', true)
            ->where(function ($q) use ($code) {
                $q->whereRaw('UPPER(screen_code) = ?', [strtoupper($code)])
                  ->orWhere('id', is_numeric($code) ? (int) $code : 0);
            })
            ->firstOrFail();
    }

    public function show(string $code): \Illuminate\View\View
    {
        $branch   = $this->resolveBranch($code);
        $timezone = app(CentreSettingsService::class)->get('queue_screen_timezone', 'America/Port_of_Spain');
        return view('tenant.queue-screen', compact('branch', 'timezone'));
    }

    public function data(string $code): JsonResponse
    {
        $branch  = $this->resolveBranch($code);
        $id      = $branch->id;
        $today   = today();
        $flashAt = now()->subSeconds(90);

        // Patients called in the last 90 seconds — flash these on screen
        $callingNow = QueueEntry::on('tenant')
            ->with(['patient:id,first_name,last_name', 'doctor.staffProfile:id,first_name,last_name'])
            ->where('branch_id', $id)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [QueueEntry::STATUS_WITH_NURSE, QueueEntry::STATUS_WITH_DOCTOR])
            ->where('called_at', '>=', $flashAt)
            ->orderByDesc('called_at')
            ->get()
            ->map(fn ($e) => $this->callingEntry($e));

        // Last person called today who is no longer in the flash window
        $lastCalled = QueueEntry::on('tenant')
            ->with(['patient:id,first_name,last_name', 'doctor.staffProfile:id,first_name,last_name'])
            ->where('branch_id', $id)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [QueueEntry::STATUS_WITH_NURSE, QueueEntry::STATUS_WITH_DOCTOR])
            ->where('called_at', '<', $flashAt)
            ->whereNotNull('called_at')
            ->orderByDesc('called_at')
            ->first();

        // Waiting queue with status so the screen can show N / D badges
        $waiting = QueueEntry::on('tenant')
            ->with('patient:id,first_name,last_name')
            ->where('branch_id', $id)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [QueueEntry::STATUS_WAITING, QueueEntry::STATUS_AWAITING_DOCTOR])
            ->orderByDesc('is_priority')
            ->orderBy('queue_number')
            ->limit(25)
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'queue_number' => $e->queue_number,
                'name'         => $this->screenName($e->patient),
                'is_priority'  => (bool) $e->is_priority,
                'badge'        => $e->status === QueueEntry::STATUS_AWAITING_DOCTOR ? 'D' : 'N',
            ]);

        $inConsultation = QueueEntry::on('tenant')
            ->where('branch_id', $id)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueEntry::STATUS_WITH_NURSE,
                QueueEntry::STATUS_WITH_DOCTOR,
                QueueEntry::STATUS_AWAITING_CHECKOUT,
            ])
            ->count();

        return response()->json([
            'branch'          => $branch->name,
            'date'            => now()->format('d F Y'),
            'time'            => now()->format('H:i'),
            'calling_now'     => $callingNow,
            'last_called'     => $lastCalled ? $this->callingEntry($lastCalled) : null,
            'waiting'         => $waiting,
            'in_consultation' => $inConsultation,
        ]);
    }

    public function news(): JsonResponse
    {
        $rssUrl = app(CentreSettingsService::class)->get('queue_screen_rss_url', '')
            ?: 'https://tt.loopnews.com/rss.xml';

        try {
            $response = Http::timeout(10)->withoutVerifying()->get($rssUrl);
            if (!$response->successful()) {
                \Log::warning('QueueScreen news fetch failed', ['url' => $rssUrl, 'status' => $response->status()]);
                return response()->json(['items' => [], 'debug' => 'http_' . $response->status()]);
            }

            $xml = @simplexml_load_string($response->body());
            if ($xml === false) {
                \Log::warning('QueueScreen RSS parse failed', ['url' => $rssUrl]);
                return response()->json(['items' => [], 'debug' => 'parse_error']);
            }

            $items = [];
            foreach ($xml->channel->item as $item) {
                $items[] = (string) $item->title;
                if (count($items) >= 15) break;
            }

            return response()->json(['items' => $items]);
        } catch (\Throwable $e) {
            \Log::warning('QueueScreen news exception', ['url' => $rssUrl, 'error' => $e->getMessage()]);
            return response()->json(['items' => [], 'debug' => $e->getMessage()]);
        }
    }

    private function callingEntry(QueueEntry $e): array
    {
        $doctorName = null;
        if ($e->status === QueueEntry::STATUS_WITH_DOCTOR && $e->doctor?->staffProfile) {
            $sp         = $e->doctor->staffProfile;
            $doctorName = 'Dr. ' . trim($sp->first_name . ' ' . $sp->last_name);
        }

        return [
            'id'           => $e->id,
            'queue_number' => $e->queue_number,
            'name'         => $this->screenName($e->patient),
            'destination'  => $doctorName ?? ($e->status === QueueEntry::STATUS_WITH_NURSE ? 'Nurse Station' : 'Consultation Room'),
            'called_at'    => $e->called_at?->toIso8601String(),
        ];
    }

    private function screenName(?Patient $patient): string
    {
        if (!$patient) {
            return '—';
        }
        $first = ucfirst(strtolower(trim($patient->first_name ?? '')));
        $last  = strtoupper(substr(trim($patient->last_name ?? ''), 0, 1));
        return $last ? "{$first} {$last}." : $first;
    }
}
