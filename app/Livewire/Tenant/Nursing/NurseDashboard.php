<?php

namespace App\Livewire\Tenant\Nursing;

use App\Models\Tenant\Encounter;
use App\Models\Tenant\NursingAssessment;
use App\Models\Tenant\NursingProcedure;
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
                $this->assessmentNotes     = $assessment->assessment_notes ?? '';
            }
        }
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
            'pulse'            => $this->pulse ?: null,
            'respiratory_rate' => $this->respiratoryRate ?: null,
            'oxygen_saturation' => $this->oxygenSat ?: null,
            'temperature'      => $this->temperature ?: null,
            'temperature_site' => $this->tempSite,
            'weight_kg'        => $this->weightKg ?: null,
            'height_cm'        => $this->heightCm ?: null,
            'blood_glucose'    => $this->bloodGlucose ?: null,
            'glucose_timing'   => $this->bloodGlucose ? $this->glucoseTiming : null,
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

        $assessment = $this->nursingService->getOrCreateAssessment($this->activeEncounterId);

        $this->nursingService->updateAssessment($assessment, [
            'allergies_reviewed'   => $this->allergiesReviewed,
            'medications_reviewed' => $this->medicationsReviewed,
            'assessment_notes'     => $this->assessmentNotes ?: null,
        ]);

        $this->successMessage = 'Assessment saved.';
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

        $assessment = NursingAssessment::on('tenant')
            ->where('encounter_id', $this->activeEncounterId)
            ->first();

        if (!$assessment || !$assessment->isComplete()) {
            $this->errorMessage = 'Mark allergies and medications as reviewed before completing.';
            return;
        }

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
            ->layout('layouts.app');
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
