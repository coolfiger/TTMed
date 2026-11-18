<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NursingProcedure extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'encounter_id', 'patient_id', 'performed_by',
        'procedure_type', 'procedure_name',
        'details', 'outcome', 'site', 'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    const TYPES = [
        'injection'    => 'Injection',
        'dressing'     => 'Dressing / Wound Care',
        'ecg'          => 'ECG',
        'nebulizer'    => 'Nebulizer',
        'catheter'     => 'Catheterization',
        'iv_line'      => 'IV Line / Cannula',
        'suture'       => 'Suture',
        'blood_draw'   => 'Blood Draw',
        'urinalysis'   => 'Urinalysis',
        'enema'        => 'Enema',
        'oxygen'       => 'Oxygen Therapy',
        'other'        => 'Other',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->procedure_type] ?? ucfirst($this->procedure_type);
    }
}
