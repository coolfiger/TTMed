<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImagingOrder extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'order_number', 'patient_id', 'encounter_id', 'ordered_by', 'branch_id',
        'modality', 'body_part', 'laterality', 'clinical_indication', 'instructions', 'priority',
        'status', 'accession_number', 'ris_order_id', 'integration_type', 'hl7_placer_order_number',
        'ordered_at', 'scheduled_at', 'performed_at',
        'report_received_at', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'ordered_at'          => 'datetime',
        'scheduled_at'        => 'datetime',
        'performed_at'        => 'datetime',
        'report_received_at'  => 'datetime',
        'reviewed_at'         => 'datetime',
    ];

    const MODALITIES = [
        'CR'  => 'X-Ray (CR)',
        'DX'  => 'X-Ray (Digital)',
        'CT'  => 'CT Scan',
        'MR'  => 'MRI',
        'US'  => 'Ultrasound',
        'XA'  => 'Fluoroscopy / Angiography',
        'NM'  => 'Nuclear Medicine',
        'PT'  => 'PET Scan',
        'MG'  => 'Mammography',
        'OT'  => 'Other',
    ];

    public function getModalityLabelAttribute(): string
    {
        return self::MODALITIES[$this->modality] ?? $this->modality;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'           => 'Pending',
            'sent_to_ris'       => 'Sent to RIS',
            'scheduled'         => 'Scheduled',
            'in_progress'       => 'In Progress',
            'completed'         => 'Completed',
            'report_available'  => 'Report Available',
            'report_reviewed'   => 'Reviewed',
            'cancelled'         => 'Cancelled',
            default             => ucfirst($this->status),
        };
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function study(): HasOne
    {
        return $this->hasOne(ImagingStudy::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(ImagingReport::class);
    }
}
