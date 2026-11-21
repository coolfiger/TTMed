<?php

namespace App\Services;

use App\Models\Tenant\DocumentAccessLog;
use App\Models\Tenant\DocumentVersion;
use App\Models\Tenant\PatientDocument;
use App\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    private const MAX_FILE_SIZE_BYTES = 20 * 1024 * 1024; // 20 MB

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditService  $auditService,
    ) {}

    public function upload(
        UploadedFile $file,
        int          $patientId,
        int          $uploadedBy,
        ?int         $encounterId    = null,
        ?int         $categoryId     = null,
        string       $title          = '',
        ?string      $description    = null,
    ): PatientDocument {
        $this->validateFile($file);

        [$storagePath, $mimeType, $sizeBytes] = $this->storeFile($file, 'documents');

        return PatientDocument::on('tenant')->create([
            'patient_id'           => $patientId,
            'encounter_id'         => $encounterId,
            'uploaded_by'          => $uploadedBy,
            'document_category_id' => $categoryId,
            'title'                => $title ?: $file->getClientOriginalName(),
            'description'          => $description,
            'original_filename'    => $file->getClientOriginalName(),
            'storage_path'         => $storagePath,
            'mime_type'            => $mimeType,
            'file_size_bytes'      => $sizeBytes,
            'version'              => 1,
        ]);
    }

    public function addVersion(
        PatientDocument $document,
        UploadedFile    $file,
        int             $uploadedBy,
        ?string         $notes = null,
    ): DocumentVersion {
        $this->validateFile($file);

        [$storagePath, , $sizeBytes] = $this->storeFile($file, 'documents');

        $newVersion = $document->version + 1;

        // Archive the current version first
        DocumentVersion::on('tenant')->create([
            'patient_document_id' => $document->id,
            'version_number'      => $document->version,
            'uploaded_by'         => $document->uploaded_by,
            'storage_path'        => $document->storage_path,
            'original_filename'   => $document->original_filename,
            'file_size_bytes'     => $document->file_size_bytes,
            'notes'               => 'Archived when version ' . $newVersion . ' was uploaded',
        ]);

        $document->update([
            'storage_path'      => $storagePath,
            'original_filename' => $file->getClientOriginalName(),
            'file_size_bytes'   => $sizeBytes,
            'mime_type'         => $file->getMimeType(),
            'version'           => $newVersion,
            'uploaded_by'       => $uploadedBy,
        ]);

        return DocumentVersion::on('tenant')->create([
            'patient_document_id' => $document->id,
            'version_number'      => $newVersion,
            'uploaded_by'         => $uploadedBy,
            'storage_path'        => $storagePath,
            'original_filename'   => $file->getClientOriginalName(),
            'file_size_bytes'     => $sizeBytes,
            'notes'               => $notes,
        ]);
    }

    public function streamDownload(PatientDocument $document, int $userId): StreamedResponse
    {
        if (!file_exists($document->storage_path)) {
            throw new \RuntimeException("Document file not found on storage.");
        }

        $this->logAccess($document, $userId, 'download');

        $filename = $document->original_filename;
        $mime     = $document->mime_type;

        return response()->streamDownload(function () use ($document) {
            $handle = fopen($document->storage_path, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }, $filename, ['Content-Type' => $mime]);
    }

    public function logAccess(PatientDocument $document, int $userId, string $type = 'view'): void
    {
        DocumentAccessLog::on('tenant')->create([
            'patient_document_id' => $document->id,
            'accessed_by'         => $userId,
            'access_type'         => $type,
            'ip_address'          => Request::ip(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private
    // -------------------------------------------------------------------------

    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw new \InvalidArgumentException('File exceeds the 20 MB limit.');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException("File type '{$file->getMimeType()}' is not allowed.");
        }
    }

    private function storeFile(UploadedFile $file, string $subdir): array
    {
        $dir = $this->tenantContext->storagePath($subdir);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Could not create storage directory: {$dir}");
        }

        $mimeType = $file->getMimeType() ?? $file->getClientMimeType();
        $filename = uniqid('doc_', true) . '.' . $file->getClientOriginalExtension();
        $path     = $dir . '/' . $filename;

        $file->move($dir, $filename);

        return [$path, $mimeType ?? mime_content_type($path), filesize($path)];
    }
}
