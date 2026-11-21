<?php

namespace App\Livewire\Tenant\Doctor;

use App\Models\Tenant\ConsultationNote;
use App\Models\Tenant\Diagnosis;
use App\Models\Tenant\Encounter;
use App\Models\Tenant\Icd10Code;
use App\Models\Tenant\ImagingOrder;
use App\Models\Tenant\ImagingReport;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\LabOrder;
use App\Models\Tenant\EncounterInstruction;
use App\Models\Tenant\FollowUp;
use App\Models\Tenant\NursingAssessment;
use App\Models\Tenant\NursingProcedure;
use App\Models\Tenant\PatientAllergy;
use App\Models\Tenant\PatientDocument;
use App\Models\Tenant\PatientFamilyHistory;
use App\Models\Tenant\PatientMedication;
use App\Models\Tenant\Prescription;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\Referral;
use App\Models\Tenant\Service;
use App\Models\Tenant\StaffProfile;
use App\Models\Tenant\VitalSign;
use App\Services\BillingService;
use App\Services\ConsultationService;
use App\Services\PrescriptionService;
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
    public array  $patientHistory   = [];
    public int    $historyLimit     = 3;    // initially show 3; "load more" bumps by 5
    public int    $historyTotal     = 0;    // total completed encounters for this patient
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
    // Billing selector (pre-checkout service selection)
    // -------------------------------------------------------------------------
    public bool   $showBillingSelector  = false;
    public array  $billingCatalog       = [];
    public array  $selectedServiceIds   = [];
    public string $billingSearchQuery   = '';

    // -------------------------------------------------------------------------
    // Prescription builder
    // -------------------------------------------------------------------------
    public bool  $showPrescriptionBuilder = false;
    public array $rxItems                 = [];

    // -------------------------------------------------------------------------
    // Instruction builder
    // -------------------------------------------------------------------------
    public bool   $showInstructionBuilder   = false;
    public array  $instructionLines         = [];
    public bool   $scheduleFollowUp         = false;
    public string $followUpScheduleDays     = '';
    public string $followUpScheduleReason   = '';

    // -------------------------------------------------------------------------
    // Post-consultation inline wizard
    // null = not in wizard; 'prescription' | 'instructions' | 'billing'
    // -------------------------------------------------------------------------
    public ?string $wizardStep = null;

    // -------------------------------------------------------------------------
    // UI state
    // -------------------------------------------------------------------------
    public string $activeTab      = 'soap';
    public string $successMessage = '';
    public string $errorMessage   = '';

    protected ConsultationService $consultationService;
    protected PrescriptionService $prescriptionService;

    public function boot(ConsultationService $consultationService, PrescriptionService $prescriptionService): void
    {
        $this->consultationService = $consultationService;
        $this->prescriptionService = $prescriptionService;
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

        $this->historyLimit = 3;
        $this->reloadHistory();

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

    public function loadMoreHistory(): void
    {
        $this->checkPermission('encounters.view');
        $this->historyLimit += 5;
        $this->reloadHistory();
    }

    private function reloadHistory(): void
    {
        if (!$this->activePatientId) return;

        $base = Encounter::on('tenant')
            ->where('patient_id', $this->activePatientId)
            ->where('status', 'completed')
            ->when($this->activeEncounterId, fn ($q) =>
                $q->where('id', '!=', $this->activeEncounterId)
            );

        $this->historyTotal = $base->count();

        $this->patientHistory = $base->orderByDesc('encounter_date')
            ->limit($this->historyLimit)
            ->get()
            ->map(function ($enc) {
                $note = ConsultationNote::on('tenant')
                    ->where('encounter_id', $enc->id)
                    ->first();

                $diagnoses = Diagnosis::on('tenant')
                    ->where('encounter_id', $enc->id)
                    ->where('diagnosis_type', 'primary')
                    ->pluck('diagnosis_text')
                    ->toArray();

                $vs = VitalSign::on('tenant')
                    ->where('encounter_id', $enc->id)
                    ->orderByDesc('recorded_at')
                    ->first();

                $vitals = null;
                if ($vs) {
                    $vitals = [
                        'systolic_bp'       => $vs->systolic_bp,
                        'diastolic_bp'      => $vs->diastolic_bp,
                        'pulse_rate'        => $vs->pulse_rate,
                        'temperature'       => $vs->temperature,
                        'oxygen_saturation' => $vs->oxygen_saturation,
                        'weight_kg'         => $vs->weight_kg,
                        'blood_glucose'     => $vs->blood_glucose,
                        'blood_glucose_timing' => $vs->blood_glucose_timing,
                    ];
                }

                return [
                    'id'              => $enc->id,
                    'encounter_date'  => $enc->encounter_date?->format('d M Y'),
                    'chief_complaint' => $enc->chief_complaint,
                    'assessment'      => $note?->assessment,
                    'plan'            => $note?->plan,
                    'diagnoses'       => $diagnoses,
                    'vitals'          => $vitals,
                ];
            })->toArray();
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
            $this->openPrescriptionBuilder();
        } catch (\LogicException|\InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Billing selector
    // -------------------------------------------------------------------------

    public function openBillingSelector(bool $fromWizard = false): void
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

        // When called directly by the button (not from the wizard), enforce the
        // prescription → instruction flow if no prescription has been issued yet.
        if (!$fromWizard) {
            $hasPrescription = Prescription::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->where('status', 'issued')
                ->exists();

            if (!$hasPrescription) {
                $this->openPrescriptionBuilder();
                return;
            }
        }

        $this->billingCatalog = Service::on('tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'category' => $s->category ?? 'General',
                'price'    => $s->price,
            ])
            ->toArray();

        // Auto-select a consultation service matching the visit type
        $entry = QueueEntry::on('tenant')->find($this->activeEntryId);
        $visitType = $entry?->visit_type ?? 'walk_in';

        $autoSelect = Service::on('tenant')
            ->where('is_active', true)
            ->where('name', 'like', '%consultation%')
            ->when(
                in_array($visitType, ['follow_up']),
                fn ($q) => $q->orderByRaw("CASE WHEN name LIKE '%follow%' THEN 0 ELSE 1 END"),
                fn ($q) => $q->orderByRaw("CASE WHEN name LIKE '%general%' THEN 0 ELSE 1 END"),
            )
            ->first();

        $this->selectedServiceIds  = $autoSelect ? [$autoSelect->id] : [];
        $this->billingSearchQuery  = '';
        $this->showBillingSelector = true;
        $this->wizardStep          = 'billing';
    }

    public function toggleBillingService(int $serviceId): void
    {
        if (in_array($serviceId, $this->selectedServiceIds)) {
            $this->selectedServiceIds = array_values(
                array_filter($this->selectedServiceIds, fn ($id) => $id !== $serviceId)
            );
        } else {
            $this->selectedServiceIds[] = $serviceId;
        }
    }

    public function closeBillingSelector(): void
    {
        $this->showBillingSelector = false;
        $this->selectedServiceIds  = [];
        $this->billingCatalog      = [];
        $this->billingSearchQuery  = '';
        $this->wizardStep          = null;
    }

    public function completeEncounterWithBilling(): void
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

        $user = Auth::guard('tenant')->user();

        try {
            // Build the invoice if services were selected
            if (!empty($this->selectedServiceIds)) {
                $existing = Invoice::on('tenant')
                    ->where('encounter_id', $this->activeEncounterId)
                    ->whereIn('status', ['draft', 'finalized', 'partially_paid'])
                    ->latest()
                    ->first();

                if (!$existing) {
                    $billing = app(BillingService::class);
                    $invoice = $billing->createInvoice(
                        patientId:   $entry->patient_id,
                        branchId:    $entry->branch_id,
                        createdBy:   $user->id,
                        encounterId: $this->activeEncounterId,
                    );

                    $services = Service::on('tenant')
                        ->whereIn('id', $this->selectedServiceIds)
                        ->get();

                    foreach ($services as $svc) {
                        $billing->addItem(
                            invoice:        $invoice,
                            serviceId:      $svc->id,
                            description:    $svc->name,
                            quantity:       1,
                            unitPriceCents: $svc->price,
                            taxRate:        $svc->taxable ? (float) $svc->tax_rate : 0.0,
                            discountCents:  0,
                        );
                    }

                    $invoice->refresh();

                    if ($invoice->total > 0) {
                        $billing->finalizeInvoice($invoice, $user->id);
                    }
                }
            }

            $this->consultationService->completeConsultationAndTransition(
                $note,
                $entry,
                $user->id,
            );

            $this->showBillingSelector = false;
            $this->selectedServiceIds  = [];
            $this->billingCatalog      = [];
            $this->wizardStep          = null;
            $this->successMessage      = 'Encounter completed.';
            $this->activeEntryId       = null;
            $this->activeEncounterId   = null;
            $this->activePatientId     = null;
            $this->activePatientName   = '';
            $this->activeEntryStatus   = '';
            $this->patientHistory      = [];
            $this->historyLimit        = 3;
            $this->historyTotal        = 0;
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
            $this->historyLimit        = 3;
            $this->historyTotal        = 0;
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

    public function selectIcdExternal(string $code, string $description): void
    {
        $this->selectedIcdId   = null;
        $this->selectedIcdCode = $code;
        $this->selectedIcdText = $description;
        $this->diagnosisSearch = $code . ' — ' . $description;
        $this->icdResults      = [];
    }

    // -------------------------------------------------------------------------
    // Prescriptions
    // -------------------------------------------------------------------------

    public function openPrescriptionBuilder(): void
    {
        $this->rxItems = [
            ['name' => '', 'strength' => '', 'form' => 'tablet', 'dose' => '', 'frequency' => '', 'duration' => '', 'duration_unit' => 'days', 'instructions' => ''],
        ];
        $this->showPrescriptionBuilder = true;
        $this->showInstructionBuilder  = false;
        $this->wizardStep              = 'prescription';
    }

    public function addRxItem(): void
    {
        $this->rxItems[] = ['name' => '', 'strength' => '', 'form' => 'tablet', 'dose' => '', 'frequency' => '', 'duration' => '', 'duration_unit' => 'days', 'instructions' => ''];
    }

    public function removeRxItem(int $index): void
    {
        array_splice($this->rxItems, $index, 1);
    }

    public function issuePrescription(): void
    {
        $filledItems = array_filter($this->rxItems, fn ($i) => !empty(trim($i['name'] ?? '')));

        if (empty($filledItems)) {
            $this->skipPrescription();
            return;
        }

        try {
            $userId  = Auth::guard('tenant')->id();
            $profile = StaffProfile::on('tenant')->where('user_id', $userId)->first();
            $doctor  = $profile?->doctor ?? null;

            if (!$doctor) {
                $this->errorMessage = 'Doctor profile not found for your account.';
                return;
            }

            $rx = $this->prescriptionService->getOrCreateDraft($this->activeEncounterId, $doctor->id);

            foreach ($filledItems as $item) {
                $this->prescriptionService->addItem($rx, [
                    'medication_name' => trim($item['name']),
                    'strength'        => trim($item['strength']) ?: null,
                    'form'            => $item['form'] ?: null,
                    'dose'            => trim($item['dose']) ?: '1',
                    'frequency'       => trim($item['frequency']) ?: 'as directed',
                    'duration'        => is_numeric($item['duration']) ? (int) $item['duration'] : null,
                    'duration_unit'   => $item['duration_unit'] ?: 'days',
                    'instructions'    => trim($item['instructions']) ?: null,
                ]);
            }

            $this->prescriptionService->issuePrescription($rx, $userId);
            $this->showPrescriptionBuilder = false;
            $this->openInstructionBuilder();
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function skipPrescription(): void
    {
        $this->showPrescriptionBuilder = false;
        $this->openInstructionBuilder();
    }

    // -------------------------------------------------------------------------
    // Patient instructions
    // -------------------------------------------------------------------------

    public function openInstructionBuilder(): void
    {
        $existing = EncounterInstruction::on('tenant')
            ->where('encounter_id', $this->activeEncounterId)
            ->first();

        if ($existing) {
            $this->instructionLines = $existing->lines ?: [''];
        } else {
            // Seed from the SOAP note's follow-up instructions field
            $seeded = array_values(array_filter(
                array_map('trim', explode("\n", $this->followUpInstructions ?? '')),
                fn ($l) => $l !== ''
            ));
            $this->instructionLines = $seeded ?: [''];
        }

        $this->showInstructionBuilder = true;
        $this->showPrescriptionBuilder = false;
        $this->wizardStep             = 'instructions';

        // Pre-fill follow-up from consultation note if already set
        $existingFollowUp = FollowUp::on('tenant')
            ->where('encounter_id', $this->activeEncounterId)
            ->where('status', 'pending')
            ->first();

        if ($existingFollowUp) {
            $this->scheduleFollowUp       = true;
            $this->followUpScheduleDays   = $existingFollowUp->follow_up_days ? (string) $existingFollowUp->follow_up_days : '';
            $this->followUpScheduleReason = $existingFollowUp->reason ?? '';
        } else {
            $this->scheduleFollowUp       = (bool) $this->followUpDays;
            $this->followUpScheduleDays   = $this->followUpDays;
            $this->followUpScheduleReason = $this->followUpInstructions;
        }
    }

    public function addInstructionLine(): void
    {
        $this->instructionLines[] = '';
    }

    public function removeInstructionLine(int $index): void
    {
        array_splice($this->instructionLines, $index, 1);
    }

    public function toggleInstructionTemplate(string $text): void
    {
        $existing = array_search($text, $this->instructionLines);
        if ($existing !== false) {
            array_splice($this->instructionLines, $existing, 1);
        } else {
            $lines = array_filter($this->instructionLines, fn ($l) => trim($l) !== '');
            $lines[] = $text;
            $this->instructionLines = array_values($lines);
        }
    }

    public function saveInstructions(): void
    {
        $lines = array_values(array_filter(
            array_map('trim', $this->instructionLines),
            fn ($l) => $l !== ''
        ));

        if (empty($lines)) {
            $this->showInstructionBuilder = false;
            $this->openBillingSelector(fromWizard: true);
            return;
        }

        $userId = Auth::guard('tenant')->id();

        EncounterInstruction::on('tenant')->updateOrCreate(
            ['encounter_id' => $this->activeEncounterId],
            [
                'patient_id' => $this->activePatientId,
                'created_by' => $userId,
                'lines'      => $lines,
            ]
        );

        // Create/update follow-up record if the doctor scheduled one
        if ($this->scheduleFollowUp && $this->followUpScheduleDays) {
            $days = (int) $this->followUpScheduleDays;
            if ($days > 0) {
                $profile  = StaffProfile::on('tenant')->where('user_id', $userId)->first();
                $doctorId = $profile?->doctor?->id;

                if ($doctorId) {
                    FollowUp::on('tenant')->updateOrCreate(
                        ['encounter_id' => $this->activeEncounterId],
                        [
                            'patient_id'      => $this->activePatientId,
                            'doctor_id'       => $doctorId,
                            'branch_id'       => $this->branchId,
                            'follow_up_days'  => $days,
                            'target_date'     => now()->addDays($days)->toDateString(),
                            'reason'          => $this->followUpScheduleReason ?: null,
                            'instructions'    => $this->followUpScheduleReason ?: null,
                            'status'          => 'pending',
                            'created_by'      => $userId,
                        ]
                    );
                }
            }
        }

        $this->showInstructionBuilder = false;
        $this->successMessage         = 'Instructions saved.';
        $this->openBillingSelector(fromWizard: true);
    }

    public function skipInstructions(): void
    {
        $this->showInstructionBuilder = false;
        $this->openBillingSelector(fromWizard: true);
    }

    public function backToPrescription(): void
    {
        // Load items from the most recent issued prescription so the doctor can revise
        $issued = Prescription::on('tenant')
            ->with('items')
            ->where('encounter_id', $this->activeEncounterId)
            ->where('status', Prescription::STATUS_ISSUED)
            ->latest()
            ->first();

        if ($issued) {
            $loaded = $issued->items->map(fn ($item) => [
                'name'          => $item->medication_name,
                'strength'      => $item->strength ?? '',
                'form'          => $item->form ?? 'tablet',
                'dose'          => $item->dose ?? '',
                'frequency'     => $item->frequency ?? '',
                'duration'      => $item->duration ? (string) $item->duration : '',
                'duration_unit' => $item->duration_unit ?? 'days',
                'instructions'  => $item->instructions ?? '',
            ])->toArray();

            $this->rxItems = $loaded ?: [['name' => '', 'strength' => '', 'form' => 'tablet', 'dose' => '', 'frequency' => '', 'duration' => '', 'duration_unit' => 'days', 'instructions' => '']];

            $this->prescriptionService->cancelPrescription(
                $issued,
                'Revised by doctor',
                Auth::guard('tenant')->id()
            );
        } else {
            $this->rxItems = [['name' => '', 'strength' => '', 'form' => 'tablet', 'dose' => '', 'frequency' => '', 'duration' => '', 'duration_unit' => 'days', 'instructions' => '']];
        }

        $this->showPrescriptionBuilder = true;
        $this->showInstructionBuilder  = false;
        $this->wizardStep              = 'prescription';
    }

    public function backToInstructions(): void
    {
        $this->openInstructionBuilder();
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
        $nursingAssessment = null;
        $nursingProcedures = [];
        $encounterDocuments = [];
        $familyHistories   = [];

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
                ->with(['items.result', 'attachments'])
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

            $rawAssessment = NursingAssessment::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->first();
            if ($rawAssessment) {
                $nursingAssessment = [
                    'chief_complaint'  => $rawAssessment->chief_complaint,
                    'nursing_notes'    => $rawAssessment->nursing_notes,
                    'status'           => $rawAssessment->status,
                ];
            }

            $nursingProcedures = NursingProcedure::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->orderBy('performed_at')
                ->get()
                ->map(fn ($p) => [
                    'type_label'   => $p->type_label,
                    'name'         => $p->procedure_name,
                    'site'         => $p->site,
                    'outcome'      => $p->outcome,
                    'performed_at' => $p->performed_at?->format('H:i'),
                ])
                ->toArray();

            $encounterDocuments = PatientDocument::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->where('is_archived', false)
                ->orderBy('created_at')
                ->get()
                ->map(fn ($d) => [
                    'id'                => $d->id,
                    'title'             => $d->title,
                    'description'       => $d->description,
                    'original_filename' => $d->original_filename,
                    'extension'         => $d->extension,
                    'file_size_label'   => $d->file_size_label,
                    'is_viewable'       => $d->isViewable(),
                    'mime_type'         => $d->mime_type,
                ])
                ->toArray();

            $familyHistories = PatientFamilyHistory::on('tenant')
                ->where('patient_id', $this->activePatientId)
                ->orderBy('relationship')
                ->get()
                ->map(fn ($h) => [
                    'relationship_label' => $h->relationship_label,
                    'condition'          => $h->condition,
                    'notes'              => $h->notes,
                ])
                ->toArray();
        }

        $savedFollowUp = $this->activeEncounterId
            ? FollowUp::on('tenant')
                ->where('encounter_id', $this->activeEncounterId)
                ->whereIn('status', ['pending', 'booked'])
                ->first()
            : null;

        return view('livewire.tenant.doctor.doctor-dashboard', [
            'vitals'             => $vitals,
            'diagnoses'          => $diagnoses,
            'labOrders'          => $labOrders,
            'imagingReports'     => $imagingReports,
            'referrals'          => $referrals,
            'noteIsFinalized'    => $noteIsFinalized,
            'nursingAssessment'  => $nursingAssessment,
            'nursingProcedures'  => $nursingProcedures,
            'encounterDocuments' => $encounterDocuments,
            'familyHistories'    => $familyHistories,
            'savedFollowUp'      => $savedFollowUp,
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
        $this->scheduleFollowUp      = false;
        $this->followUpScheduleDays  = '';
        $this->followUpScheduleReason = '';
        $this->wizardStep            = null;
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
