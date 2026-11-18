<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingStudy extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'imaging_order_id', 'patient_id',
        'study_instance_uid', 'modality', 'study_description',
        'number_of_series', 'number_of_instances',
        'pacs_study_url', 'pacs_system', 'study_datetime',
    ];

    protected $casts = [
        'study_datetime' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ImagingOrder::class, 'imaging_order_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
