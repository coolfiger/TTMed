<?php

namespace App\Livewire\Tenant\Doctor;

use App\Models\Tenant\ConsultationNote;
use App\Models\Tenant\Diagnosis;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\Icd10Code;
use App\Models\Tenant\ImagingOrder;
use App\Models\Tenant\ImagingReport;
use App\Models\Tenant\LabOrder;
use App\Models\Tenant\PatientAllergy;
use App\Models\Tenant\PatientMedication;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\Referral;
use App\Models\Tenant\VitalSign;
use App\Services\ConsultationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DoctorDashboard extends Component
{
    // -------------------------------------------------------------------------
    // Queue
    // -------------------------------------------------------------------------
    public int   $branchId = 0;
    public array $queue    = [];

    // -------------------------------------------------------------------------
    // Active patient / encounter
    // -------------------------------------------------------------------------
    public ?int   $activeEntryId     = null;
    public ?int   $activeEncounterId = null;
    public ?int   $activePatientId   = null;
    public string $activePatientName = '';
    public string $activeEntryStatus = '';

    // -------------------------------------------------------------------------
    // Patient context (loaded on selectPatient)
    // -------------------------------------------------------------------------
    public array  $patientHistory   = [];   // last 3 completed encounters
    public array  $allergies        = [];
    public array  $medications      = [];

    // -------------------------------------------------------------------------
    // Imaging comparison
    // -------------------------------------------------------------------------
    public array $compareReportIds    = [];
    public bool  $showComparisonModal = false;

    // -------------------------------------------------------------------------
    // SOAP note fields
    // -------------------------------------------------------------------------
    public string $subjective            = '';
    public string $objective             = '';
    public string $assessment            = '';
    public string $plan                  = '';
    public string $examinationFindings   = '';
    public string $investigationsOrdered = '';
    public string $followUpInstructions  = '';
    public string $followUpDays          = '';

    // -------------------------------------------------------------------------
    // Diagnosis form
    // -------------------------------------------------------------------------
    public bool   $showDiagnosisForm = false;
    public string $diagnosisSearch   = '';
    public array  $icdResults        = [];
    public ?int   $selectedIcdId     = null;
    public string $selectedIcdCode   = '';
    public string $selectedIcdText   = '';
    public string $diagnosisType     = 'primary';
    public string $certainty         = 'confirmed';
    public string $diagnosisNotes    = '';

    // -------------------------------------------------------------------------
    // Referral form
    // -------------------------------------------------------------------------
    public bool   $showReferralForm    = false;
    public string $referredToName      = '';
    public string $referredToSpecialty = '';
    public string $referredToFacility  = '';
    public string $referralUrgency     = 'routine';
    public string $referralReason      = '';
    public string $clinicalSummary     = '';
    public string $patientInstructions = '';

    // -------------------------------------------------------------------------
    // UI state
    // -------------------------------------------------------------------------
    public string $activeTab      = 'soap';
    public string $successMessage = '';
    public string $errorMessage   = '';

    protected ConsultationService $consultationService;

    public function boot(ConsultationService $consultationService): void
    {
        $this->consultationService = $consultationService;
    }

    public function mount(): void
    {
        $this->checkPermission('encounters.view');
        $user           = Auth::guard('tenant')->user();
        $this->branchId = $user?->branch_id ?? 0;
        $this->loadQueue();
    }

    // -------------------------------------------------------------------------
    // Queue
    // -------------------------------------------------------------------------

    public function loadQueue(): void
    {
        $this->queue = QueueEntry::on('tenant')
            ->with('patient')
            ->forBranchToday($this->branchId)
            ->whereIn('status', ['awaiting_doctor', 'with_doctor'])
            ->orderByRaw("FIELD(status, 'with_doctor', 'awaiting_doctor')")
            ->orderBy('is_priority', 'desc')
            ->orderBy('queue_number')
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'queue_number' => $e->queue_number,
                'patient_name' => $e->patient?->full_name ?? '—',
                'status'       => $e->status,
                'is_priority'  => $e->is_priority,
            ])
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Call patient in (awaiting_doctor → with_doctor)
    // -------------------------------------------------------------------------

    public function callPatient(int $entryId): void
    {
        $this->checkPermission('encounters.view');

        $entry = QueueEntry::on('tenant')
            ->with('patient')
            ->where('branch_id', $this->branchId)
            ->where('status', 'awaiting_doctor')
            ->findOrFail($entryId);

        $entry->update(['status' => 'with_doctor']);

        if ($entry->encounter_id) {
            Encounter::on('tenant')
                ->where('id', $entry->encounter_id)
                ->update([
                    'status'            => 'with_doctor',
                    'seen_by_doctor_at' => now(),
                ]);
        }

        $this->selectPatient($entryId);
        $this->successMessage = 'Patient called in.';
        $this->loadQueue();
    }

    // -------------------------------------------------------------------------
    // Select patient
    // -------------------------------------------------------------------------

    public function selectPatient(int $entryId): void
    {
        $this->checkPermission('encounters.view');

        $entry = QueueEntry::on('tenant')
            ->with('patient')
            ->where('branch_id', $this->branchId)
            ->findOrFail($entryId);

        $this->activeEntryId     = $entry->id;
        $this->activePatientId   = $entry->patient_id;
        $this->activePatientName = $entry->patient?->full_name ?? '—';
        $this->activeEncounterId = $entry->encounter_id;
        $this->activeEntryStatus = $entry->status;

        $this->resetForms();
        $this->activeTab         = 'soap';
        $this->compareReportIds  = [];
        $this->showComparisonModal = false;

        // Load existing SOAP note
        if ($this->activeEncounterId) {
            $note = ConsultationNote::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->first();

            if ($note) {
                $this->subjective            = $note->subjective ?? '';
                $this->objective             = $note->objective ?? '';
                $this->assessment            = $note->assessment ?? '';
                $this->plan                  = $note->plan ?? '';
                $this->examinationFindings   = $note->examination_findings ?? '';
                $this->investigationsOrdered = $note->investigations_ordered ?? '';
                $this->followUpInstructions  = $note->follow_up_instructions ?? '';
                $this->followUpDays          = $note->follow_up_days ? (string) $note->follow_up_days : '';
            }
        }

        // Load patient history (previous completed encounters)
        $this->loadPatientContext();
    }

    private function loadPatientContext(): void
    {
        if (!$this->activePatientId) {
            return;
        }

        // Last 3 completed encounters (excluding current)
        $pastEncounters = Encounter::on('tenant')
            ->where('patient_id', $this->activePatientId)
            ->where('status', 'completed')
            ->when($this->activeEncounterId, fn ($q) =>
                $q->where('id', '!=', $this->activeEncounterId)
            )
            ->orderByDesc('encounter_date')
            ->limit(3)
            ->get();

        $this->patientHistory = $pastEncounters->map(function ($enc) {
            $note = ConsultationNote::on('tenant')
                ->where('encounter_id', $enc->id)
                ->first();

            $diagnoses = Diagnosis::on('tenant')
                ->where('encounter_id', $enc->id)
                ->where('diagnosis_type', 'primary')
                ->pluck('diagnosis_text')
                ->toArray();

            return [
                'id'              => $enc->id,
                'encounter_date'  => $enc->encounter_date?->format('d M Y'),
                'chief_complaint' => $enc->chief_complaint,
                'assessment'      => $note?->assessment,
                'plan'            => $note?->plan,
                'diagnoses'       => $diagnoses,
            ];
        })->toArray();

        // Current allergies
        $this->allergies = PatientAllergy::on('tenant')
            ->where('patient_id', $this->activePatientId)
            ->where('is_active', true)
            ->get()
            ->map(fn ($a) => [
                'allergen'  => $a->allergen,
                'reaction'  => $a->reaction,
                'severity'  => $a->severity,
            ])
            ->toArray();

        // Current medications
        $this->medications = PatientMedication::on('tenant')
            ->where('patient_id', $this->activePatientId)
            ->where('is_current', true)
            ->get()
            ->map(fn ($m) => [
                'medication_name' => $m->medication_name,
                'dosage'          => $m->dosage,
                'frequency'       => $m->frequency,
                'route'           => $m->route,
            ])
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Imaging comparison
    // -------------------------------------------------------------------------

    public function toggleCompare(int $reportId): void
    {
        if (in_array($reportId, $this->compareReportIds)) {
            $this->compareReportIds = array_values(
                array_filter($this->compareReportIds, fn ($id) => $id !== $reportId)
            );
            $this->showComparisonModal = false;
        } elseif (count($this->compareReportIds) < 2) {
            $this->compareReportIds[] = $reportId;
        }
    }

    public function openComparison(): void
    {
        if (count($this->compareReportIds) === 2) {
            $this->showComparisonModal = true;
        }
    }

    public function closeComparison(): void
    {
        $this->showComparisonModal = false;
    }

    // -------------------------------------------------------------------------
    // SOAP note
    // -------------------------------------------------------------------------

    public function saveNote(): void
    {
        $this->checkPermission('encounters.view');

        if (!$this->activeEncounterId) {
            $this->errorMessage = 'No encounter selected.';
            return;
        }

        $this->validate([
            'followUpDays' => 'nullable|integer|min:1|max:365',
        ]);

        $note = $this->consultationService->getOrCreateNote(
            $this->activeEncounterId,
            Auth::guard('tenant')->id()
        );

        if ($note->isFinalized()) {
            $this->errorMessage = 'This note has been finalized and cannot be edited.';
            return;
        }

        $this->consultationService->updateNote($note, [
            'subjective'             => $this->subjective ?: null,
            'objective'              => $this->objective ?: null,
            'assessment'             => $this->assessment ?: null,
            'plan'                   => $this->plan ?: null,
            'examination_findings'   => $this->examinationFindings ?: null,
            'investigations_ordered' => $this->investigationsOrdered ?: null,
            'follow_up_instructions' => $this->followUpInstructions ?: null,
            'follow_up_days'         => $this->followUpDays ?: null,
        ]);

        $this->successMessage = 'Note saved.';
        $this->errorMessage   = '';
    }

    public function finalizeNote(): void
    {
        $this->checkPermission('encounters.view');

        if (!$this->activeEncounterId) {
            $this->errorMessage = 'No encounter selected.';
            return;
        }

        $note = ConsultationNote::on('tenant')
            ->where('encounter_id', $this->activeEncounterId)
            ->first();

        if (!$note) {
            $this->errorMessage = 'Save the note before finalizing.';
            return;
        }

        try {
            $this->consultationService->finalizeNote($note, Auth::guard('tenant')->id());
            $this->successMessage = 'Note finalized.';
            $this->errorMessage   = '';
        } catch (\LogicException|\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function completeEncounter(): void
    {
        $this->checkPermission('encounters.view');

        if (!$this->activeEntryId || !$this->activeEncounterId) {
            $this->errorMessage = 'No active patient selected.';
            return;
        }

        $note = ConsultationNote::on('tenant')
            ->where('encounter_id', $this->activeEncounterId)
            ->first();

        if (!$note || !$note->isFinalized()) {
            $this->errorMessage = 'Finalize the consultation note before completing the encounter.';
            return;
        }

        $entry = QueueEntry::on('tenant')
            ->where('branch_id', $this->branchId)
            ->findOrFail($this->activeEntryId);

        try {
            $this->consultationService->completeConsultationAndTransition(
                $note,
                $entry,
                Auth::guard('tenant')->id(),
            );

            $this->successMessage      = 'Encounter completed.';
            $this->activeEntryId       = null;
            $this->activeEncounterId   = null;
            $this->activePatientId     = null;
            $this->activePatientName   = '';
            $this->activeEntryStatus   = '';
            $this->patientHistory      = [];
            $this->allergies           = [];
            $this->medications         = [];
            $this->compareReportIds    = [];
            $this->showComparisonModal = false;
            $this->resetForms();
            $this->loadQueue();
        } catch (\LogicException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // ICD-10 search
    // -------------------------------------------------------------------------

    public function searchIcd(): void
    {
        if (strlen(trim($this->diagnosisSearch)) < 2) {
            $this->icdResults = [];
            return;
        }

        $this->icdResults = Icd10Code::on('tenant')
            ->active()
            ->search($this->diagnosisSearch)
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'id'          => $c->id,
                'code'        => $c->code,
                'description' => $c->description,
                'display'     => $c->display,
            ])
            ->toArray();
    }

    public function selectIcd(int $id): void
    {
        $code = Icd10Code::on('tenant')->find($id);
        if (!$code) {
            return;
        }

        $this->selectedIcdId   = $code->id;
        $this->selectedIcdCode = $code->code;
        $this->selectedIcdText = $code->description;
        $this->diagnosisSearch = $code->display;
        $this->icdResults      = [];
    }

    // -------------------------------------------------------------------------
    // Diagnoses
    // -------------------------------------------------------------------------

    public function saveDiagnosis(): void
    {
        $this->checkPermission('encounters.view');

        if (!$this->activeEncounterId || !$this->activePatientId) {
            $this->errorMessage = 'No active encounter.';
            return;
        }

        $this->validate([
            'diagnosisType'   => 'required|in:primary,secondary,differential',
            'certainty'       => 'required|in:confirmed,probable,suspected,ruled_out',
            'selectedIcdText' => 'required|string|max:500',
        ]);

        $this->consultationService->addDiagnosis(
            encounterId:   $this->activeEncounterId,
            patientId:     $this->activePatientId,
            diagnosedBy:   Auth::guard('tenant')->id(),
            diagnosisType: $this->diagnosisType,
            certainty:     $this->certainty,
            diagnosisText: $this->selectedIcdText,
            icd10CodeId:   $this->selectedIcdId,
            icd10Code:     $this->selectedIcdCode ?: null,
            notes:         $this->diagnosisNotes ?: null,
        );

        $this->successMessage    = 'Diagnosis added.';
        $this->showDiagnosisForm = false;
        $this->resetDiagnosisForm();
    }

    public function removeDiagnosis(int $diagnosisId): void
    {
        $this->checkPermission('encounters.view');

        $diagnosis = Diagnosis::on('tenant')
            ->where('encounter_id', $this->activeEncounterId)
            ->findOrFail($diagnosisId);

        $this->consultationService->removeDiagnosis($diagnosis);
        $this->successMessage = 'Diagnosis removed.';
    }

    // -------------------------------------------------------------------------
    // Referrals
    // -------------------------------------------------------------------------

    public function saveReferral(): void
    {
        $this->checkPermission('encounters.view');

        if (!$this->activeEncounterId || !$this->activePatientId) {
            $this->errorMessage = 'No active encounter.';
            return;
        }

        $this->validate([
            'referredToName'  => 'required|string|max:200',
            'referralUrgency' => 'required|in:routine,urgent,emergency',
            'referralReason'  => 'required|string',
        ]);

        $this->consultationService->addReferral(
            encounterId:     $this->activeEncounterId,
            patientId:       $this->activePatientId,
            referredBy:      Auth::guard('tenant')->id(),
            referredToName:  $this->referredToName,
            urgency:         $this->referralUrgency,
            reason:          $this->referralReason,
            specialty:       $this->referredToSpecialty ?: null,
            facility:        $this->referredToFacility ?: null,
            clinicalSummary: $this->clinicalSummary ?: null,
            instructions:    $this->patientInstructions ?: null,
        );

        $this->successMessage   = 'Referral created.';
        $this->showReferralForm = false;
        $this->resetReferralForm();
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    public function render()
    {
        $vitals          = [];
        $diagnoses       = [];
        $labOrders       = [];
        $imagingReports  = [];
        $referrals       = [];
        $noteIsFinalized = false;

        if ($this->activeEncounterId && $this->activePatientId) {
            $vitals = VitalSign::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->orderByDesc('recorded_at')
                ->get()
                ->toArray();

            $diagnoses = Diagnosis::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->with('diagnosedBy')
                ->get()
                ->toArray();

            // All lab orders for the patient, flagged if from current encounter
            $labOrders = LabOrder::on('tenant')
                ->where('patient_id', $this->activePatientId)
                ->with(['items.results', 'attachments'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($order) => array_merge($order->toArray(), [
                    'is_current' => $order->encounter_id === $this->activeEncounterId,
                ]))
                ->toArray();

            // All imaging reports for the patient, with order info
            $imagingReports = ImagingReport::on('tenant')
                ->where('patient_id', $this->activePatientId)
                ->with('order')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($report) => [
                    'id'               => $report->id,
                    'order_number'     => $report->order?->order_number,
                    'modality'         => $report->order?->modality,
                    'body_part'        => $report->order?->body_part,
                    'study_description'=> $report->order?->study_description,
                    'findings'         => $report->findings,
                    'impression'       => $report->impression,
                    'recommendations'  => $report->recommendations,
                    'radiologist_name' => $report->radiologist_name,
                    'has_pdf'          => !empty($report->report_pdf_path),
                    'report_date'      => $report->created_at?->format('d M Y'),
                    'is_current'       => $report->order?->encounter_id === $this->activeEncounterId,
                ])
                ->toArray();

            $referrals = Referral::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->get()
                ->toArray();

            $note = ConsultationNote::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->first();
            $noteIsFinalized = $note?->isFinalized() ?? false;
        }

        return view('livewire.tenant.doctor.doctor-dashboard', [
            'vitals'          => $vitals,
            'diagnoses'       => $diagnoses,
            'labOrders'       => $labOrders,
            'imagingReports'  => $imagingReports,
            'referrals'       => $referrals,
            'noteIsFinalized' => $noteIsFinalized,
        ])->layout('layouts.app-workspace');
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
        $this->subjective            = '';
        $this->objective             = '';
        $this->assessment            = '';
        $this->plan                  = '';
        $this->examinationFindings   = '';
        $this->investigationsOrdered = '';
        $this->followUpInstructions  = '';
        $this->followUpDays          = '';
        $this->successMessage        = '';
        $this->errorMessage          = '';
        $this->showDiagnosisForm     = false;
        $this->showReferralForm      = false;
        $this->resetDiagnosisForm();
        $this->resetReferralForm();
    }

    private function resetDiagnosisForm(): void
    {
        $this->diagnosisSearch = '';
        $this->icdResults      = [];
        $this->selectedIcdId   = null;
        $this->selectedIcdCode = '';
        $this->selectedIcdText = '';
        $this->diagnosisType   = 'primary';
        $this->certainty       = 'confirmed';
        $this->diagnosisNotes  = '';
    }

    private function resetReferralForm(): void
    {
        $this->referredToName      = '';
        $this->referredToSpecialty = '';
        $this->referredToFacility  = '';
        $this->referralUrgency     = 'routine';
        $this->referralReason      = '';
        $this->clinicalSummary     = '';
        $this->patientInstructions = '';
    }
}
