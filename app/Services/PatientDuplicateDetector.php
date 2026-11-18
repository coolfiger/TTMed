<?php

namespace App\Services;

use App\Models\Tenant\Patient;
use Illuminate\Support\Collection;

class PatientDuplicateDetector
{
    public function findPotentialDuplicates(
        string $firstName,
        string $lastName,
        ?string $dateOfBirth = null,
        ?string $phone = null,
        ?int $excludeId = null
    ): Collection {
        $query = Patient::query()->withTrashed(false);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Exact DOB + last name match (high confidence)
        if ($dateOfBirth) {
            $exactDob = Patient::query()
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->where('last_name', 'like', $lastName)
                ->where('date_of_birth', $dateOfBirth)
                ->get();

            if ($exactDob->isNotEmpty()) {
                return $exactDob;
            }
        }

        // Phone match
        if ($phone) {
            $phoneMatch = Patient::query()
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->where(fn ($q) => $q
                    ->where('phone_primary', $phone)
                    ->orWhere('phone_secondary', $phone))
                ->get();

            if ($phoneMatch->isNotEmpty()) {
                return $phoneMatch;
            }
        }

        // Fuzzy name match (same last name, similar first name)
        $nameMatches = Patient::query()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('last_name', 'like', $lastName)
            ->where('first_name', 'like', substr($firstName, 0, 3) . '%')
            ->get();

        return $nameMatches;
    }
}
