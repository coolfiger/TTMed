<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantDatabaseManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateDemoPdfsCommand extends Command
{
    protected $signature   = 'tenant:generate-demo-pdfs {code : Tenant code (e.g. shiva)}';
    protected $description = 'Generate placeholder lab report and imaging report PDFs for demo data';

    public function handle(TenantDatabaseManager $dbManager, TenantContext $ctx): int
    {
        $code   = $this->argument('code');
        $tenant = Tenant::on('platform')->where('code', $code)->first();

        if (! $tenant) {
            $this->error("Tenant '{$code}' not found.");
            return self::FAILURE;
        }

        $dbManager->connect($tenant);
        $ctx->set($tenant);

        $disk    = 'local';
        $basePath = 'tenants/' . $tenant->uuid . '/reports';

        Storage::disk($disk)->makeDirectory($basePath . '/lab');
        Storage::disk($disk)->makeDirectory($basePath . '/imaging');

        $this->generateLabReports($basePath . '/lab', $disk);
        $this->generateImagingReports($basePath . '/imaging', $disk);

        $this->info('Done.');
        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function generateLabReports(string $dir, string $disk): void
    {
        $orders = DB::connection('tenant')
            ->table('lab_orders as lo')
            ->join('patients as p', 'p.id', '=', 'lo.patient_id')
            ->join('users as u', 'u.id', '=', 'lo.ordered_by')
            ->leftJoin('branches as b', 'b.id', '=', 'lo.branch_id')
            ->select(
                'lo.id as order_id',
                'lo.order_number',
                'lo.created_at as ordered_at',
                'p.id as patient_id',
                'p.first_name', 'p.last_name', 'p.date_of_birth', 'p.gender',
                'p.patient_number',
                'u.name as doctor_name',
                'b.name as branch_name', 'b.phone as branch_phone', 'b.address as branch_address',
            )
            ->get();

        $bar = $this->output->createProgressBar($orders->count());
        $bar->setFormat(' %current%/%max% [%bar%] lab reports');

        foreach ($orders as $order) {
            $results = DB::connection('tenant')
                ->table('lab_results as lr')
                ->join('lab_order_items as li', 'li.id', '=', 'lr.lab_order_item_id')
                ->where('li.lab_order_id', $order->order_id)
                ->select('li.test_name', 'lr.value', 'lr.value_unit as unit', 'lr.reference_range', 'lr.flag', 'lr.interpretation_notes as notes')
                ->get();

            $html = view('pdf.lab-report', [
                'order'      => $order,
                'results'    => $results,
                'printedAt'  => now()->format('d M Y H:i'),
            ])->render();

            $pdf      = Pdf::loadHTML($html)->setPaper('a4');
            $fileName = 'LAB-' . $order->order_number . '.pdf';
            $path     = $dir . '/' . $fileName;

            Storage::disk($disk)->put($path, $pdf->output());

            // Insert lab_result_attachments row
            $existing = DB::connection('tenant')
                ->table('lab_result_attachments')
                ->where('lab_order_id', $order->order_id)
                ->where('file_name', $fileName)
                ->exists();

            if (! $existing) {
                DB::connection('tenant')->table('lab_result_attachments')->insert([
                    'lab_order_id' => $order->order_id,
                    'patient_id'   => $order->patient_id,
                    'file_name'    => $fileName,
                    'storage_path' => $path,
                    'mime_type'    => 'application/pdf',
                    'file_size'    => Storage::disk($disk)->size($path),
                    'uploaded_by'  => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  → {$orders->count()} lab report PDFs generated.");
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function generateImagingReports(string $dir, string $disk): void
    {
        $reports = DB::connection('tenant')
            ->table('imaging_reports as ir')
            ->join('imaging_orders as io', 'io.id', '=', 'ir.imaging_order_id')
            ->join('patients as p', 'p.id', '=', 'ir.patient_id')
            ->leftJoin('branches as b', 'b.id', '=', 'io.branch_id')
            ->select(
                'ir.id as report_id',
                'ir.imaging_order_id',
                'ir.modality', 'ir.body_part', 'ir.description',
                'ir.findings', 'ir.impression', 'ir.recommendations',
                'ir.radiologist_name',
                'ir.created_at as reported_at',
                'io.order_number',
                'p.first_name', 'p.last_name', 'p.date_of_birth', 'p.gender', 'p.patient_number',
                'b.name as branch_name', 'b.address as branch_address', 'b.phone as branch_phone',
            )
            ->get();

        $bar = $this->output->createProgressBar($reports->count());
        $bar->setFormat(' %current%/%max% [%bar%] imaging reports');

        foreach ($reports as $report) {
            $html = view('pdf.imaging-report', [
                'report'    => $report,
                'printedAt' => now()->format('d M Y H:i'),
            ])->render();

            $pdf      = Pdf::loadHTML($html)->setPaper('a4');
            $fileName = 'IMG-' . $report->order_number . '.pdf';
            $path     = $dir . '/' . $fileName;

            Storage::disk($disk)->put($path, $pdf->output());

            DB::connection('tenant')
                ->table('imaging_reports')
                ->where('id', $report->report_id)
                ->update([
                    'report_pdf_path' => $path,
                    'report_pdf_size' => Storage::disk($disk)->size($path),
                    'updated_at'      => now(),
                ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  → {$reports->count()} imaging report PDFs generated.");
    }
}
