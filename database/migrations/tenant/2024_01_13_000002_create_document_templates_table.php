<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30); // prescription, invoice, receipt, lab_report, imaging_report, certificate, referral, generic
            $table->unsignedBigInteger('branch_id')->nullable(); // null = all branches
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // Branding
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->default('#1e40af');
            $table->string('font_family', 60)->default('DejaVu Sans');

            // Content overrides
            $table->text('header_html')->nullable();
            $table->text('footer_html')->nullable();
            $table->text('custom_css')->nullable();

            // Watermark
            $table->boolean('watermark_enabled')->default(false);
            $table->string('watermark_text', 100)->nullable();
            $table->decimal('watermark_opacity', 3, 2)->default(0.08);

            // Branch detail placement
            $table->boolean('show_branch_header')->default(true);
            $table->boolean('show_branch_footer')->default(false);

            // Page layout
            $table->string('page_size', 10)->default('A4');
            $table->string('orientation', 10)->default('portrait');
            $table->unsignedTinyInteger('margin_top')->default(20);
            $table->unsignedTinyInteger('margin_right')->default(20);
            $table->unsignedTinyInteger('margin_bottom')->default(20);
            $table->unsignedTinyInteger('margin_left')->default(20);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->index(['type', 'branch_id', 'is_default', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('document_templates');
    }
};
