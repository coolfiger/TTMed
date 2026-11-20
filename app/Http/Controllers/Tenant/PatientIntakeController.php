<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Patient;
use App\Models\Tenant\PatientIntakeToken;
use App\Services\PatientNumberService;
use Illuminate\Http\Request;

class PatientIntakeController extends Controller
{
    public function show(Request $request, string $token)
    {
        $record = PatientIntakeToken::on('tenant')
            ->where('token', $token)
            ->with('patient')
            ->first();

        if (!$record || !$record->isValid()) {
            return view('patient-intake-expired');
        }

        return view('patient-intake', ['record' => $record, 'token' => $token]);
    }

    public function submit(Request $request, string $token)
    {
        $record = PatientIntakeToken::on('tenant')
            ->where('token', $token)
            ->with('patient')
            ->first();

        if (!$record || !$record->isValid()) {
            abort(410, 'This intake link has expired or already been used.');
        }

        $data = $request->validate([
            'pin'              => 'required|string|size:4',
            'first_name'       => 'required|string|max:80',
            'last_name'        => 'required|string|max:80',
            'date_of_birth'    => 'nullable|date|before:today',
            'gender'           => 'nullable|in:male,female,other',
            'phone_primary'    => 'nullable|string|max:30',
            'email'            => 'nullable|email|max:150',
            'address'          => 'nullable|string|max:500',
            'city'             => 'nullable|string|max:100',
            'occupation'       => 'nullable|string|max:100',
            'emergency_name'   => 'nullable|string|max:100',
            'emergency_phone'  => 'nullable|string|max:30',
            'emergency_relation' => 'nullable|string|max:50',
            'known_allergies'  => 'nullable|string|max:1000',
            'current_medications' => 'nullable|string|max:1000',
        ]);

        if ($data['pin'] !== $record->pin) {
            return back()->withErrors(['pin' => 'Incorrect PIN. Please try again.'])->withInput();
        }

        $record->patient->update([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'date_of_birth' => $data['date_of_birth'] ?? $record->patient->date_of_birth,
            'gender'        => $data['gender'] ?? $record->patient->gender,
            'phone_primary' => $data['phone_primary'] ?? $record->patient->phone_primary,
            'email'         => $data['email'] ?? $record->patient->email,
            'address'       => $data['address'] ?? $record->patient->address,
            'city'          => $data['city'] ?? $record->patient->city,
            'occupation'    => $data['occupation'] ?? $record->patient->occupation,
        ]);

        if (!empty($data['emergency_name'])) {
            $record->patient->contacts()->updateOrCreate(
                ['contact_type' => 'emergency'],
                [
                    'contact_name'     => $data['emergency_name'],
                    'contact_phone'    => $data['emergency_phone'] ?? null,
                    'relationship'     => $data['emergency_relation'] ?? null,
                    'is_primary'       => true,
                ]
            );
        }

        $record->update(['used_at' => now()]);

        return view('patient-intake-done');
    }

    // -------------------------------------------------------------------------
    // Registration flow — new patient, no existing patient_id
    // -------------------------------------------------------------------------

    public function showRegistration(Request $request, string $token)
    {
        $record = PatientIntakeToken::on('tenant')
            ->where('token', $token)
            ->where('type', 'registration')
            ->first();

        if (!$record || !$record->isValidRegistration()) {
            return view('patient-registration-expired');
        }

        return view('patient-registration', ['token' => $token]);
    }

    public function submitRegistration(Request $request, string $token)
    {
        $record = PatientIntakeToken::on('tenant')
            ->where('token', $token)
            ->where('type', 'registration')
            ->lockForUpdate()
            ->first();

        if (!$record || !$record->isValidRegistration()) {
            abort(410, 'This registration link has expired or already been used.');
        }

        $data = $request->validate([
            'first_name'       => 'required|string|max:80',
            'last_name'        => 'required|string|max:80',
            'date_of_birth'    => 'nullable|date|before:today',
            'gender'           => 'nullable|in:male,female,other',
            'phone_primary'    => 'nullable|string|max:30',
            'email'            => 'nullable|email|max:150',
            'address'          => 'nullable|string|max:500',
            'city'             => 'nullable|string|max:100',
            'occupation'       => 'nullable|string|max:100',
            'national_id'      => 'nullable|string|max:30',
            'emergency_name'   => 'nullable|string|max:100',
            'emergency_phone'  => 'nullable|string|max:30',
            'emergency_relation' => 'nullable|string|max:50',
        ]);

        $patient = Patient::on('tenant')->create([
            'patient_number'  => app(PatientNumberService::class)->generate(),
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'gender'          => $data['gender'] ?? null,
            'phone_primary'   => $data['phone_primary'] ?? null,
            'email'           => $data['email'] ?? null,
            'address'         => $data['address'] ?? null,
            'city'            => $data['city'] ?? null,
            'occupation'      => $data['occupation'] ?? null,
            'national_id'     => $data['national_id'] ?? null,
            'registered_by'   => $record->created_by,
        ]);

        if (!empty($data['emergency_name'])) {
            $patient->contacts()->create([
                'contact_type'  => 'emergency',
                'contact_name'  => $data['emergency_name'],
                'contact_phone' => $data['emergency_phone'] ?? null,
                'relationship'  => $data['emergency_relation'] ?? null,
                'is_primary'    => true,
            ]);
        }

        // Link token to new patient — receptionist polls for this
        $record->update(['patient_id' => $patient->id, 'used_at' => now()]);

        return view('patient-registration-done');
    }
}
