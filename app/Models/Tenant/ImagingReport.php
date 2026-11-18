<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingReport extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'imaging_order_id', 'patient_id',
        'radiologist_name', 'report_status',
        'findings', 'impression', 'recommendations',
        'report_pdf_path', 'report_pdf_size',
        'source', 'raw_message',
        'is_critical', 'critical_finding',
        'report_datetime', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'is_critical'    => 'boolean',
        'report_datetime' => 'datetime',
        'reviewed_at'    => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ImagingOrder::class, 'imaging_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isFinal(): bool
    {
        return $this->report_status === 'final';
    }
}
