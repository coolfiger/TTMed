<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_document_id', 'version_number', 'uploaded_by',
        'storage_path', 'original_filename', 'file_size_bytes', 'notes',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'version_number'  => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(PatientDocument::class, 'patient_document_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
