<?php

namespace App\Livewire\Tenant\Patients;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Doctor;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\ImagingOrder;
use App\Models\Tenant\ImagingReport;
use App\Models\Tenant\InsuranceProvider;
use App\Models\Tenant\Patient;
use App\Models\Tenant\PatientAllergy;
use App\Models\Tenant\PatientCondition;
use App\Models\Tenant\PatientDocument;
use App\Models\Tenant\PatientIntakeToken;
use App\Models\Tenant\PatientMedication;
use App\Models\Tenant\QueueEntry;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\QueueService;
use App\Tenancy\TenantContext;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class PatientProfile extends Component
{
    use WithFileUploads;

    public Patient $patient;
    public string $activeTab = 'overview';

    // ── Inline edit fields ────────────────────────────────────────────
    public string $editFirstName    = '';
    public string $editLastName     = '';
    public string $editMiddleName   = '';
    public string $editPreferredName = '';
    public string $editTitle        = '';
    public string $editGender       = '';
    public string $editDob          = '';
    public string $editNationalId   = '';
    public string $editPassport     = '';
    public string $editMaritalStatus = '';
    public string $editBloodType    = '';
    public string $editPhone        = '';
    public string $editPhoneAlt     = '';
    public string $editEmail        = '';
    public string $editAddress      = '';
    public string $editCity         = '';
    public string $editCountry      = '';
    public string $editOccupation   = '';
    public string $editEmployer     = '';
    public string $editInsurerId    = '';
    public string $editPolicyNum    = '';
    public string $editGroupNum     = '';
    public string $editDoctorId     = '';
    public string $editNotes        = '';
    // Medical history
    public string $surgicalHistory    = '';
    public string $familyHistory      = '';
    public string $socialHistory      = '';
    public string $obstetricHistory   = '';
    public string $immunisationHistory = '';
    public string $otherNotes         = '';

    // ── Allergy form ──────────────────────────────────────────────────
    public bool   $showAllergyForm = false;
    public string $allergyAllergen = '';
    public string $allergyReaction = '';
    public string $allergySeverity = '';

    // ── Condition form ────────────────────────────────────────────────
    public bool   $showConditionForm  = false;
    public string $conditionName      = '';
    public string $conditionIcd10     = '';
    public string $conditionDate      = '';
    public string $conditionStatus    = 'active';
    public string $conditionNotes     = '';

    // ── Medication form ───────────────────────────────────────────────
    public bool   $showMedicationForm     = false;
    public string $medicationName         = '';
    public string $medicationDosage       = '';
    public string $medicationFrequency    = '';
    public string $medicationRoute        = '';
    public string $medicationStartDate    = '';
    public string $medicationPrescribedBy = '';

    // ── Imaging upload ────────────────────────────────────────────────
    public bool   $showImagingUpload  = false;
    public        $imagingFile        = null;
    public string $imagingModality    = 'DX';
    public string $imagingBodyPart    = '';
    public string $imagingStudyDate   = '';
    public string $imagingRadiologist = '';
    public string $imagingFindings    = '';
    public string $imagingImpression  = '';

    // ── Send to queue ────────────────────────────────────────────────
    public bool   $showQueueForm    = false;
    public int    $queueDoctorId    = 0;
    public string $queueVisitType   = 'walk_in';
    public string $queueComplaint   = '';
    public bool   $queuePriority    = false;
    public string $queueBranchId    = '';

    // ── QR intake ────────────────────────────────────────────────────
    public string $qrSvg      = '';
    public string $qrPin      = '';
    public string $qrUrl      = '';

    // ── UI state ──────────────────────────────────────────────────────
    public string $successMessage = '';
    public string $errorMessage   = '';

    public function mount(int $id): void
    {
        $this->patient = Patient::on('tenant')->findOrFail($id);
        $this->checkPermission('patients.view');
        $this->loadEditFields();
        $this->loadMedicalHistory();

        $user = Auth::guard('tenant')->user();
        $this->queueBranchId = (string) ($user?->branch_id ?? Branch::on('tenant')->where('is_main', true)->value('id') ?? '');
    }

    private function loadEditFields(): void
    {
        $p = $this->patient;
        $this->editFirstName     = $p->first_name;
        $this->editLastName      = $p->last_name;
        $this->editMiddleName    = $p->middle_name ?? '';
        $this->editPreferredName = $p->preferred_name ?? '';
        $this->editTitle         = $p->title ?? '';
        $this->editGender        = $p->gender ?? '';
        $this->editDob           = $p->date_of_birth?->format('Y-m-d') ?? '';
        $this->editNationalId    = $p->national_id ?? '';
        $this->editPassport      = $p->passport_number ?? '';
        $this->editMaritalStatus = $p->marital_status ?? '';
        $this->editBloodType     = $p->blood_type ?? '';
        $this->editPhone         = $p->phone_primary ?? '';
        $this->editPhoneAlt      = $p->phone_secondary ?? '';
        $this->editEmail         = $p->email ?? '';
        $this->editAddress       = $p->address ?? '';
        $this->editCity          = $p->city ?? '';
        $this->editCountry       = $p->country ?? 'Trinidad and Tobago';
        $this->editOccupation    = $p->occupation ?? '';
        $this->editEmployer      = $p->employer ?? '';
        $this->editInsurerId     = (string) ($p->insurance_provider_id ?? '');
        $this->editPolicyNum     = $p->insurance_policy_number ?? '';
        $this->editGroupNum      = $p->insurance_group_number ?? '';
        $this->editDoctorId      = (string) ($p->primary_doctor_id ?? '');
        $this->editNotes         = $p->notes ?? '';
    }

    private function loadMedicalHistory(): void
    {
        $h = $this->patient->medicalHistory;
        if ($h) {
            $this->surgicalHistory     = $h->surgical_history ?? '';
            $this->familyHistory       = $h->family_history ?? '';
            $this->socialHistory       = $h->social_history ?? '';
            $this->obstetricHistory    = $h->obstetric_history ?? '';
            $this->immunisationHistory = $h->immunisation_history ?? '';
            $this->otherNotes          = $h->other_notes ?? '';
        }
    }

    // ── Save demographics ─────────────────────────────────────────────

    public function saveDemographics(): void
    {
        $this->checkPermission('patients.edit');
        $this->validate([
            'editFirstName'  => 'required|string|max:80',
            'editLastName'   => 'required|string|max:80',
            'editDob'        => 'nullable|date|before:today',
            'editEmail'      => 'nullable|email|max:150',
        ]);

        $this->patient->update([
            'title'            => $this->editTitle ?: null,
            'first_name'       => $this->editFirstName,
            'last_name'        => $this->editLastName,
            'middle_name'      => $this->editMiddleName ?: null,
            'preferred_name'   => $this->editPreferredName ?: null,
            'gender'           => $this->editGender ?: null,
            'date_of_birth'    => $this->editDob ?: null,
            'national_id'      => $this->editNationalId ?: null,
            'passport_number'  => $this->editPassport ?: null,
            'marital_status'   => $this->editMaritalStatus ?: null,
            'blood_type'       => $this->editBloodType ?: null,
        ]);
        $this->patient->refresh();
        $this->successMessage = 'Demographics saved.';
    }

    public function saveContact(): void
    {
        $this->checkPermission('patients.edit');
        $this->validate(['editEmail' => 'nullable|email|max:150']);

        $this->patient->update([
            'phone_primary'   => $this->editPhone ?: null,
            'phone_secondary' => $this->editPhoneAlt ?: null,
            'email'           => $this->editEmail ?: null,
            'address'         => $this->editAddress ?: null,
            'city'            => $this->editCity ?: null,
            'country'         => $this->editCountry ?: 'Trinidad and Tobago',
            'occupation'      => $this->editOccupation ?: null,
            'employer'        => $this->editEmployer ?: null,
        ]);
        $this->patient->refresh();
        $this->successMessage = 'Contact info saved.';
    }

    public function saveInsurance(): void
    {
        $this->checkPermission('patients.edit');
        $this->patient->update([
            'insurance_provider_id'   => $this->editInsurerId ?: null,
            'insurance_policy_number' => $this->editPolicyNum ?: null,
            'insurance_group_number'  => $this->editGroupNum ?: null,
        ]);
        $this->patient->refresh();
        $this->successMessage = 'Insurance saved.';
    }

    public function saveClinical(): void
    {
        $this->checkPermission('patients.edit');
        $this->patient->update([
            'primary_doctor_id' => $this->editDoctorId ?: null,
            'notes'             => $this->editNotes ?: null,
        ]);
        $this->patient->refresh();
        $this->successMessage = 'Clinical info saved.';
    }

    // ── Medical history ───────────────────────────────────────────────

    public function saveMedicalHistory(): void
    {
        $this->checkPermission('patients.edit');
        $this->patient->medicalHistory()->updateOrCreate(
            ['patient_id' => $this->patient->id],
            [
                'surgical_history'     => $this->surgicalHistory ?: null,
                'family_history'       => $this->familyHistory ?: null,
                'social_history'       => $this->socialHistory ?: null,
                'obstetric_history'    => $this->obstetricHistory ?: null,
                'immunisation_history' => $this->immunisationHistory ?: null,
                'other_notes'          => $this->otherNotes ?: null,
                'updated_by'           => Auth::guard('tenant')->id(),
            ]
        );
        $this->successMessage = 'Medical history saved.';
    }

    // ── Allergies ─────────────────────────────────────────────────────

    public function saveAllergy(): void
    {
        $this->checkPermission('patients.edit');
        $this->validate(['allergyAllergen' => 'required|string|max:150']);

        PatientAllergy::on('tenant')->create([
            'patient_id'  => $this->patient->id,
            'allergen'    => $this->allergyAllergen,
            'reaction'    => $this->allergyReaction ?: null,
            'severity'    => $this->allergySeverity ?: null,
            'is_active'   => true,
            'recorded_by' => Auth::guard('tenant')->id(),
        ]);
        $this->reset(['allergyAllergen', 'allergyReaction', 'allergySeverity', 'showAllergyForm']);
        $this->patient->refresh();
        $this->successMessage = 'Allergy recorded.';
    }

    public function removeAllergy(int $id): void
    {
        $this->checkPermission('patients.edit');
        PatientAllergy::on('tenant')->where('patient_id', $this->patient->id)->findOrFail($id)
            ->update(['is_active' => false]);
        $this->patient->refresh();
        $this->successMessage = 'Allergy removed.';
    }

    // ── Conditions ───────────────────────────────────────────────────

    public function saveCondition(): void
    {
        $this->checkPermission('patients.edit');
        $this->validate(['conditionName' => 'required|string|max:200']);

        PatientCondition::on('tenant')->create([
            'patient_id'     => $this->patient->id,
            'condition_name' => $this->conditionName,
            'icd10_code'     => $this->conditionIcd10 ?: null,
            'diagnosed_date' => $this->conditionDate ?: null,
            'status'         => $this->conditionStatus,
            'notes'          => $this->conditionNotes ?: null,
            'recorded_by'    => Auth::guard('tenant')->id(),
        ]);
        $this->reset(['conditionName', 'conditionIcd10', 'conditionDate', 'conditionStatus', 'conditionNotes', 'showConditionForm']);
        $this->patient->refresh();
        $this->successMessage = 'Condition recorded.';
    }

    // ── Medications ───────────────────────────────────────────────────

    public function saveMedication(): void
    {
        $this->checkPermission('patients.edit');
        $this->validate(['medicationName' => 'required|string|max:150']);

        PatientMedication::on('tenant')->create([
            'patient_id'      => $this->patient->id,
            'medication_name' => $this->medicationName,
            'dosage'          => $this->medicationDosage ?: null,
            'frequency'       => $this->medicationFrequency ?: null,
            'route'           => $this->medicationRoute ?: null,
            'start_date'      => $this->medicationStartDate ?: null,
            'is_current'      => true,
            'prescribed_by'   => $this->medicationPrescribedBy ?: null,
            'recorded_by'     => Auth::guard('tenant')->id(),
        ]);
        $this->reset(['medicationName', 'medicationDosage', 'medicationFrequency', 'medicationRoute', 'medicationStartDate', 'medicationPrescribedBy', 'showMedicationForm']);
        $this->patient->refresh();
        $this->successMessage = 'Medication recorded.';
    }

    public function discontinueMedication(int $id): void
    {
        $this->checkPermission('patients.edit');
        PatientMedication::on('tenant')->where('patient_id', $this->patient->id)->findOrFail($id)
            ->update(['is_current' => false, 'end_date' => now()->toDateString()]);
        $this->patient->refresh();
        $this->successMessage = 'Medication discontinued.';
    }

    // ── Imaging upload ────────────────────────────────────────────────

    public function uploadImaging(): void
    {
        $this->checkPermission('patients.edit');
        $this->validate([
            'imagingFile'      => 'required|file|mimes:pdf,jpg,jpeg,png,tif,tiff|max:30720',
            'imagingModality'  => 'required|string',
            'imagingBodyPart'  => 'required|string|max:150',
            'imagingStudyDate' => 'nullable|date',
        ]);

        $tenantContext = app(TenantContext::class);
        $dir = $tenantContext->storagePath('imaging');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = uniqid('img_', true) . '.' . $this->imagingFile->getClientOriginalExtension();
        $path = $dir . '/' . $filename;
        $this->imagingFile->move($dir, $filename);

        $orderNum = 'IMG-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $order = ImagingOrder::on('tenant')->create([
            'order_number'       => $orderNum,
            'patient_id'         => $this->patient->id,
            'encounter_id'       => null,
            'ordered_by'         => Auth::guard('tenant')->id(),
            'branch_id'          => $this->queueBranchId ?: null,
            'modality'           => $this->imagingModality,
            'body_part'          => $this->imagingBodyPart,
            'clinical_indication'=> 'Patient-provided',
            'status'             => 'report_available',
            'ordered_at'         => now(),
            'performed_at'       => $this->imagingStudyDate ? \Carbon\Carbon::parse($this->imagingStudyDate) : now(),
        ]);

        ImagingReport::on('tenant')->create([
            'imaging_order_id' => $order->id,
            'patient_id'       => $this->patient->id,
            'radiologist_name' => $this->imagingRadiologist ?: null,
            'report_status'    => 'final',
            'findings'         => $this->imagingFindings ?: null,
            'impression'       => $this->imagingImpression ?: null,
            'report_pdf_path'  => $path,
            'source'           => 'patient_provided',
            'report_datetime'  => now(),
        ]);

        app(AuditService::class)->logTenant(
            action:       'imaging.uploaded',
            resourceType: 'imaging_order',
            resourceId:   $order->id,
            newValues:    ['patient_id' => $this->patient->id],
            description:  "Imaging ({$this->imagingModality} - {$this->imagingBodyPart}) uploaded for patient #{$this->patient->patient_number}",
        );

        $this->reset(['imagingFile', 'imagingBodyPart', 'imagingStudyDate', 'imagingRadiologist', 'imagingFindings', 'imagingImpression', 'showImagingUpload']);
        $this->successMessage = 'Imaging file uploaded successfully.';
    }

    // ── Send to queue ────────────────────────────────────────────────

    public function sendToQueue(): void
    {
        $this->checkPermission('queue.checkin');
        $this->validate([
            'queueVisitType' => 'required|in:walk_in,appointment,emergency,follow_up',
            'queueBranchId'  => 'required|exists:tenant.branches,id',
        ]);

        $entry = app(QueueService::class)->checkIn(
            patient:        $this->patient,
            branchId:       (int) $this->queueBranchId,
            userId:         Auth::guard('tenant')->id(),
            doctorId:       $this->queueDoctorId ?: null,
            visitType:      $this->queueVisitType,
            chiefComplaint: $this->queueComplaint ?: null,
            isPriority:     $this->queuePriority,
            priorityReason: null,
        );

        app(AuditService::class)->logTenant(
            'queue.checkin',
            'queue_entry',
            $entry->id,
            null,
            null,
            "Patient {$this->patient->display_name} checked in — Queue #{$entry->queue_number}",
        );

        $this->reset(['queueDoctorId', 'queueVisitType', 'queueComplaint', 'queuePriority', 'showQueueForm']);
        $this->successMessage = "Checked in as Queue #{$entry->queue_number}.";
    }

    // ── QR intake token ───────────────────────────────────────────────

    public function generateIntakeQr(): void
    {
        $this->checkPermission('patients.edit');

        // Expire any old tokens for this patient
        PatientIntakeToken::on('tenant')
            ->where('patient_id', $this->patient->id)
            ->whereNull('used_at')
            ->delete();

        $token = Str::random(48);
        $pin   = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        PatientIntakeToken::on('tenant')->create([
            'patient_id'  => $this->patient->id,
            'token'       => $token,
            'pin'         => $pin,
            'expires_at'  => now()->addHours(24),
            'created_by'  => Auth::guard('tenant')->id(),
        ]);

        $tenantCode = app(TenantContext::class)->get()?->code ?? '';
        $url = url("/intake/{$tenantCode}/{$token}");

        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );
        $writer  = new Writer($renderer);
        $this->qrSvg = $writer->writeString($url);
        $this->qrPin = $pin;
        $this->qrUrl = $url;
    }

    // ── Render ────────────────────────────────────────────────────────

    public function render()
    {
        $this->patient->load([
            'branch',
            'insuranceProvider',
            'primaryDoctor.staffProfile',
            'contacts',
            'allergies',
            'conditions',
            'medications',
            'medicalHistory',
        ]);

        $encounters = Encounter::on('tenant')
            ->where('patient_id', $this->patient->id)
            ->with([
                'doctor.staffProfile',
                'branch',
                'consultationNote',
                'diagnoses',
                'vitalSigns',
                'prescriptions.items',
                'referrals',
            ])
            ->orderByDesc('encounter_date')
            ->limit(50)
            ->get();

        $imagingOrders = ImagingOrder::on('tenant')
            ->where('patient_id', $this->patient->id)
            ->with('report')
            ->orderByDesc('ordered_at')
            ->get();

        $documents = PatientDocument::on('tenant')
            ->where('patient_id', $this->patient->id)
            ->where('is_archived', false)
            ->with(['uploadedBy', 'category'])
            ->orderByDesc('created_at')
            ->get();

        $branches  = Branch::on('tenant')->where('is_active', true)->get(['id', 'name']);
        $doctors   = Doctor::on('tenant')->with('staffProfile')->get();
        $insurers  = InsuranceProvider::on('tenant')->where('is_active', true)->orderBy('name')->get();

        return view('livewire.tenant.patients.patient-profile', compact(
            'encounters', 'imagingOrders', 'documents', 'branches', 'doctors', 'insurers'
        ))->layout('layouts.app-workspace');
    }

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }
}
