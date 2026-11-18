<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PatientNumberService
{
    public function generate(): string
    {
        return DB::connection('tenant')->transaction(function () {
            $seq = DB::connection('tenant')
                ->table('patient_number_sequences')
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                // Bootstrap sequence from centre settings
                $prefix    = app(CentreSettingsService::class)->get('patient_number.prefix', 'PT');
                $padLength = (int) app(CentreSettingsService::class)->get('patient_number.pad_length', 6);

                DB::connection('tenant')->table('patient_number_sequences')->insert([
                    'prefix'      => $prefix,
                    'last_number' => 1,
                    'pad_length'  => $padLength,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                return $prefix . '-' . str_pad('1', $padLength, '0', STR_PAD_LEFT);
            }

            $next = $seq->last_number + 1;

            DB::connection('tenant')
                ->table('patient_number_sequences')
                ->where('id', $seq->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return $seq->prefix . '-' . str_pad((string) $next, $seq->pad_length, '0', STR_PAD_LEFT);
        });
    }

    public function configure(string $prefix, int $padLength = 6): void
    {
        DB::connection('tenant')
            ->table('patient_number_sequences')
            ->updateOrInsert(
                [],
                [
                    'prefix'      => strtoupper($prefix),
                    'pad_length'  => max(4, min(10, $padLength)),
                    'updated_at'  => now(),
                ]
            );

        // Also persist to settings for display
        $settings = app(CentreSettingsService::class);
        $settings->set('patient_number.prefix', strtoupper($prefix));
        $settings->set('patient_number.pad_length', $padLength, 'integer');
    }
}
