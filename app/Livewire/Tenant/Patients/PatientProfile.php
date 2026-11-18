<?php

namespace App\Livewire\Tenant\Patients;

use App\Models\Tenant\Patient;
use App\Models\Tenant\PatientAllergy;
use App\Models\Tenant\PatientCondition;
use App\Models\Tenant\PatientConsent;
use App\Models\Tenant\PatientMedication;
use App\Models\Tenant\PatientPhysicalFile;
use App\Services\AuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PatientProfile extends Component
{
    public Patient $patient;
    public string $activeTab = 'personal';

    // Allergy form
    public bool $showAllergyForm = false;
    public string $allergyAllergen = '';
    public string $allergyReaction = '';
    public string $allergySeverity = '';

    // Condition form
    public bool $showConditionForm = false;
    public string $conditionName = '';
    public string $conditionIcd10 = '';
    public string $conditionDate = '';
    public string $conditionStatus = 'active';
    public string $conditionNotes = '';

    // Medication form
    public bool $showMedicationForm = false;
    public string $medicationName = '';
    public string $medicationDosage = '';
    public string $medicationFrequency = '';
    public string $medicationRoute = '';
    public string $medicationStartDate = '';
    public string $medicationPrescribedBy = '';

    // Medical history
    public string $surgicalHistory = '';
    public string $familyHistory = '';
    public string $socialHistory = '';
    public string $obstetricHistory = '';
    public string $immunisationHistory = '';
    public string $otherNotes = '';

    // Consent form
    public bool $showConsentForm = false;
    public string $consentType = '';
    public string $consentGivenBy = '';
    public string $consentRelationship = '';
    public string $consentDate = '';
    public string $consentNotes = '';

    // Physical file
    public bool $showMoveFileForm = false;
    public string $fileToLocation = '';
    public string $fileMoveReason = '';

    public function mount(int $id): void
    {
        $this->patient = Patient::findOrFail($id);
        $this->loadMedicalHistory();
    }

    private function loadMedicalHistory(): void
    {
        $history = $this->patient->medicalHistory;
        if ($history) {
            $this->surgicalHistory     = $history->surgical_history ?? '';
            $this->familyHistory       = $history->family_history ?? '';
            $this->socialHistory       = $history->social_history ?? '';
            $this->obstetricHistory    = $history->obstetric_history ?? '';
            $this->immunisationHistory = $history->immunisation_history ?? '';
            $this->otherNotes          = $history->other_notes ?? '';
        }
    }

    // --- Allergies ---

    public function saveAllergy(): void
    {
        $this->validate(['allergyAllergen' => 'required|string|max:150']);

        PatientAllergy::create([
            'patient_id'  => $this->patient->id,
            'allergen'    => $this->allergyAllergen,
            'reaction'    => $this->allergyReaction ?: null,
            'severity'    => $this->allergySeverity ?: null,
            'is_active'   => true,
            'recorded_by' => auth()->guard('tenant')->id(),
        ]);

        $this->reset(['allergyAllergen', 'allergyReaction', 'allergySeverity', 'showAllergyForm']);
        $this->patient->refresh();
    }

    public function removeAllergy(int $id): void
    {
        PatientAllergy::where('patient_id', $this->patient->id)->findOrFail($id)
            ->update(['is_active' => false]);
        $this->patient->refresh();
    }

    // --- Conditions ---

    public function saveCondition(): void
    {
        $this->validate(['conditionName' => 'required|string|max:200']);

        PatientCondition::create([
            'patient_id'     => $this->patient->id,
            'condition_name' => $this->conditionName,
            'icd10_code'     => $this->conditionIcd10 ?: null,
            'diagnosed_date' => $this->conditionDate ?: null,
            'status'         => $this->conditionStatus,
            'notes'          => $this->conditionNotes ?: null,
            'recorded_by'    => auth()->guard('tenant')->id(),
        ]);

        $this->reset(['conditionName', 'conditionIcd10', 'conditionDate', 'conditionStatus', 'conditionNotes', 'showConditionForm']);
        $this->patient->refresh();
    }

    // --- Medications ---

    public function saveMedication(): void
    {
        $this->validate(['medicationName' => 'required|string|max:150']);

        PatientMedication::create([
            'patient_id'      => $this->patient->id,
            'medication_name' => $this->medicationName,
            'dosage'          => $this->medicationDosage ?: null,
            'frequency'       => $this->medicationFrequency ?: null,
            'route'           => $this->medicationRoute ?: null,
            'start_date'      => $this->medicationStartDate ?: null,
            'is_current'      => true,
            'prescribed_by'   => $this->medicationPrescribedBy ?: null,
            'recorded_by'     => auth()->guard('tenant')->id(),
        ]);

        $this->reset(['medicationName', 'medicationDosage', 'medicationFrequency', 'medicationRoute', 'medicationStartDate', 'medicationPrescribedBy', 'showMedicationForm']);
        $this->patient->refresh();
    }

    public function discontinueMedication(int $id): void
    {
        PatientMedication::where('patient_id', $this->patient->id)->findOrFail($id)
            ->update(['is_current' => false, 'end_date' => now()->toDateString()]);
        $this->patient->refresh();
    }

    // --- Medical History ---

    public function saveMedicalHistory(): void
    {
        $this->patient->medicalHistory()->updateOrCreate(
            ['patient_id' => $this->patient->id],
            [
                'surgical_history'     => $this->surgicalHistory ?: null,
                'family_history'       => $this->familyHistory ?: null,
                'social_history'       => $this->socialHistory ?: null,
                'obstetric_history'    => $this->obstetricHistory ?: null,
                'immunisation_history' => $this->immunisationHistory ?: null,
                'other_notes'          => $this->otherNotes ?: null,
                'updated_by'           => auth()->guard('tenant')->id(),
            ]
        );

        session()->flash('message', 'Medical history saved.');
    }

    // --- Consents ---

    public function saveConsent(): void
    {
        $this->validate([
            'consentType' => 'required|string|max:100',
            'consentDate' => 'nullable|date',
        ]);

        PatientConsent::create([
            'patient_id'              => $this->patient->id,
            'consent_type'            => $this->consentType,
            'is_given'                => true,
            'given_by'                => $this->consentGivenBy ?: null,
            'relationship_to_patient' => $this->consentRelationship ?: null,
            'consent_date'            => $this->consentDate ?: now()->toDateString(),
            'notes'                   => $this->consentNotes ?: null,
            'recorded_by'             => auth()->guard('tenant')->id(),
        ]);

        $this->reset(['consentType', 'consentGivenBy', 'consentRelationship', 'consentDate', 'consentNotes', 'showConsentForm']);
        $this->patient->refresh();
    }

    // --- Physical file ---

    public function moveFile(): void
    {
        $this->validate([
            'fileToLocation' => 'required|string|max:200',
        ]);

        $file = $this->patient->physicalFile;
        $from = $file?->location_summary ?? 'Unknown';

        if ($file) {
            $file->movements()->create([
                'from_location' => $from,
                'to_location'   => $this->fileToLocation,
                'reason'        => $this->fileMoveReason ?: null,
                'moved_by'      => auth()->guard('tenant')->id(),
                'moved_at'      => now(),
            ]);
            $file->update(['current_location_note' => $this->fileToLocation]);
        } else {
            $file = PatientPhysicalFile::create([
                'patient_id'           => $this->patient->id,
                'current_location_note'=> $this->fileToLocation,
            ]);
        }

        $this->reset(['fileToLocation', 'fileMoveReason', 'showMoveFileForm']);
        $this->patient->refresh();
    }

    public function render(): \Illuminate\View\View
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
            'consents',
            'physicalFile.movements',
        ]);

        return view('livewire.tenant.patients.patient-profile');
    }
}
