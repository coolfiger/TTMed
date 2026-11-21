<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PatientDocument;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentDownloadController extends Controller
{
    public function __construct(private readonly DocumentService $documentService) {}

    public function __invoke(Request $request, int $id)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('documents.view')) {
            abort(403);
        }

        $document = PatientDocument::on('tenant')->findOrFail($id);

        return $this->documentService->streamDownload($document, $user->id);
    }

    public function view(Request $request, int $id)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('documents.view')) {
            abort(403);
        }

        $document = PatientDocument::on('tenant')->findOrFail($id);

        if (!$document->isViewable()) {
            abort(415);
        }

        return $this->documentService->streamInline($document, $user->id);
    }
}
