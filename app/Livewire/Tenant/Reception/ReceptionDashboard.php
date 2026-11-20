<?php

namespace App\Livewire\Tenant\Reception;

use App\Events\QueueUpdated;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Doctor;
use App\Models\Tenant\DoctorAvailability;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Patient;
use App\Models\Tenant\PatientIntakeToken;
use App\Models\Tenant\QueueEntry;
use App\Models\Tenant\Service;
use App\Services\AuditService;
use App\Services\BillingService;
use App\Services\DocumentService;
use App\Services\QueueService;
use App\Tenancy\TenantContext;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReceptionDashboard extends Component
{
    use WithFileUploads;
    public int    $branchId     = 0;
    public string $patientSearch = '';
    public array  $searchResults = [];
    public bool   $showCheckinForm = false;

    // Patient card state (shown after selection, before queue wizard)
    public int    $selectedPatientId        = 0;
    public string $selectedPatientName      = '';
    public string $selectedPatientNumber    = '';
    public int    $selectedPatientAllergyCount = 0;
    public string $selectedPatientBloodType = '';

    // Queue wizard state
    public bool   $showQueueWizard = false;
    public int    $selectedDoctorId  = 0;
    public string $visitType         = 'walk_in';
    public string $chiefComplaint    = '';
    public bool   $isPriority        = false;
    public string $priorityReason    = '';

    // QR panel state (existing patient intake)
    public bool   $showQrPanel = false;
    public string $qrSvg       = '';
    public string $qrPin       = '';
    public string $qrUrl       = '';

    // New patient registration QR
    public bool    $showRegisterPanel  = false;
    public ?string $regQrSvg           = null;
    public ?string $regToken           = null;
    public string  $regStatus          = '';  // '' | 'pending' | 'complete'
    public ?int    $regPatientId       = null;
    public string  $regExpiresAt       = '';

    // Queue display
    public array  $queueEntries      = [];
    public array  $doctorStatuses    = [];

    // Checkout panel
    public bool    $showCheckout        = false;
    public ?int    $checkoutEntryId     = null;
    public string  $checkoutPatientName = '';
    public ?int    $checkoutInvoiceId   = null;
    public int     $checkoutTotal       = 0;    // cents
    public int     $checkoutAmountDue   = 0;    // cents
    public string  $checkoutMethod      = 'cash';
    public string  $checkoutAmountInput = '';
    public string  $checkoutReference   = '';
    public string  $checkoutError       = '';
    public bool    $checkoutPaid        = false;
    public array   $checkoutItems       = [];

    // Document upload step (shown after successful check-in)
    public bool   $showDocumentUpload   = false;
    public ?int   $uploadPatientId      = null;
    public ?int   $uploadEncounterId    = null;
    public string $uploadPatientName    = '';
    public array  $pendingDocuments     = [];   // [{tmpPath, name, size, type}]
    public        $docFile              = null; // Livewire temp upload
    public string $docTitle             = '';
    public string $docUploadError       = '';
    public array  $uploadedDocNames     = [];

    public function mount(): void
    {
        $user = auth()->guard('tenant')->user();
        $this->branchId = $user?->staffProfile?->branch_id ?? Branch::on('tenant')->where('is_main', true)->value('id') ?? 0;
        $this->loadQueue();
        $this->loadDoctorStatuses();
    }

    public function updatedPatientSearch(): void
    {
        if (strlen($this->patientSearch) < 2) {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = Patient::on('tenant')
            ->search($this->patientSearch)
            ->where('is_active', true)
            ->limit(8)
            ->get(['id', 'patient_number', 'first_name', 'last_name', 'date_of_birth', 'phone_primary'])
            ->map(fn ($p) => [
                'id'            => $p->id,
                'display_name'  => $p->display_name,
                'patient_number' => $p->patient_number,
                'dob'           => $p->date_of_birth?->format('d/m/Y'),
                'phone'         => $p->phone_primary,
            ])
            ->toArray();
    }

    public function selectPatient(int $id): void
    {
        $patient = Patient::on('tenant')
            ->with('allergies')
            ->find($id);

        if (!$patient) {
            return;
        }

        $this->selectedPatientId           = $patient->id;
        $this->selectedPatientName         = $patient->display_name;
        $this->selectedPatientNumber       = $patient->patient_number;
        $this->selectedPatientAllergyCount = $patient->allergies->count();
        $this->selectedPatientBloodType    = $patient->blood_type ?? '';
        $this->patientSearch               = '';
        $this->searchResults               = [];
        $this->showCheckinForm             = true;
        $this->showQueueWizard             = false;
        $this->showQrPanel                 = false;
    }

    public function startQueueWizard(): void
    {
        $this->showQueueWizard = true;
        $this->showQrPanel     = false;
    }

    public function generatePatientQr(): void
    {
        $this->checkPermission('queue.view');

        $patient = Patient::on('tenant')->find($this->selectedPatientId);
        if (!$patient) return;

        PatientIntakeToken::on('tenant')
            ->where('patient_id', $patient->id)
            ->whereNull('used_at')
            ->delete();

        $token = Str::random(48);
        $pin   = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        PatientIntakeToken::on('tenant')->create([
            'patient_id'  => $patient->id,
            'token'       => $token,
            'pin'         => $pin,
            'expires_at'  => now()->addHours(24),
            'created_by'  => Auth::guard('tenant')->id(),
        ]);

        $url = route('tenant.patient.intake', $token);

        $renderer = new ImageRenderer(
            new RendererStyle(220),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);

        $this->qrSvg       = $writer->writeString($url);
        $this->qrPin       = $pin;
        $this->qrUrl       = $url;
        $this->showQrPanel = true;
        $this->showQueueWizard = false;
    }

    public function dismissQr(): void
    {
        $this->showQrPanel = false;
        $this->qrSvg       = '';
        $this->qrPin       = '';
    }

    public function openRegisterPanel(): void
    {
        $this->showRegisterPanel = true;
        $this->regQrSvg          = null;
        $this->regToken          = null;
        $this->regStatus         = '';
        $this->regPatientId      = null;
    }

    public function closeRegisterPanel(): void
    {
        $this->showRegisterPanel = false;
        $this->regQrSvg          = null;
        $this->regToken          = null;
        $this->regStatus         = '';
        $this->regPatientId      = null;
    }

    public function generateRegQr(): void
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('patients.create')) {
            abort(403);
        }

        // Expire any previous pending registration tokens for this user
        PatientIntakeToken::on('tenant')
            ->where('type', 'registration')
            ->where('created_by', $user->id)
            ->whereNull('patient_id')
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $token     = Str::random(40);
        $expiresAt = now()->addHours(24);

        PatientIntakeToken::on('tenant')->create([
            'type'       => 'registration',
            'patient_id' => null,
            'pin'        => null,
            'token'      => $token,
            'expires_at' => $expiresAt,
            'created_by' => $user->id,
        ]);

        $url = route('tenant.patient.register', $token);

        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $this->regQrSvg    = (new Writer($renderer))->writeString($url);
        $this->regToken    = $token;
        $this->regStatus   = 'pending';
        $this->regExpiresAt = $expiresAt->format('d M Y, H:i');
    }

    public function checkRegComplete(): void
    {
        if (!$this->regToken) return;

        $record = PatientIntakeToken::on('tenant')
            ->where('token', $this->regToken)
            ->first();

        if ($record && $record->patient_id) {
            $this->regPatientId = $record->patient_id;
            $this->regStatus    = 'complete';
        }
    }

    public function checkIn(): void
    {
        $this->checkPermission('queue.checkin');

        $this->validate([
            'selectedPatientId' => 'required|integer|min:1',
            'visitType'         => 'required|in:walk_in,appointment,emergency,follow_up',
            'isPriority'        => 'boolean',
            'priorityReason'    => 'nullable|string|max:200',
            'chiefComplaint'    => 'nullable|string|max:1000',
        ]);

        $patient = Patient::on('tenant')->find($this->selectedPatientId);

        if (!$patient) {
            $this->addError('selectedPatientId', 'Patient not found.');
            return;
        }

        $userId = auth()->guard('tenant')->id();

        try {
            $entry = app(QueueService::class)->checkIn(
                patient:         $patient,
                branchId:        $this->branchId,
                userId:          $userId,
                doctorId:        $this->selectedDoctorId ?: null,
                visitType:       $this->visitType,
                chiefComplaint:  $this->chiefComplaint ?: null,
                isPriority:      $this->isPriority,
                priorityReason:  $this->priorityReason ?: null,
            );
        } catch (\RuntimeException $e) {
            $this->addError('selectedPatientId', $e->getMessage());
            return;
        }

        app(AuditService::class)->logTenant(
            'queue.checkin',
            'Patient',
            $patient->id,
            null,
            null,
            "Queue #{$entry->queue_number}"
        );

        // Store context for the optional document upload step
        $this->uploadPatientId   = $patient->id;
        $this->uploadEncounterId = $entry->encounter_id;
        $this->uploadPatientName = $patient->display_name;
        $this->uploadedDocNames  = [];
        $this->pendingDocuments  = [];
        $this->docTitle          = '';
        $this->docFile           = null;
        $this->docUploadError    = '';

        $this->resetCheckinForm();
        $this->loadQueue();
        $this->showDocumentUpload = true;
        session()->flash('success', "{$patient->display_name} checked in — Queue #{$entry->queue_number}.");
    }

    public function removeFromQueue(int $entryId): void
    {
        $this->checkPermission('queue.checkin');

        $entry = QueueEntry::on('tenant')
            ->forBranchToday($this->branchId)
            ->active()
            ->find($entryId);

        if (!$entry) {
            return;
        }

        $userId = auth()->guard('tenant')->id();

        app(QueueService::class)->transition($entry, QueueEntry::STATUS_CANCELLED, $userId, 'Removed by receptionist');

        app(AuditService::class)->logTenant(
            'queue.removed',
            'Patient',
            $entry->patient_id,
            null,
            null,
            "Queue #{$entry->queue_number} cancelled"
        );

        $this->loadQueue();
    }

    public function openCheckout(int $entryId): void
    {
        $this->checkPermission('billing.create');

        // Open the slide-over first so any error is visible to the user
        $this->checkoutEntryId     = $entryId;
        $this->checkoutPatientName = '';
        $this->checkoutInvoiceId   = null;
        $this->checkoutTotal       = 0;
        $this->checkoutAmountDue   = 0;
        $this->checkoutItems       = [];
        $this->checkoutAmountInput = '';
        $this->checkoutMethod      = 'cash';
        $this->checkoutReference   = '';
        $this->checkoutError       = '';
        $this->checkoutPaid        = false;
        $this->showCheckout        = true;

        try {
            $entry = QueueEntry::on('tenant')
                ->where('branch_id', $this->branchId)
                ->where('status', QueueEntry::STATUS_AWAITING_CHECKOUT)
                ->with('patient')
                ->find($entryId);

            if (!$entry) {
                $this->checkoutError = 'Patient not found in awaiting-checkout queue.';
                return;
            }

            $this->checkoutPatientName = $entry->patient?->display_name ?? '';

            $user = auth()->guard('tenant')->user();

            $invoice = Invoice::on('tenant')
                ->where('encounter_id', $entry->encounter_id)
                ->whereIn('status', ['draft', 'finalized', 'partially_paid'])
                ->latest()
                ->first();

            if (!$invoice) {
                $billing = app(BillingService::class);
                $invoice = $billing->createInvoice(
                    patientId:   $entry->patient_id,
                    branchId:    $entry->branch_id,
                    createdBy:   $user->id,
                    encounterId: $entry->encounter_id,
                );

                $service = Service::on('tenant')
                    ->where('is_active', true)
                    ->where('name', 'like', '%consultation%')
                    ->orderByRaw("CASE WHEN name LIKE '%general%' THEN 0 ELSE 1 END")
                    ->first();

                if ($service) {
                    $billing->addItem(
                        invoice:        $invoice,
                        serviceId:      $service->id,
                        description:    $service->name,
                        quantity:       1,
                        unitPriceCents: $service->price,
                        taxRate:        $service->taxable ? (float) $service->tax_rate : 0.0,
                        discountCents:  0,
                    );
                    $invoice->refresh();
                }

                if ($invoice->total > 0) {
                    $billing->finalizeInvoice($invoice, $user->id);
                    $invoice->refresh();
                }
            }

            $items = $invoice->items()->get()->map(fn ($i) => [
                'description' => $i->description,
                'quantity'    => $i->quantity,
                'line_total'  => $i->line_total,
            ])->toArray();

            $this->checkoutInvoiceId   = $invoice->id;
            $this->checkoutTotal       = $invoice->total;
            $this->checkoutAmountDue   = $invoice->amount_due;
            $this->checkoutItems       = $items;
            $this->checkoutAmountInput = number_format($invoice->amount_due / 100, 2);
        } catch (\Throwable $e) {
            $this->checkoutError = 'Could not load invoice: ' . $e->getMessage();
        }
    }

    public function collectCheckoutPayment(): void
    {
        $this->checkPermission('billing.create');
        $this->checkoutError = '';

        $invoice = Invoice::on('tenant')->find($this->checkoutInvoiceId);
        if (!$invoice) {
            $this->checkoutError = 'Invoice not found.';
            return;
        }

        $amountCents = (int) round((float) str_replace(',', '', $this->checkoutAmountInput) * 100);
        if ($amountCents <= 0) {
            $this->checkoutError = 'Enter a valid amount.';
            return;
        }
        if ($amountCents > $invoice->amount_due) {
            $this->checkoutError = 'Amount exceeds balance due of $' . number_format($invoice->amount_due / 100, 2) . '.';
            return;
        }

        $user = auth()->guard('tenant')->user();

        try {
            app(BillingService::class)->collectPayment(
                invoice:         $invoice,
                amountCents:     $amountCents,
                method:          $this->checkoutMethod,
                collectedBy:     $user->id,
                referenceNumber: $this->checkoutReference ?: null,
            );

            $invoice->refresh();
            $this->checkoutAmountDue = $invoice->amount_due;

            if ($invoice->amount_due === 0) {
                $entry = QueueEntry::on('tenant')
                    ->where('branch_id', $this->branchId)
                    ->find($this->checkoutEntryId);

                if ($entry) {
                    app(QueueService::class)->transition($entry, QueueEntry::STATUS_COMPLETED, $user->id, 'Checked out');
                }

                $this->checkoutPaid = true;
                $this->loadQueue();
            } else {
                $this->checkoutAmountInput = number_format($invoice->amount_due / 100, 2);
                $this->checkoutError = 'Partial payment recorded. Balance: $' . number_format($invoice->amount_due / 100, 2);
            }
        } catch (\Throwable $e) {
            $this->checkoutError = $e->getMessage();
        }
    }

    public function closeCheckout(): void
    {
        $this->showCheckout    = false;
        $this->checkoutEntryId = null;
        $this->checkoutPaid    = false;
        $this->checkoutError   = '';
    }

    public function completeCheckout(int $entryId): void
    {
        $this->checkPermission('queue.checkin');

        $entry = QueueEntry::on('tenant')
            ->forBranchToday($this->branchId)
            ->where('status', QueueEntry::STATUS_AWAITING_CHECKOUT)
            ->find($entryId);

        if (!$entry) {
            return;
        }

        $userId = auth()->guard('tenant')->id();

        app(QueueService::class)->transition($entry, QueueEntry::STATUS_COMPLETED, $userId, 'Checked out by receptionist');

        app(AuditService::class)->logTenant(
            'queue.checkout',
            'Patient',
            $entry->patient_id,
            null,
            null,
            "Queue #{$entry->queue_number} checked out"
        );

        $this->loadQueue();
    }

    public function addDocument(): void
    {
        $this->docUploadError = '';

        if (!$this->docFile) {
            $this->docUploadError = 'Please choose a file first.';
            return;
        }

        try {
            $userId  = auth()->guard('tenant')->id();
            $title   = trim($this->docTitle) ?: $this->docFile->getClientOriginalName();

            app(DocumentService::class)->upload(
                file:        $this->docFile,
                patientId:   $this->uploadPatientId,
                uploadedBy:  $userId,
                encounterId: $this->uploadEncounterId,
                title:       $title,
                description: 'Uploaded at reception during check-in',
            );

            $this->uploadedDocNames[] = $title;
            $this->docFile            = null;
            $this->docTitle           = '';
        } catch (\InvalidArgumentException $e) {
            $this->docUploadError = $e->getMessage();
        }
    }

    public function doneUploading(): void
    {
        $this->showDocumentUpload = false;
        $this->uploadPatientId    = null;
        $this->uploadEncounterId  = null;
        $this->uploadPatientName  = '';
        $this->uploadedDocNames   = [];
        $this->docFile            = null;
        $this->docTitle           = '';
        $this->docUploadError     = '';
    }

    public function cancelCheckin(): void
    {
        $this->resetCheckinForm();
    }

    private function resetCheckinForm(): void
    {
        $this->selectedPatientId           = 0;
        $this->selectedPatientName         = '';
        $this->selectedPatientNumber       = '';
        $this->selectedPatientAllergyCount = 0;
        $this->selectedPatientBloodType    = '';
        $this->selectedDoctorId            = 0;
        $this->visitType                   = 'walk_in';
        $this->chiefComplaint              = '';
        $this->isPriority                  = false;
        $this->priorityReason              = '';
        $this->showCheckinForm             = false;
        $this->showQueueWizard             = false;
        $this->showQrPanel                 = false;
        $this->qrSvg                       = '';
        $this->qrPin                       = '';
    }

    #[On('queue-updated')]
    public function onQueueUpdated(): void
    {
        $this->loadQueue();
    }

    public function loadQueue(): void
    {
        $this->queueEntries = QueueEntry::on('tenant')
            ->forBranchToday($this->branchId)
            ->active()
            ->with(['patient', 'doctor.staffProfile'])
            ->orderBy('is_priority', 'desc')
            ->orderBy('queue_number')
            ->get()
            ->map(fn ($e) => [
                'id'          => $e->id,
                'number'      => $e->queue_number,
                'patientName' => $e->patient?->display_name ?? '',
                'doctorName'  => $e->doctor?->staffProfile?->full_name ?? 'Unassigned',
                'doctorId'    => $e->doctor_id,
                'status'      => $e->status,
                'statusLabel' => $e->status_label,
                'isPriority'  => $e->is_priority,
                'visitType'   => $e->visit_type,
            ])
            ->toArray();
    }

    public function loadDoctorStatuses(): void
    {
        $this->doctorStatuses = Doctor::on('tenant')
            ->with(['staffProfile', 'staffProfile.branch'])
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId)->where('is_active', true))
            ->get()
            ->map(function ($doc) {
                $avail = DoctorAvailability::on('tenant')
                    ->where('doctor_id', $doc->id)
                    ->where('branch_id', $this->branchId)
                    ->first();

                return [
                    'id'          => $doc->id,
                    'name'        => $doc->staffProfile?->full_name ?? '',
                    'specialty'   => $doc->specialty,
                    'status'      => $avail?->status ?? 'available',
                    'statusLabel' => $avail?->status_label ?? 'Available',
                    'statusNote'  => $avail?->status_note ?? '',
                    'queueCount'  => QueueEntry::on('tenant')
                        ->where('doctor_id', $doc->id)
                        ->forBranchToday($this->branchId)
                        ->active()
                        ->count(),
                ];
            })
            ->toArray();
    }

    public function switchBranch(int $id): void
    {
        $branch = Branch::on('tenant')->find($id);
        if ($branch) {
            $this->branchId = $id;
            $this->loadQueue();
            $this->loadDoctorStatuses();
        }
    }

    private function checkPermission(string $perm): void
    {
        $user = auth()->guard('tenant')->user();
        if (!$user?->hasPermission($perm)) {
            abort(403);
        }
    }

    public function render()
    {
        $branches      = Branch::on('tenant')->where('is_active', true)->get(['id', 'name']);
        $activeDoctors = Doctor::on('tenant')
            ->with('staffProfile')
            ->whereHas('staffProfile', fn ($q) => $q->where('branch_id', $this->branchId)->where('is_active', true))
            ->get();

        return view('livewire.tenant.reception.reception-dashboard', [
            'branches'      => $branches,
            'activeDoctors' => $activeDoctors,
        ])->layout('layouts.app-workspace');
    }
}
