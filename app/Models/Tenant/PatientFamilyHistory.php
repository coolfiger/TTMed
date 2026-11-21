<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFamilyHistory extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'patient_id', 'relationship', 'condition', 'notes', 'recorded_by',
    ];

    const RELATIONSHIPS = [
        'mother'                => 'Mother',
        'father'                => 'Father',
        'maternal_grandmother'  => 'Maternal Grandmother',
        'maternal_grandfather'  => 'Maternal Grandfather',
        'paternal_grandmother'  => 'Paternal Grandmother',
        'paternal_grandfather'  => 'Paternal Grandfather',
        'sibling'               => 'Sibling',
        'other'                 => 'Other',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getRelationshipLabelAttribute(): string
    {
        return self::RELATIONSHIPS[$this->relationship] ?? ucfirst($this->relationship);
    }
}
