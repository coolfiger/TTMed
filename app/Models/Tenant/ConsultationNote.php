<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationNote extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'encounter_id', 'patient_id', 'doctor_id',
        'subjective', 'objective', 'assessment', 'plan',
        'examination_findings', 'investigations_ordered',
        'follow_up_instructions', 'follow_up_days',
        'is_finalized', 'finalized_by', 'finalized_at', 'content_hash',
    ];

    protected $attributes = [
        'is_finalized' => false,
    ];

    protected $casts = [
        'is_finalized'  => 'boolean',
        'finalized_at'  => 'datetime',
        'follow_up_days' => 'integer',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isFinalized(): bool
    {
        return $this->is_finalized;
    }

    /**
     * Compute a deterministic hash of the clinical content.
     * Stored at finalisation so silent post-hoc edits are detectable.
     */
    public function computeContentHash(): string
    {
        $content = implode('|', [
            $this->encounter_id,
            $this->subjective ?? '',
            $this->objective ?? '',
            $this->assessment ?? '',
            $this->plan ?? '',
            $this->examination_findings ?? '',
        ]);

        return hash('sha256', $content);
    }

    /**
     * True if any SOAP field has been populated.
     */
    public function hasContent(): bool
    {
        return filled($this->subjective)
            || filled($this->objective)
            || filled($this->assessment)
            || filled($this->plan);
    }
}
