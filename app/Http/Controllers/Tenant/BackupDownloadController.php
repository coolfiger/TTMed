<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly BackupService $backupService,
    ) {}

    public function __invoke(Request $request, string $filename): StreamedResponse
    {
        $user = auth()->guard('tenant')->user();

        if (! $user || ! $user->hasPermission('settings.manage')) {
            abort(403);
        }

        try {
            $path = $this->backupService->resolveBackupPath($this->tenantContext->get(), $filename);
        } catch (\InvalidArgumentException) {
            abort(404);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response()->streamDownload(function () use ($path) {
            $handle = fopen($path, 'rb');
            while (! feof($handle)) {
                echo fread($handle, 8192);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'application/gzip',
            'Content-Length'      => filesize($path),
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Robots-Tag'        => 'noindex',
        ]);
    }
}
