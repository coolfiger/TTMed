<?php

namespace App\Livewire\Tenant\Nursing;

use App\Models\Tenant\Encounter;
use App\Models\Tenant\NursingAssessment;
use App\Models\Tenant\NursingProcedure;
use App\Models\Tenant\PatientAllergy;
use App\Models\Tenant\PatientMedication;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\VitalSign;
use App\Services\NursingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NurseDashboard extends Component
{
    // -------------------------------------------------------------------------
    // Queue display
    // -------------------------------------------------------------------------
    public int    $branchId     = 0;
    public array  $queue        = [];

    // -------------------------------------------------------------------------
    // Active patient
    // -------------------------------------------------------------------------
    public ?int   $activeEntryId     = null;
    public ?int   $activeEncounterId = null;
    public ?int   $activePatientId   = null;
    public string $activePatientName = '';

    // -------------------------------------------------------------------------
    // Vitals form
    // -------------------------------------------------------------------------
    public string  $systolic       = '';
    public string  $diastolic      = '';
    public string  $pulse          = '';
    public string  $respiratoryRate = '';
    public string  $oxygenSat      = '';
    public string  $temperature    = '';
    public string  $tempSite       = 'oral';
    public string  $weightKg       = '';
    public string  $heightCm       = '';
    public string  $bmi            = '';  // computed live
    public string  $bloodGlucose   = '';
    public string  $glucoseTiming  = 'fasting';
    public string  $painScore      = '';
    public string  $vitalsNotes    = '';

    // -------------------------------------------------------------------------
    // Assessment form
    // -------------------------------------------------------------------------
    public bool   $allergiesReviewed   = false;
    public bool   $medicationsReviewed = false;
    public string $chiefComplaint      = '';
    public string $assessmentNotes     = '';

    // Patient allergies (loaded on patient select)
    public array  $patientAllergies    = [];
    public bool   $showAddAllergy      = false;
    public string $newAllergen         = '';
    public string $newAllergyReaction  = '';
    public string $newAllergySeverity  = 'mild';

    // Patient medications (loaded on patient select)
    public array  $patientMedications  = [];
    public bool   $showAddMedication   = false;
    public string $newMedName          = '';
    public string $newMedDosage        = '';
    public string $newMedFrequency     = '';

    // -------------------------------------------------------------------------
    // Procedure form
    // -------------------------------------------------------------------------
    public bool   $showProcedureForm  = false;
    public string $procedureType      = '';
    public string $procedureName      = '';
    public string $procedureDetails   = '';
    public string $procedureOutcome   = '';
    public string $procedureSite      = '';

    // -------------------------------------------------------------------------
    // UI state
    // -------------------------------------------------------------------------
    public string $activeTab       = 'vitals';
    public string $successMessage  = '';
    public string $errorMessage    = '';

    protected NursingService $nursingService;

    public function boot(NursingService $nursingService): void
    {
        $this->nursingService = $nursingService;
    }

    public function mount(): void
    {
        $this->checkPermission('nursing.assess');
        $user           = Auth::guard('tenant')->user();
        $this->branchId = $user?->branch_id ?? 0;
        $this->loadQueue();
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    public function updatedWeightKg(): void
    {
        $this->recalcBmi();
    }

    public function updatedHeightCm(): void
    {
        $this->recalcBmi();
    }

    private function recalcBmi(): void
    {
        $w = (float) $this->weightKg;
        $h = (float) $this->heightCm;

        if ($w > 0 && $h > 0) {
            $hm        = $h / 100;
            $this->bmi = number_format($w / ($hm * $hm), 1);
        } else {
            $this->bmi = '';
        }
    }

    // -------------------------------------------------------------------------
    // Queue
    // -------------------------------------------------------------------------

    public function loadQueue(): void
    {
        $this->queue = QueueEntry::on('tenant')
            ->with('patient')
            ->forBranchToday($this->branchId)
            ->whereIn('status', ['waiting', 'with_nurse'])
            ->orderBy('queue_number')
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'queue_number' => $e->queue_number,
                'patient_name' => $e->patient?->full_name ?? '—',
                'status'       => $e->status,
                'status_label' => $e->status_label,
                'is_priority'  => $e->is_priority,
            ])
            ->toArray();
    }

    public function selectPatient(int $entryId): void
    {
        $this->checkPermission('nursing.assess');

        $entry = QueueEntry::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($entryId);

        $this->activeEntryId     = $entry->id;
        $this->activePatientId   = $entry->patient_id;
        $this->activePatientName = $entry->patient?->full_name ?? '—';
        $this->activeEncounterId = $entry->encounter_id;

        $this->resetForms();
        $this->activeTab = 'vitals';

        // Pre-load existing assessment if present
        if ($this->activeEncounterId) {
            $assessment = NursingAssessment::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->first();

            if ($assessment) {
                $this->allergiesReviewed   = $assessment->allergies_reviewed;
                $this->medicationsReviewed = $assessment->medications_reviewed;
                $this->assessmentNotes     = $assessment->nursing_notes ?? '';
            }
        }

        $this->loadPatientAllergiesAndMeds();
    }

    private function loadPatientAllergiesAndMeds(): void
    {
        if (!$this->activePatientId) return;

        $this->patientAllergies = PatientAllergy::on('tenant')
            ->where('patient_id', $this->activePatientId)
            ->where('is_active', true)
            ->orderBy('severity', 'desc')
            ->get(['id', 'allergen', 'reaction', 'severity'])
            ->map(fn ($a) => [
                'id'       => $a->id,
                'allergen' => $a->allergen,
                'reaction' => $a->reaction ?? '',
                'severity' => $a->severity_label,
            ])
            ->toArray();

        $this->patientMedications = PatientMedication::on('tenant')
            ->where('patient_id', $this->activePatientId)
            ->where('is_current', true)
            ->orderBy('medication_name')
            ->get(['id', 'medication_name', 'dosage', 'frequency'])
            ->map(fn ($m) => [
                'id'        => $m->id,
                'name'      => $m->medication_name,
                'dosage'    => $m->dosage ?? '',
                'frequency' => $m->frequency ?? '',
            ])
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Vitals
    // -------------------------------------------------------------------------

    public function saveVitals(): void
    {
        $this->checkPermission('nursing.assess');

        $this->validate([
            'systolic'       => 'nullable|integer|min:50|max:300',
            'diastolic'      => 'nullable|integer|min:20|max:200',
            'pulse'          => 'nullable|integer|min:20|max:300',
            'respiratoryRate' => 'nullable|integer|min:1|max:60',
            'oxygenSat'      => 'nullable|numeric|min:50|max:100',
            'temperature'    => 'nullable|numeric|min:30|max:45',
            'weightKg'       => 'nullable|numeric|min:0.5|max:500',
            'heightCm'       => 'nullable|numeric|min:20|max:250',
            'bloodGlucose'   => 'nullable|numeric|min:0.5|max:50',
            'painScore'      => 'nullable|integer|min:0|max:10',
        ]);

        if (!$this->activePatientId) {
            $this->errorMessage = 'No patient selected.';
            return;
        }

        $data = [
            'patient_id'       => $this->activePatientId,
            'encounter_id'     => $this->activeEncounterId,
            'systolic_bp'      => $this->systolic ?: null,
            'diastolic_bp'     => $this->diastolic ?: null,
            'pulse_rate'           => $this->pulse ?: null,
            'respiratory_rate'     => $this->respiratoryRate ?: null,
            'oxygen_saturation'    => $this->oxygenSat ?: null,
            'temperature'          => $this->temperature ?: null,
            'temperature_site'     => $this->tempSite,
            'weight_kg'            => $this->weightKg ?: null,
            'height_cm'            => $this->heightCm ?: null,
            'blood_glucose'        => $this->bloodGlucose ?: null,
            'blood_glucose_timing' => $this->bloodGlucose ? $this->glucoseTiming : null,
            'pain_score'       => $this->painScore !== '' ? (int) $this->painScore : null,
            'notes'            => $this->vitalsNotes ?: null,
        ];

        $this->nursingService->recordVitals($data, Auth::guard('tenant')->id());

        $this->successMessage = 'Vitals recorded.';
        $this->resetVitalsForm();
    }

    // -------------------------------------------------------------------------
    // Assessment
    // -------------------------------------------------------------------------

    public function saveAssessment(): void
    {
        $this->checkPermission('nursing.assess');

        if (!$this->activeEncounterId) {
            $this->errorMessage = 'No encounter linked to this queue entry.';
            return;
        }

        $assessment = $this->nursingService->getOrCreateAssessment($this->activeEncounterId, $this->activePatientId);

        $this->nursingService->updateAssessment($assessment, [
            'allergies_reviewed'   => $this->allergiesReviewed,
            'medications_reviewed' => $this->medicationsReviewed,
            'nursing_notes'        => $this->assessmentNotes ?: null,
        ]);

        $this->successMessage = 'Assessment saved.';
    }

    public function addAllergy(): void
    {
        $this->checkPermission('nursing.assess');

        $this->validate([
            'newAllergen'        => 'required|string|max:200',
            'newAllergyReaction' => 'nullable|string|max:300',
            'newAllergySeverity' => 'required|in:mild,moderate,severe,life_threatening',
        ]);

        if (!$this->activePatientId) return;

        PatientAllergy::on('tenant')->create([
            'patient_id'  => $this->activePatientId,
            'allergen'    => $this->newAllergen,
            'reaction'    => $this->newAllergyReaction ?: null,
            'severity'    => $this->newAllergySeverity,
            'is_active'   => true,
            'recorded_by' => Auth::guard('tenant')->id(),
        ]);

        $this->newAllergen        = '';
        $this->newAllergyReaction = '';
        $this->newAllergySeverity = 'mild';
        $this->showAddAllergy     = false;
        $this->loadPatientAllergiesAndMeds();
    }

    public function addMedication(): void
    {
        $this->checkPermission('nursing.assess');

        $this->validate([
            'newMedName'      => 'required|string|max:200',
            'newMedDosage'    => 'nullable|string|max:100',
            'newMedFrequency' => 'nullable|string|max:100',
        ]);

        if (!$this->activePatientId) return;

        PatientMedication::on('tenant')->create([
            'patient_id'      => $this->activePatientId,
            'medication_name' => $this->newMedName,
            'dosage'          => $this->newMedDosage ?: null,
            'frequency'       => $this->newMedFrequency ?: null,
            'is_current'      => true,
            'recorded_by'     => Auth::guard('tenant')->id(),
        ]);

        $this->newMedName         = '';
        $this->newMedDosage       = '';
        $this->newMedFrequency    = '';
        $this->showAddMedication  = false;
        $this->loadPatientAllergiesAndMeds();
    }

    // -------------------------------------------------------------------------
    // Procedures
    // -------------------------------------------------------------------------

    public function saveProcedure(): void
    {
        $this->checkPermission('nursing.assess');

        $this->validate([
            'procedureType' => 'required|string',
            'procedureName' => 'required|string|max:200',
        ]);

        if (!$this->activeEncounterId) {
            $this->errorMessage = 'No encounter linked to this queue entry.';
            return;
        }

        try {
            $this->nursingService->addProcedure(
                encounterId:   $this->activeEncounterId,
                procedureType: $this->procedureType,
                procedureName: $this->procedureName,
                performedBy:   Auth::guard('tenant')->id(),
                details:       $this->procedureDetails ?: null,
                outcome:       $this->procedureOutcome ?: null,
                site:          $this->procedureSite ?: null,
            );

            $this->successMessage    = 'Procedure recorded.';
            $this->showProcedureForm = false;
            $this->resetProcedureForm();
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Complete nursing → transition queue
    // -------------------------------------------------------------------------

    public function completeAndSendToDoctor(): void
    {
        $this->checkPermission('nursing.assess');

        if (!$this->activeEntryId || !$this->activeEncounterId) {
            $this->errorMessage = 'No active patient selected.';
            return;
        }

        if (!$this->allergiesReviewed || !$this->medicationsReviewed) {
            $this->errorMessage = 'Tick both review checkboxes before sending to doctor.';
            return;
        }

        // Auto-save assessment with current checkbox state so the nurse
        // doesn't have to click "Save Assessment" separately first.
        $assessment = $this->nursingService->getOrCreateAssessment($this->activeEncounterId, $this->activePatientId);
        $this->nursingService->updateAssessment($assessment, [
            'allergies_reviewed'   => $this->allergiesReviewed,
            'medications_reviewed' => $this->medicationsReviewed,
            'nursing_notes'        => $this->assessmentNotes ?: null,
            'status'               => 'completed',
        ]);
        $assessment = $assessment->fresh();

        $entry = QueueEntry::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($this->activeEntryId);

        try {
            $this->nursingService->completeNursingAndTransition(
                $assessment,
                $entry,
                Auth::guard('tenant')->id(),
            );

            $this->successMessage    = 'Patient sent to doctor.';
            $this->activeEntryId     = null;
            $this->activeEncounterId = null;
            $this->activePatientId   = null;
            $this->activePatientName = '';
            $this->resetForms();
            $this->loadQueue();
        } catch (\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    public function render()
    {
        return view('livewire.tenant.nursing.nurse-dashboard')
            ->layout('layouts.app-workspace');
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function checkPermission(string $perm): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission($perm)) {
            abort(403);
        }
    }

    private function resetForms(): void
    {
        $this->resetVitalsForm();
        $this->resetProcedureForm();
        $this->allergiesReviewed   = false;
        $this->medicationsReviewed = false;
        $this->chiefComplaint      = '';
        $this->assessmentNotes     = '';
        $this->successMessage      = '';
        $this->errorMessage        = '';
        $this->showProcedureForm   = false;
        $this->patientAllergies    = [];
        $this->patientMedications  = [];
        $this->showAddAllergy      = false;
        $this->showAddMedication   = false;
        $this->newAllergen         = '';
        $this->newAllergyReaction  = '';
        $this->newAllergySeverity  = 'mild';
        $this->newMedName          = '';
        $this->newMedDosage        = '';
        $this->newMedFrequency     = '';
    }

    private function resetVitalsForm(): void
    {
        $this->systolic        = '';
        $this->diastolic       = '';
        $this->pulse           = '';
        $this->respiratoryRate = '';
        $this->oxygenSat       = '';
        $this->temperature     = '';
        $this->tempSite        = 'oral';
        $this->weightKg        = '';
        $this->heightCm        = '';
        $this->bmi             = '';
        $this->bloodGlucose    = '';
        $this->glucoseTiming   = 'fasting';
        $this->painScore       = '';
        $this->vitalsNotes     = '';
    }

    private function resetProcedureForm(): void
    {
        $this->procedureType    = '';
        $this->procedureName    = '';
        $this->procedureDetails = '';
        $this->procedureOutcome = '';
        $this->procedureSite    = '';
    }
}
