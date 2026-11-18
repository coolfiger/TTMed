<?php

namespace App\Livewire\Tenant\Billing;

use App\Models\Tenant\CashierShift;
use App\Services\BillingService;
use Livewire\Component;

class CashierShiftManager extends Component
{
    private BillingService $billingService;

    public ?CashierShift $activeShift = null;

    // Open shift form
    public int    $openingFloat    = 0;   // cents

    // Close shift form
    public int    $actualCash      = 0;   // cents
    public string $closeNotes      = '';

    public string $successMessage  = '';
    public string $errorMessage    = '';

    public function boot(BillingService $billingService): void
    {
        $this->billingService = $billingService;
    }

    public function mount(): void
    {
        $this->checkPermission('shifts.view');
        $this->loadActiveShift();
    }

    private function loadActiveShift(): void
    {
        $user = auth()->guard('tenant')->user();

        $this->activeShift = CashierShift::on('tenant')
            ->where('cashier_id', $user->id)
            ->where('branch_id', $user->branch_id ?? 0)
            ->where('status', 'open')
            ->first();
    }

    public function openShift(): void
    {
        $this->checkPermission('shifts.open');

        $this->validate(['openingFloat' => 'required|integer|min:0']);

        $user = auth()->guard('tenant')->user();

        try {
            $this->activeShift = $this->billingService->openShift(
                branchId:         $user->branch_id ?? 0,
                cashierId:        $user->id,
                openingFloatCents: $this->openingFloat,
            );
            $this->successMessage = 'Shift opened.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function closeShift(): void
    {
        $this->checkPermission('shifts.close');

        if (!$this->activeShift) {
            $this->errorMessage = 'No open shift found.';
            return;
        }

        $this->validate(['actualCash' => 'required|integer|min:0']);

        try {
            $this->billingService->closeShift(
                $this->activeShift,
                $this->actualCash,
                $this->closeNotes ?: null,
            );
            $this->activeShift = null;
            $this->successMessage = 'Shift closed successfully.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function approveShift(int $shiftId): void
    {
        $this->checkPermission('shifts.approve');

        $shift = CashierShift::on('tenant')
            ->where('id', $shiftId)
            ->where('branch_id', auth()->guard('tenant')->user()->branch_id ?? 0)
            ->firstOrFail();

        try {
            $this->billingService->approveShift($shift, auth()->guard('tenant')->id());
            $this->successMessage = 'Shift approved.';
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
        $user = auth()->guard('tenant')->user();

        $recentShifts = CashierShift::on('tenant')
            ->where('branch_id', $user->branch_id ?? 0)
            ->whereIn('status', ['closed', 'approved'])
            ->orderBy('closed_at', 'desc')
            ->limit(10)
            ->get();

        $pendingApproval = CashierShift::on('tenant')
            ->where('branch_id', $user->branch_id ?? 0)
            ->where('status', 'closed')
            ->get();

        $summary = $this->activeShift
            ? $this->billingService->getShiftSummary($this->activeShift)
            : null;

        return view('livewire.tenant.billing.cashier-shift-manager', [
            'recentShifts'    => $recentShifts,
            'pendingApproval' => $pendingApproval,
            'summary'         => $summary,
        ]);
    }
}
