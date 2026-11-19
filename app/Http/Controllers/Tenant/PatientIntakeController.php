<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PatientIntakeToken;
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
}
