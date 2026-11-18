<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QueueNumberService
{
    public function next(int $branchId, \DateTimeInterface|string|null $date = null): int
    {
        $date = $date ? \Carbon\Carbon::parse($date)->toDateString() : today()->toDateString();

        return DB::connection('tenant')->transaction(function () use ($branchId, $date) {
            $seq = DB::connection('tenant')
                ->table('queue_number_sequences')
                ->where('branch_id', $branchId)
                ->where('queue_date', $date)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::connection('tenant')->table('queue_number_sequences')->insert([
                    'branch_id'   => $branchId,
                    'queue_date'  => $date,
                    'last_number' => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                return 1;
            }

            $next = $seq->last_number + 1;

            DB::connection('tenant')
                ->table('queue_number_sequences')
                ->where('id', $seq->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        });
    }

    public function currentMax(int $branchId, \DateTimeInterface|string|null $date = null): int
    {
        $date = $date ? \Carbon\Carbon::parse($date)->toDateString() : today()->toDateString();

        return (int) DB::connection('tenant')
            ->table('queue_number_sequences')
            ->where('branch_id', $branchId)
            ->where('queue_date', $date)
            ->value('last_number') ?? 0;
    }
}
