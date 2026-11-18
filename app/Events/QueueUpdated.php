<?php

namespace App\Events;

use App\Models\Tenant\QueueEntry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $tenantUuid,
        public readonly int    $branchId,
        public readonly string $action,
        public readonly array  $payload,
    ) {}

    public function broadcastOn(): Channel
    {
        // Channel is tenant-scoped — no cross-tenant leakage possible
        return new Channel("tenant.{$this->tenantUuid}.branch.{$this->branchId}.queue");
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action'   => $this->action,
            'branchId' => $this->branchId,
            'payload'  => $this->payload,
        ];
    }

    public static function entryPayload(QueueEntry $entry): array
    {
        return [
            'id'           => $entry->id,
            'queueNumber'  => $entry->queue_number,
            'patientName'  => $entry->patient?->display_name ?? '',
            'doctorId'     => $entry->doctor_id,
            'status'       => $entry->status,
            'statusLabel'  => $entry->status_label,
            'isPriority'   => $entry->is_priority,
            'visitType'    => $entry->visit_type,
        ];
    }
}
