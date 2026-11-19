<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentInboundLog extends Model
{
    protected $connection = 'tenant';
    protected $table = 'document_inbound_log';

    protected $fillable = [
        'type', 'source', 'reference_type', 'reference_id',
        'patient_id', 'branch_id', 'description', 'status',
        'is_critical', 'critical_note',
        'reviewed_by', 'reviewed_at', 'notes',
    ];

    protected $casts = [
        'is_critical'  => 'boolean',
        'reviewed_at'  => 'datetime',
    ];

    const TYPES = [
        'imaging_report'   => 'Imaging Report',
        'lab_result'       => 'Lab Result',
        'patient_document' => 'Patient Document',
        'manual_upload'    => 'Manual Upload',
    ];

    const SOURCES = [
        'hl7_v2'           => 'HL7 v2',
        'vepro'            => 'VEPRO',
        'fhir_r4'          => 'FHIR R4',
        'manual'           => 'Manual',
        'patient_provided' => 'Patient-Provided',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? ucfirst(str_replace('_', ' ', $this->source));
    }

    public function markReviewed(int $userId): void
    {
        $this->update([
            'status'      => 'reviewed',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);
    }
}
