<?php

namespace App\Services;

use App\Models\Tenant\Branch;
use App\Models\Tenant\DocumentTemplate;

class DocumentTemplateService
{
    /**
     * Resolve the best template for a given document type and branch.
     * Priority: branch-specific default > global default > null.
     */
    public function resolve(string $type, ?int $branchId = null): ?DocumentTemplate
    {
        // Branch-specific first
        if ($branchId) {
            $template = DocumentTemplate::on('tenant')
                ->where('type', $type)
                ->where('branch_id', $branchId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Global default
        return DocumentTemplate::on('tenant')
            ->where('type', $type)
            ->whereNull('branch_id')
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Build the CSS block for a template, merging defaults with overrides.
     */
    public function buildCss(DocumentTemplate $template): string
    {
        $color = e($template->primary_color ?: '#1e40af');
        $font  = e($template->font_family ?: 'DejaVu Sans');

        $css = <<<CSS
            :root { --brand: {$color}; }
            body { font-family: '{$font}', DejaVu Sans, sans-serif; }
            .brand-color { color: {$color}; }
            .brand-border { border-color: {$color}; }
            .brand-bg { background-color: {$color}; }
        CSS;

        if ($template->custom_css) {
            $css .= "\n" . $template->custom_css;
        }

        return $css;
    }

    /**
     * Build watermark CSS if enabled.
     */
    public function buildWatermarkCss(DocumentTemplate $template): string
    {
        if (!$template->watermark_enabled || !$template->watermark_text) {
            return '';
        }

        $opacity = number_format((float) $template->watermark_opacity, 2);
        $text    = addslashes(e($template->watermark_text));

        return <<<CSS
            .watermark {
                position: fixed; top: 50%; left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 60pt; font-weight: bold;
                color: rgba(0,0,0,{$opacity});
                z-index: -1; white-space: nowrap;
                pointer-events: none;
            }
        CSS;
    }

    /**
     * Render the watermark element if enabled.
     */
    public function watermarkHtml(DocumentTemplate $template): string
    {
        if (!$template->watermark_enabled || !$template->watermark_text) {
            return '';
        }

        return '<div class="watermark">' . e($template->watermark_text) . '</div>';
    }

    /**
     * Build page margins config string for DOMPDF.
     */
    public function margins(DocumentTemplate $template): array
    {
        return [
            'top'    => ($template->margin_top ?? 20) . 'mm',
            'right'  => ($template->margin_right ?? 20) . 'mm',
            'bottom' => ($template->margin_bottom ?? 20) . 'mm',
            'left'   => ($template->margin_left ?? 20) . 'mm',
        ];
    }

    /**
     * Render the branch detail block for use in headers/footers.
     */
    public function branchBlock(?Branch $branch): string
    {
        if (!$branch) {
            return '';
        }

        $lines = array_filter([
            '<strong>' . e($branch->name) . '</strong>',
            $branch->address ? e($branch->address) : null,
            $branch->phone   ? 'Tel: ' . e($branch->phone) : null,
            $branch->email   ? e($branch->email) : null,
        ]);

        return implode('<br>', $lines);
    }
}
