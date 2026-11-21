<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Patient;
use App\Models\Tenant\QueueEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class QueueScreenController extends Controller
{
    public function show(int $branchId): \Illuminate\View\View
    {
        $branch = Branch::on('tenant')
            ->where('id', $branchId)
            ->where('is_active', true)
            ->firstOrFail();

        return view('tenant.queue-screen', compact('branch'));
    }

    public function data(int $branchId): JsonResponse
    {
        $branch = Branch::on('tenant')
            ->where('id', $branchId)
            ->where('is_active', true)
            ->firstOrFail();

        $today       = today();
        $flashWindow = now()->subSeconds(90);

        // Patients currently being called (called within last 90 seconds)
        $calling = QueueEntry::on('tenant')
            ->with('patient:id,first_name,last_name')
            ->where('branch_id', $branchId)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueEntry::STATUS_WITH_NURSE,
                QueueEntry::STATUS_WITH_DOCTOR,
            ])
            ->where('called_at', '>=', $flashWindow)
            ->orderByDesc('called_at')
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'queue_number' => $e->queue_number,
                'name'         => $this->screenName($e->patient),
                'destination'  => $e->status === QueueEntry::STATUS_WITH_NURSE
                    ? 'Nurse Station'
                    : 'Consultation Room',
                'called_at'    => $e->called_at?->toIso8601String(),
            ]);

        // Waiting queue
        $waiting = QueueEntry::on('tenant')
            ->with('patient:id,first_name,last_name')
            ->where('branch_id', $branchId)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueEntry::STATUS_WAITING,
                QueueEntry::STATUS_AWAITING_DOCTOR,
            ])
            ->orderByDesc('is_priority')
            ->orderBy('queue_number')
            ->limit(25)
            ->get()
            ->map(fn ($e) => [
                'id'          => $e->id,
                'queue_number' => $e->queue_number,
                'name'        => $this->screenName($e->patient),
                'is_priority' => (bool) $e->is_priority,
            ]);

        $inConsultation = QueueEntry::on('tenant')
            ->where('branch_id', $branchId)
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueEntry::STATUS_WITH_NURSE,
                QueueEntry::STATUS_WITH_DOCTOR,
                QueueEntry::STATUS_AWAITING_CHECKOUT,
            ])
            ->count();

        return response()->json([
            'branch'         => $branch->name,
            'date'           => now()->format('d F Y'),
            'time'           => now()->format('H:i'),
            'calling'        => $calling,
            'waiting'        => $waiting,
            'in_consultation' => $inConsultation,
        ]);
    }

    // Show first name + last initial only — enough to identify without full exposure
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
