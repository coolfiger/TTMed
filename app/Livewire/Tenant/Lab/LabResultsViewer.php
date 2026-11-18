<?php

namespace App\Livewire\Tenant\Lab;

use App\Models\Tenant\LabOrder;
use App\Models\Tenant\LabResult;
use App\Services\LabOrderService;
use Livewire\Component;

class LabResultsViewer extends Component
{
    private LabOrderService $labOrderService;

    public int    $patientId     = 0;
    public string $filterStatus  = '';

    public string $successMessage = '';
    public string $errorMessage   = '';

    public function boot(LabOrderService $labOrderService): void
    {
        $this->labOrderService = $labOrderService;
    }

    public function mount(int $patientId): void
    {
        $this->checkPermission('lab.orders.create');
        $this->patientId = $patientId;
    }

    public function acknowledgeResults(int $orderId): void
    {
        $this->checkPermission('lab.results.acknowledge');

        $order = LabOrder::on('tenant')
            ->where('id', $orderId)
            ->where('patient_id', $this->patientId)
            ->firstOrFail();

        if (!$order->hasResults()) {
            $this->errorMessage = 'No results to acknowledge.';
            return;
        }

        $order->update([
            'status'      => LabOrder::STATUS_RESULTS_REVIEWED,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->guard('tenant')->id(),
        ]);

        $this->successMessage = "Order {$order->order_number} marked as reviewed.";
    }

    private function checkPermission(string $permission): void
    {
        if (!auth()->guard('tenant')->user()?->hasPermission($permission)) {
            abort(403);
        }
    }

    public function render()
    {
        $query = LabOrder::on('tenant')
            ->where('patient_id', $this->patientId)
            ->with(['items', 'results'])
            ->orderBy('ordered_at', 'desc');

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return view('livewire.tenant.lab.lab-results-viewer', [
            'orders' => $query->paginate(15),
        ]);
    }
}
