<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name', 'generic_name', 'brand_names', 'strength', 'strength_unit',
        'form', 'route', 'drug_class', 'description', 'is_active', 'is_custom',
    ];

    protected $casts = [
        'brand_names' => 'array',
        'is_active'   => 'boolean',
        'is_custom'   => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        $like = '%' . $term . '%';
        return $query->where(fn ($q) =>
            $q->where('name', 'like', $like)
              ->orWhere('generic_name', 'like', $like)
        );
    }

    public function getDisplayAttribute(): string
    {
        $parts = [$this->name];
        if ($this->strength) {
            $parts[] = $this->strength;
        }
        if ($this->form) {
            $parts[] = ucfirst($this->form);
        }
        return implode(' — ', $parts);
    }
}
