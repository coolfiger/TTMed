<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplate extends Model
{
    protected $connection = 'tenant';

    const TYPES = [
        'prescription'    => 'Prescription',
        'invoice'         => 'Invoice',
        'receipt'         => 'Receipt',
        'lab_report'      => 'Lab Report',
        'imaging_report'  => 'Imaging Report',
        'certificate'     => 'Medical Certificate',
        'referral'        => 'Referral Letter',
        'generic'         => 'Generic Document',
    ];

    protected $fillable = [
        'name', 'type', 'branch_id', 'is_default', 'is_active',
        'logo_path', 'primary_color', 'font_family',
        'header_html', 'footer_html', 'custom_css',
        'watermark_enabled', 'watermark_text', 'watermark_opacity',
        'show_branch_header', 'show_branch_footer',
        'page_size', 'orientation',
        'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
        'created_by',
    ];

    protected $casts = [
        'is_default'        => 'boolean',
        'is_active'         => 'boolean',
        'watermark_enabled' => 'boolean',
        'watermark_opacity' => 'float',
        'show_branch_header'=> 'boolean',
        'show_branch_footer'=> 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
