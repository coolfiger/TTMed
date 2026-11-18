<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $connection = 'tenant';

    const STATUS_DRAFT     = 'draft';
    const STATUS_ISSUED    = 'issued';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'prescription_number', 'encounter_id', 'patient_id', 'doctor_id', 'branch_id',
        'status', 'allergy_checked', 'allergy_check_notes', 'pdf_path',
        'issued_by', 'issued_at', 'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'allergy_checked' => 'boolean',
        'issued_at'       => 'datetime',
        'cancelled_at'    => 'datetime',
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

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('sort_order');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(PrescriptionDeliveryLog::class);
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function hasItems(): bool
    {
        return $this->items()->exists();
    }
}
