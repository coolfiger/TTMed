<?php

namespace App\Livewire\Tenant\Staff;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Doctor;
use App\Models\Tenant\DoctorOperatingHour;
use App\Models\Tenant\Role;
use App\Models\Tenant\StaffProfile;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class StaffForm extends Component
{
    use WithFileUploads;

    public ?StaffProfile $staffProfile = null;

    // User account
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public array $selectedRoles = [];
    public bool $userActive = true;

    // Staff profile
    public string $title = '';
    public string $firstName = '';
    public string $lastName = '';
    public string $gender = '';
    public string $dateOfBirth = '';
    public string $phone = '';
    public string $mobile = '';
    public string $address = '';
    public string $emergencyContactName = '';
    public string $emergencyContactPhone = '';
    public ?int $branchId = null;
    public bool $staffActive = true;

    // Doctor profile
    public bool $isDoctor = false;
    public string $registrationNumber = '';
    public string $specialty = '';
    public string $qualifications = '';
    public string $prescriptionHeader = '';
    public string $prescriptionFooter = '';
    public bool $availableForAppointments = true;

    // Doctor operating hours
    public array $doctorHours = [];

    // File uploads
    public $signatureUpload = null;
    public $stampUpload = null;
    public $prescriptionLogoUpload = null;

    public function mount(?int $id = null): void
    {
        $this->initDoctorHours();

        if ($id) {
            $this->staffProfile = StaffProfile::with(['user.roles', 'doctor.operatingHours'])->findOrFail($id);
            $user = $this->staffProfile->user;
            $doctor = $this->staffProfile->doctor;

            $this->fill([
                'name'          => $user->name,
                'username'      => $user->username,
                'email'         => $user->email ?? '',
                'selectedRoles' => $user->roles->pluck('id')->toArray(),
                'userActive'    => $user->is_active,
                'title'         => $this->staffProfile->title ?? '',
                'firstName'     => $this->staffProfile->first_name,
                'lastName'      => $this->staffProfile->last_name,
                'gender'        => $this->staffProfile->gender ?? '',
                'dateOfBirth'   => $this->staffProfile->date_of_birth?->format('Y-m-d') ?? '',
                'phone'         => $this->staffProfile->phone ?? '',
                'mobile'        => $this->staffProfile->mobile ?? '',
                'address'       => $this->staffProfile->address ?? '',
                'emergencyContactName'  => $this->staffProfile->emergency_contact_name ?? '',
                'emergencyContactPhone' => $this->staffProfile->emergency_contact_phone ?? '',
                'branchId'      => $this->staffProfile->branch_id,
                'staffActive'   => $this->staffProfile->is_active,
                'isDoctor'      => $doctor !== null,
            ]);

            if ($doctor) {
                $this->fill([
                    'registrationNumber'      => $doctor->registration_number ?? '',
                    'specialty'               => $doctor->specialty ?? '',
                    'qualifications'          => $doctor->qualifications ?? '',
                    'prescriptionHeader'      => $doctor->prescription_header ?? '',
                    'prescriptionFooter'      => $doctor->prescription_footer ?? '',
                    'availableForAppointments' => $doctor->available_for_appointments,
                ]);

                foreach ($doctor->operatingHours as $hour) {
                    $this->doctorHours[$hour->day_of_week] = [
                        'start_time' => $hour->start_time,
                        'end_time'   => $hour->end_time,
                        'is_active'  => $hour->is_active,
                    ];
                }
            }
        }
    }

    public function save(): void
    {
        $rules = [
            'firstName'    => 'required|string|max:100',
            'lastName'     => 'required|string|max:100',
            'name'         => 'required|string|max:150',
            'username'     => 'required|string|max:50|alpha_dash',
            'email'        => 'nullable|email|max:150',
            'selectedRoles' => 'required|array|min:1',
        ];

        if (!$this->staffProfile) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        if ($this->signatureUpload) {
            $rules['signatureUpload'] = 'image|max:2048';
        }

        $this->validate($rules);

        // 1. Create/update user account
        $userData = [
            'name'      => $this->name,
            'username'  => $this->username,
            'email'     => $this->email ?: null,
            'is_active' => $this->userActive,
        ];

        if ($this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        if ($this->staffProfile) {
            $user = $this->staffProfile->user;
            $user->update($userData);
        } else {
            $user = User::create($userData);
        }

        $user->roles()->sync($this->selectedRoles);

        // 2. Create/update staff profile
        $profileData = [
            'user_id'                 => $user->id,
            'branch_id'               => $this->branchId,
            'title'                   => $this->title ?: null,
            'first_name'              => $this->firstName,
            'last_name'               => $this->lastName,
            'gender'                  => $this->gender ?: null,
            'date_of_birth'           => $this->dateOfBirth ?: null,
            'phone'                   => $this->phone ?: null,
            'mobile'                  => $this->mobile ?: null,
            'address'                 => $this->address ?: null,
            'emergency_contact_name'  => $this->emergencyContactName ?: null,
            'emergency_contact_phone' => $this->emergencyContactPhone ?: null,
            'is_active'               => $this->staffActive,
        ];

        if ($this->staffProfile) {
            $this->staffProfile->update($profileData);
            $profile = $this->staffProfile;
        } else {
            $profile = StaffProfile::create($profileData);
        }

        // 3. Doctor profile
        if ($this->isDoctor) {
            $doctorData = [
                'staff_profile_id'         => $profile->id,
                'registration_number'      => $this->registrationNumber ?: null,
                'specialty'                => $this->specialty ?: null,
                'qualifications'           => $this->qualifications ?: null,
                'prescription_header'      => $this->prescriptionHeader ?: null,
                'prescription_footer'      => $this->prescriptionFooter ?: null,
                'available_for_appointments' => $this->availableForAppointments,
            ];

            $doctor = Doctor::updateOrCreate(['staff_profile_id' => $profile->id], $doctorData);

            $this->handleFileUpload($doctor);
            $this->saveDoctorHours($doctor->id);
        }

        app(AuditService::class)->logTenant(
            auth()->guard('tenant')->id(),
            $this->staffProfile ? 'staff.updated' : 'staff.created',
            'StaffProfile',
            $profile->id
        );

        session()->flash('message', 'Staff member saved.');
        $this->redirect(route('tenant.staff.index'));
    }

    private function handleFileUpload(Doctor $doctor): void
    {
        $storagePath = app(\App\Tenancy\TenantContext::class)->storagePath('images');

        if ($this->signatureUpload) {
            $path = $this->signatureUpload->store('', ['disk' => 'tenant_private', 'path' => $storagePath]);
            $doctor->update(['signature_path' => $path]);
        }

        if ($this->stampUpload) {
            $path = $this->stampUpload->store('', ['disk' => 'tenant_private', 'path' => $storagePath]);
            $doctor->update(['stamp_path' => $path]);
        }

        if ($this->prescriptionLogoUpload) {
            $path = $this->prescriptionLogoUpload->store('', ['disk' => 'tenant_private', 'path' => $storagePath]);
            $doctor->update(['prescription_logo_path' => $path]);
        }
    }

    private function saveDoctorHours(int $doctorId): void
    {
        foreach ($this->doctorHours as $day => $hour) {
            DoctorOperatingHour::updateOrCreate(
                ['doctor_id' => $doctorId, 'day_of_week' => $day],
                [
                    'start_time' => $hour['is_active'] ? ($hour['start_time'] ?: '08:00') : '08:00',
                    'end_time'   => $hour['is_active'] ? ($hour['end_time'] ?: '17:00') : '17:00',
                    'is_active'  => $hour['is_active'],
                ]
            );
        }
    }

    private function initDoctorHours(): void
    {
        for ($i = 0; $i <= 6; $i++) {
            $this->doctorHours[$i] = [
                'start_time' => '08:00',
                'end_time'   => '17:00',
                'is_active'  => $i >= 1 && $i <= 5,
            ];
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tenant.staff.staff-form', [
            'roles'    => Role::orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'dayNames' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ]);
    }
}
