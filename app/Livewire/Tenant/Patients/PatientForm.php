<?php

namespace App\Livewire\Tenant\Patients;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Doctor;
use App\Models\Tenant\InsuranceProvider;
use App\Models\Tenant\Patient;
use App\Services\AuditService;
use App\Services\PatientDuplicateDetector;
use App\Services\PatientNumberService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PatientForm extends Component
{
    public ?Patient $patient = null;

    // Personal
    public string $title = '';
    public string $firstName = '';
    public string $lastName = '';
    public string $middleName = '';
    public string $preferredName = '';
    public string $gender = '';
    public string $dateOfBirth = '';
    public string $nationalId = '';
    public string $passportNumber = '';
    public string $maritalStatus = '';
    public string $bloodType = '';

    // Contact
    public string $phonePrimary = '';
    public string $phoneSecondary = '';
    public string $email = '';
    public string $address = '';
    public string $city = '';
    public string $country = 'Trinidad and Tobago';
    public string $occupation = '';
    public string $employer = '';

    // Insurance
    public string $insuranceProviderId = '';
    public string $insurancePolicyNumber = '';
    public string $insuranceGroupNumber = '';

    // Clinical
    public string $primaryDoctorId = '';
    public string $branchId = '';
    public string $notes = '';

    // Duplicate detection state
    public bool $showDuplicateWarning = false;
    public array $duplicates = [];
    public bool $confirmedNoDuplicate = false;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->patient = Patient::findOrFail($id);
            $this->fill([
                'title'                  => $this->patient->title ?? '',
                'firstName'              => $this->patient->first_name,
                'lastName'               => $this->patient->last_name,
                'middleName'             => $this->patient->middle_name ?? '',
                'preferredName'          => $this->patient->preferred_name ?? '',
                'gender'                 => $this->patient->gender ?? '',
                'dateOfBirth'            => $this->patient->date_of_birth?->format('Y-m-d') ?? '',
                'nationalId'             => $this->patient->national_id ?? '',
                'passportNumber'         => $this->patient->passport_number ?? '',
                'maritalStatus'          => $this->patient->marital_status ?? '',
                'bloodType'              => $this->patient->blood_type ?? '',
                'phonePrimary'           => $this->patient->phone_primary ?? '',
                'phoneSecondary'         => $this->patient->phone_secondary ?? '',
                'email'                  => $this->patient->email ?? '',
                'address'                => $this->patient->address ?? '',
                'city'                   => $this->patient->city ?? '',
                'country'                => $this->patient->country ?? 'Trinidad and Tobago',
                'occupation'             => $this->patient->occupation ?? '',
                'employer'               => $this->patient->employer ?? '',
                'insuranceProviderId'    => (string) ($this->patient->insurance_provider_id ?? ''),
                'insurancePolicyNumber'  => $this->patient->insurance_policy_number ?? '',
                'insuranceGroupNumber'   => $this->patient->insurance_group_number ?? '',
                'primaryDoctorId'        => (string) ($this->patient->primary_doctor_id ?? ''),
                'branchId'               => (string) ($this->patient->branch_id ?? ''),
                'notes'                  => $this->patient->notes ?? '',
            ]);
        }
    }

    public function checkDuplicates(): void
    {
        $this->validate([
            'firstName' => 'required|string|max:80',
            'lastName'  => 'required|string|max:80',
        ]);

        if ($this->patient) {
            // Editing — skip duplicate check
            $this->save();
            return;
        }

        $found = app(PatientDuplicateDetector::class)->findPotentialDuplicates(
            $this->firstName,
            $this->lastName,
            $this->dateOfBirth ?: null,
            $this->phonePrimary ?: null
        );

        if ($found->isNotEmpty() && !$this->confirmedNoDuplicate) {
            $this->duplicates = $found->map(fn ($p) => [
                'id'             => $p->id,
                'patient_number' => $p->patient_number,
                'display_name'   => $p->display_name,
                'date_of_birth'  => $p->date_of_birth?->format('d M Y'),
                'phone_primary'  => $p->phone_primary,
            ])->toArray();

            $this->showDuplicateWarning = true;
            return;
        }

        $this->save();
    }

    public function confirmAndSave(): void
    {
        $this->confirmedNoDuplicate = true;
        $this->showDuplicateWarning = false;
        $this->save();
    }

    public function save(): void
    {
        $this->validate([
            'firstName'    => 'required|string|max:80',
            'lastName'     => 'required|string|max:80',
            'dateOfBirth'  => 'nullable|date|before:today',
            'email'        => 'nullable|email|max:150',
            'phonePrimary' => 'nullable|string|max:30',
            'branchId'     => 'nullable|exists:branches,id',
        ]);

        $data = [
            'title'                  => $this->title ?: null,
            'first_name'             => $this->firstName,
            'last_name'              => $this->lastName,
            'middle_name'            => $this->middleName ?: null,
            'preferred_name'         => $this->preferredName ?: null,
            'gender'                 => $this->gender ?: null,
            'date_of_birth'          => $this->dateOfBirth ?: null,
            'national_id'            => $this->nationalId ?: null,
            'passport_number'        => $this->passportNumber ?: null,
            'marital_status'         => $this->maritalStatus ?: null,
            'blood_type'             => $this->bloodType ?: null,
            'phone_primary'          => $this->phonePrimary ?: null,
            'phone_secondary'        => $this->phoneSecondary ?: null,
            'email'                  => $this->email ?: null,
            'address'                => $this->address ?: null,
            'city'                   => $this->city ?: null,
            'country'                => $this->country ?: 'Trinidad and Tobago',
            'occupation'             => $this->occupation ?: null,
            'employer'               => $this->employer ?: null,
            'insurance_provider_id'  => $this->insuranceProviderId ?: null,
            'insurance_policy_number'=> $this->insurancePolicyNumber ?: null,
            'insurance_group_number' => $this->insuranceGroupNumber ?: null,
            'primary_doctor_id'      => $this->primaryDoctorId ?: null,
            'branch_id'              => $this->branchId ?: null,
            'notes'                  => $this->notes ?: null,
        ];

        if ($this->patient) {
            $this->patient->update($data);
            $patientId = $this->patient->id;
        } else {
            $data['patient_number'] = app(PatientNumberService::class)->generate();
            $data['registered_by']  = auth()->guard('tenant')->id();
            $patient = Patient::create($data);
            $patientId = $patient->id;
        }

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            $this->patient ? 'patient.updated' : 'patient.registered',
            'Patient',
            $patientId
        );

        session()->flash('message', $this->patient ? 'Patient updated.' : 'Patient registered.');
        $this->redirect(route('tenant.patients.show', $patientId));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.patients.patient-form', [
            'branches'  => Branch::where('is_active', true)->orderBy('name')->get(),
            'doctors'   => Doctor::with('staffProfile')->get(),
            'insurers'  => InsuranceProvider::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
