<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // =========================================================================
    // Financial Summary
    // =========================================================================

    /**
     * Revenue collected, invoice counts, and payment method breakdown for a period.
     *
     * @return array{
     *   total_invoiced: int,
     *   total_collected: int,
     *   total_outstanding: int,
     *   invoice_count: int,
     *   paid_count: int,
     *   partial_count: int,
     *   cancelled_count: int,
     *   by_method: Collection,
     *   daily: Collection
     * }
     */
    public function financialSummary(Carbon $from, Carbon $to, ?int $branchId = null): array
    {
        $invoiceQ = DB::connection('tenant')
            ->table('invoices')
            ->whereBetween('created_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->whereNotIn('status', ['void']);

        if ($branchId) {
            $invoiceQ->where('branch_id', $branchId);
        }

        $invoices = $invoiceQ->selectRaw('
            SUM(total)        AS total_invoiced,
            SUM(amount_paid)  AS total_collected,
            SUM(amount_due)   AS total_outstanding,
            COUNT(*)          AS invoice_count,
            SUM(CASE WHEN status = "paid"          THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN status = "partially_paid" THEN 1 ELSE 0 END) AS partial_count,
            SUM(CASE WHEN status = "cancelled"      THEN 1 ELSE 0 END) AS cancelled_count
        ')->first();

        $paymentQ = DB::connection('tenant')
            ->table('payments')
            ->where('voided', false)
            ->whereBetween('created_at', [$from->startOfDay(), $to->copy()->endOfDay()]);

        if ($branchId) {
            $paymentQ->where('branch_id', $branchId);
        }

        $byMethod = $paymentQ->selectRaw('payment_method, SUM(amount) AS total, COUNT(*) AS count')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $daily = DB::connection('tenant')
            ->table('payments')
            ->where('voided', false)
            ->whereBetween('created_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('DATE(created_at) AS day, SUM(amount) AS total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'total_invoiced'   => (int) ($invoices->total_invoiced ?? 0),
            'total_collected'  => (int) ($invoices->total_collected ?? 0),
            'total_outstanding'=> (int) ($invoices->total_outstanding ?? 0),
            'invoice_count'    => (int) ($invoices->invoice_count ?? 0),
            'paid_count'       => (int) ($invoices->paid_count ?? 0),
            'partial_count'    => (int) ($invoices->partial_count ?? 0),
            'cancelled_count'  => (int) ($invoices->cancelled_count ?? 0),
            'by_method'        => $byMethod,
            'daily'            => $daily,
        ];
    }

    // =========================================================================
    // Invoice Aging
    // =========================================================================

    /**
     * Outstanding invoices grouped by age buckets (0-30, 31-60, 61-90, 90+).
     *
     * @return array{buckets: array, rows: Collection}
     */
    public function invoiceAging(?int $branchId = null): array
    {
        $today = now()->toDateString();

        $q = DB::connection('tenant')
            ->table('invoices')
            ->join('patients', 'invoices.patient_id', '=', 'patients.id')
            ->whereIn('invoices.status', ['finalized', 'partially_paid'])
            ->where('invoices.amount_due', '>', 0)
            ->when($branchId, fn ($q) => $q->where('invoices.branch_id', $branchId))
            ->selectRaw("
                invoices.id,
                invoices.invoice_number,
                invoices.created_at,
                invoices.amount_due,
                invoices.status,
                patients.first_name,
                patients.last_name,
                CAST(julianday('{$today}') - julianday(DATE(invoices.created_at)) AS INTEGER) AS age_days
            ")
            ->orderByDesc('age_days')
            ->get();

        $buckets = [
            '0-30'  => ['count' => 0, 'total' => 0],
            '31-60' => ['count' => 0, 'total' => 0],
            '61-90' => ['count' => 0, 'total' => 0],
            '90+'   => ['count' => 0, 'total' => 0],
        ];

        foreach ($q as $row) {
            $key = match (true) {
                $row->age_days <= 30 => '0-30',
                $row->age_days <= 60 => '31-60',
                $row->age_days <= 90 => '61-90',
                default              => '90+',
            };
            $buckets[$key]['count']++;
            $buckets[$key]['total'] += $row->amount_due;
        }

        return ['buckets' => $buckets, 'rows' => $q];
    }

    // =========================================================================
    // Appointment Volume
    // =========================================================================

    /**
     * Appointment counts by status, by doctor, and daily trend for a period.
     *
     * @return array{
     *   total: int,
     *   completed: int,
     *   no_show: int,
     *   cancelled: int,
     *   no_show_rate: float,
     *   by_doctor: Collection,
     *   daily: Collection
     * }
     */
    public function appointmentVolume(Carbon $from, Carbon $to, ?int $branchId = null): array
    {
        $q = DB::connection('tenant')
            ->table('appointments')
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $summary = (clone $q)->selectRaw('
            COUNT(*) AS total,
            SUM(CASE WHEN status = "completed"  THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = "no_show"    THEN 1 ELSE 0 END) AS no_show,
            SUM(CASE WHEN status = "cancelled"  THEN 1 ELSE 0 END) AS cancelled
        ')->first();

        $total   = (int) ($summary->total ?? 0);
        $noShow  = (int) ($summary->no_show ?? 0);

        $byDoctor = (clone $q)
            ->join('staff', 'appointments.doctor_id', '=', 'staff.id')
            ->selectRaw('
                staff.first_name,
                staff.last_name,
                COUNT(*) AS total,
                SUM(CASE WHEN appointments.status = "completed" THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN appointments.status = "no_show"   THEN 1 ELSE 0 END) AS no_show
            ')
            ->groupBy('staff.id', 'staff.first_name', 'staff.last_name')
            ->orderByDesc('total')
            ->get();

        $daily = (clone $q)
            ->selectRaw('appointment_date AS day, COUNT(*) AS total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = "no_show"   THEN 1 ELSE 0 END) AS no_show')
            ->groupBy('appointment_date')
            ->orderBy('appointment_date')
            ->get();

        return [
            'total'        => $total,
            'completed'    => (int) ($summary->completed ?? 0),
            'no_show'      => $noShow,
            'cancelled'    => (int) ($summary->cancelled ?? 0),
            'no_show_rate' => $total > 0 ? round($noShow / $total * 100, 1) : 0.0,
            'by_doctor'    => $byDoctor,
            'daily'        => $daily,
        ];
    }

    // =========================================================================
    // Inventory Status
    // =========================================================================

    /**
     * Current stock snapshot: low stock, out of stock, stock value, and top movers.
     *
     * @return array{
     *   total_items: int,
     *   low_stock_count: int,
     *   out_of_stock_count: int,
     *   stock_value_cents: int,
     *   low_stock_items: Collection,
     *   top_dispensed: Collection
     * }
     */
    public function inventoryStatus(Carbon $from, Carbon $to, ?int $branchId = null): array
    {
        $summary = DB::connection('tenant')
            ->table('inventory_items')
            ->where('is_active', true)
            ->selectRaw('
                COUNT(*) AS total_items,
                SUM(CASE WHEN quantity_on_hand <= reorder_level THEN 1 ELSE 0 END) AS low_stock_count,
                SUM(CASE WHEN quantity_on_hand = 0              THEN 1 ELSE 0 END) AS out_of_stock_count,
                SUM(quantity_on_hand * unit_cost)                                   AS stock_value_cents
            ')
            ->first();

        $lowStock = DB::connection('tenant')
            ->table('inventory_items')
            ->leftJoin('inventory_categories', 'inventory_items.category_id', '=', 'inventory_categories.id')
            ->where('inventory_items.is_active', true)
            ->whereRaw('inventory_items.quantity_on_hand <= inventory_items.reorder_level')
            ->select(
                'inventory_items.id',
                'inventory_items.name',
                'inventory_items.code',
                'inventory_items.unit',
                'inventory_items.quantity_on_hand',
                'inventory_items.reorder_level',
                'inventory_items.reorder_quantity',
                'inventory_categories.name AS category_name'
            )
            ->orderBy('inventory_items.quantity_on_hand')
            ->get();

        $topDispensed = DB::connection('tenant')
            ->table('inventory_transactions')
            ->join('inventory_items', 'inventory_transactions.inventory_item_id', '=', 'inventory_items.id')
            ->where('inventory_transactions.transaction_type', 'dispense')
            ->whereBetween('inventory_transactions.created_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->when($branchId, fn ($q) => $q->where('inventory_transactions.branch_id', $branchId))
            ->selectRaw('
                inventory_items.name,
                inventory_items.code,
                inventory_items.unit,
                SUM(ABS(inventory_transactions.quantity)) AS total_dispensed
            ')
            ->groupBy('inventory_items.id', 'inventory_items.name', 'inventory_items.code', 'inventory_items.unit')
            ->orderByDesc('total_dispensed')
            ->limit(10)
            ->get();

        return [
            'total_items'        => (int) ($summary->total_items ?? 0),
            'low_stock_count'    => (int) ($summary->low_stock_count ?? 0),
            'out_of_stock_count' => (int) ($summary->out_of_stock_count ?? 0),
            'stock_value_cents'  => (int) ($summary->stock_value_cents ?? 0),
            'low_stock_items'    => $lowStock,
            'top_dispensed'      => $topDispensed,
        ];
    }

    // =========================================================================
    // Patient Activity
    // =========================================================================

    /**
     * New registrations and encounter volume for a period.
     *
     * @return array{
     *   new_patients: int,
     *   total_encounters: int,
     *   encounter_by_type: Collection,
     *   daily_registrations: Collection
     * }
     */
    public function patientActivity(Carbon $from, Carbon $to, ?int $branchId = null): array
    {
        $newPatients = DB::connection('tenant')
            ->table('patients')
            ->whereBetween('created_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->whereNull('deleted_at')
            ->count();

        $dailyReg = DB::connection('tenant')
            ->table('patients')
            ->whereBetween('created_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->whereNull('deleted_at')
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $encounterQ = DB::connection('tenant')
            ->table('encounters')
            ->whereBetween('encounter_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $totalEncounters = (clone $encounterQ)->count();

        $byType = (clone $encounterQ)
            ->selectRaw('encounter_type, COUNT(*) AS count')
            ->groupBy('encounter_type')
            ->orderByDesc('count')
            ->get();

        return [
            'new_patients'       => $newPatients,
            'total_encounters'   => $totalEncounters,
            'encounter_by_type'  => $byType,
            'daily_registrations'=> $dailyReg,
        ];
    }

    // =========================================================================
    // CSV export helpers
    // =========================================================================

    public function financialSummaryCsv(array $data): string
    {
        $rows   = [['Payment Method', 'Count', 'Amount (TTD)']];
        foreach ($data['by_method'] as $m) {
            $rows[] = [$m->payment_method, $m->count, number_format($m->total / 100, 2)];
        }
        return $this->toCsv($rows);
    }

    public function invoiceAgingCsv(Collection $rows): string
    {
        $out = [['Invoice #', 'Patient', 'Date', 'Days Outstanding', 'Amount Due (TTD)', 'Status']];
        foreach ($rows as $r) {
            $out[] = [
                $r->invoice_number,
                "{$r->first_name} {$r->last_name}",
                Carbon::parse($r->created_at)->toDateString(),
                $r->age_days,
                number_format($r->amount_due / 100, 2),
                $r->status,
            ];
        }
        return $this->toCsv($out);
    }

    public function appointmentVolumeCsv(array $data): string
    {
        $rows = [['Doctor', 'Total', 'Completed', 'No-Show']];
        foreach ($data['by_doctor'] as $d) {
            $rows[] = ["{$d->first_name} {$d->last_name}", $d->total, $d->completed, $d->no_show];
        }
        return $this->toCsv($rows);
    }

    public function inventoryStatusCsv(array $data): string
    {
        $rows = [['Code', 'Name', 'Category', 'On Hand', 'Reorder Level', 'Unit']];
        foreach ($data['low_stock_items'] as $i) {
            $rows[] = [
                $i->code,
                $i->name,
                $i->category_name ?? '—',
                $i->quantity_on_hand,
                $i->reorder_level,
                $i->unit,
            ];
        }
        return $this->toCsv($rows);
    }

    private function toCsv(array $rows): string
    {
        $buf = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($buf, $row);
        }
        rewind($buf);
        $csv = stream_get_contents($buf);
        fclose($buf);
        return $csv;
    }
}
