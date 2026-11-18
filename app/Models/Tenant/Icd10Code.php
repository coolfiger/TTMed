<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icd10Code extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'code', 'description', 'category_code', 'category_description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Full-text search over code and description.
     */
    public function scopeSearch($query, string $term)
    {
        $term = '%' . $term . '%';
        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', $term)
              ->orWhere('description', 'like', $term);
        });
    }

    public function getDisplayAttribute(): string
    {
        return "{$this->code} — {$this->description}";
    }
}
