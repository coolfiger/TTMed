<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\DocumentTemplate;
use App\Support\DefaultTemplates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DocumentTemplate::TYPES as $type => $label) {
            // Update existing default if it has no body_html yet (pre-dates the editor)
            $blank = DB::connection('tenant')
                ->table('document_templates')
                ->where('type', $type)
                ->whereNull('branch_id')
                ->where('is_default', true)
                ->whereNull('body_html')
                ->first();

            if ($blank) {
                DB::connection('tenant')
                    ->table('document_templates')
                    ->where('id', $blank->id)
                    ->update([
                        'body_html'  => DefaultTemplates::forType($type),
                        'updated_at' => now(),
                    ]);
                continue;
            }

            // Skip if a fully-populated default already exists
            $exists = DB::connection('tenant')
                ->table('document_templates')
                ->where('type', $type)
                ->whereNull('branch_id')
                ->where('is_default', true)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::connection('tenant')->table('document_templates')->insert([
                'name'               => 'Default ' . $label,
                'type'               => $type,
                'branch_id'          => null,
                'is_default'         => true,
                'is_active'          => true,
                'primary_color'      => '#1e40af',
                'font_family'        => 'DejaVu Sans',
                'body_html'          => DefaultTemplates::forType($type),
                'gjs_data'           => null,
                'watermark_enabled'  => false,
                'watermark_text'     => null,
                'watermark_opacity'  => 0.08,
                'show_branch_header' => true,
                'show_branch_footer' => false,
                'page_size'          => 'A4',
                'orientation'        => 'portrait',
                'margin_top'         => 20,
                'margin_right'       => 20,
                'margin_bottom'      => 20,
                'margin_left'        => 20,
                'created_by'         => null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }
}
