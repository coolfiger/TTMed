<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\LabResultAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LabAttachmentDownloadController extends Controller
{
    public function __invoke(int $id)
    {
        $user = Auth::guard('tenant')->user();
        if (!$user || !$user->hasPermission('lab.orders.create')) {
            abort(403);
        }

        $attachment = LabResultAttachment::on('tenant')->findOrFail($id);
        $path       = $attachment->storage_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return response()->stream(
            fn () => print(Storage::disk('local')->get($path)),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"',
                'Content-Length'      => $attachment->file_size ?? Storage::disk('local')->size($path),
                'Cache-Control'       => 'private, no-store',
            ]
        );
    }
}
