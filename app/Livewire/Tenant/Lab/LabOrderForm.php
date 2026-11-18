<?php

namespace App\Livewire\Tenant\Lab;

use App\Models\Tenant\LabOrder;
use App\Models\Tenant\LabOrderItem;
use App\Models\Tenant\LabResult;
use App\Models\Tenant\Patient;
use App\Services\LabOrderService;
use Livewire\Component;

class LabOrderForm extends Component
{
    private LabOrderService $labOrderService;

    public int    $encounterId  = 0;
    public int    $patientId    = 0;
    public string $orderType    = 'routine';
    public string $clinicalNotes = '';

    // Test/panel search
    public string $testSearch   = '';
    public array  $testResults  = [];

    // Selected items: [['id'=>, 'name'=>, 'code'=>, 'type'=>'test'|'panel']]
    public array  $selectedItems = [];

    // Order state
    public ?LabOrder $order      = null;

    // Result entry
    public ?int   $resultItemId  = null;
    public string $resultValue   = '';
    public string $resultFlag    = 'normal';
    public string $resultUnit    = '';
    public string $resultRange   = '';
    public bool   $resultCritical = false;

    public string $successMessage = '';
    public string $errorMessage   = '';

    public function boot(LabOrderService $labOrderService): void
    {
        $this->labOrderService = $labOrderService;
    }

    public function mount(int $encounterId, int $patientId, ?int $orderId = null): void
    {
        $this->checkPermission('lab.orders.create');
        $this->encounterId = $encounterId;
        $this->patientId   = $patientId;

        if ($orderId) {
            $this->order = LabOrder::on('tenant')->findOrFail($orderId);
        }
    }

    // -------------------------------------------------------------------------
    // Test search
    // -------------------------------------------------------------------------

    public function searchTests(): void
    {
        if (strlen($this->testSearch) < 2) {
            $this->testResults = [];
            return;
        }

        $tests = \Illuminate\Support\Facades\DB::connection('tenant')
            ->table('lab_tests')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->testSearch}%")
                  ->orWhere('code', 'like', "%{$this->testSearch}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'code', 'specimen_type']);

        $panels = \Illuminate\Support\Facades\DB::connection('tenant')
            ->table('lab_panels')
            ->where('is_active', true)
            ->where('name', 'like', "%{$this->testSearch}%")
            ->limit(5)
            ->get(['id', 'name', 'code']);

        $this->testResults = array_merge(
            $tests->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'code' => $t->code, 'type' => 'test', 'specimen' => $t->specimen_type])->toArray(),
            $panels->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code, 'type' => 'panel', 'specimen' => null])->toArray(),
        );
    }

    public function selectTest(int $id, string $type, string $name, string $code): void
    {
        foreach ($this->selectedItems as $item) {
            if ($item['id'] === $id && $item['type'] === $type) {
                return; // already added
            }
        }

        $this->selectedItems[] = compact('id', 'type', 'name', 'code');
        $this->testSearch   = '';
        $this->testResults  = [];
    }

    public function removeTest(int $index): void
    {
        array_splice($this->selectedItems, $index, 1);
    }

    // -------------------------------------------------------------------------
    // Order
    // -------------------------------------------------------------------------

    public function placeOrder(): void
    {
        $this->checkPermission('lab.orders.create');

        if (empty($this->selectedItems)) {
            $this->errorMessage = 'Add at least one test or panel before ordering.';
            return;
        }

        $patient = Patient::on('tenant')->findOrFail($this->patientId);
        $user    = auth()->guard('tenant')->user();

        try {
            $testIds  = collect($this->selectedItems)->where('type', 'test')->pluck('id')->toArray();
            $panelIds = collect($this->selectedItems)->where('type', 'panel')->pluck('id')->toArray();

            $this->order = $this->labOrderService->createOrder(
                patient:       $patient,
                orderedBy:     $user->id,
                testIds:       $testIds,
                panelIds:      $panelIds,
                encounterId:   $this->encounterId,
                branchId:      $user->branch_id,
                orderType:     $this->orderType,
                clinicalNotes: $this->clinicalNotes ?: null,
            );

            $this->selectedItems  = [];
            $this->clinicalNotes  = '';
            $this->successMessage = "Lab order {$this->order->order_number} placed.";
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function markCollected(): void
    {
        $this->checkPermission('lab.orders.create');

        try {
            $this->order = $this->labOrderService->markSpecimenCollected(
                $this->order,
                auth()->guard('tenant')->id(),
            );
            $this->successMessage = 'Specimen marked as collected.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    // -------------------------------------------------------------------------
    // Result entry
    // -------------------------------------------------------------------------

    public function openResultEntry(int $itemId): void
    {
        $this->checkPermission('lab.results.enter');
        $this->resultItemId   = $itemId;
        $this->resultValue    = '';
        $this->resultFlag     = 'normal';
        $this->resultUnit     = '';
        $this->resultRange    = '';
        $this->resultCritical = false;
    }

    public function saveResult(): void
    {
        $this->checkPermission('lab.results.enter');

        $this->validate([
            'resultValue' => 'required|string|max:500',
            'resultFlag'  => 'required|in:normal,low,high,critical_low,critical_high,abnormal',
        ]);

        try {
            $this->labOrderService->storeManualResult(
                order:          $this->order,
                orderItemId:    $this->resultItemId,
                value:          $this->resultValue,
                flag:           $this->resultFlag,
                unit:           $this->resultUnit ?: null,
                referenceRange: $this->resultRange ?: null,
                isCritical:     $this->resultCritical,
            );

            $this->order->refresh();
            $this->resultItemId   = null;
            $this->successMessage = 'Result saved.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function checkPermission(string $permission): void
    {
        if (!auth()->guard('tenant')->user()?->hasPermission($permission)) {
            abort(403);
        }
    }

    public function render()
    {
        $items   = $this->order ? LabOrderItem::on('tenant')->where('lab_order_id', $this->order->id)->get() : collect();
        $results = $this->order ? LabResult::on('tenant')->where('lab_order_id', $this->order->id)->get() : collect();
        $patient = Patient::on('tenant')->find($this->patientId);

        return view('livewire.tenant.lab.lab-order-form', [
            'items'   => $items,
            'results' => $results,
            'patient' => $patient,
        ]);
    }
}
