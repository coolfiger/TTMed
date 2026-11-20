<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('document_templates', function (Blueprint $table) {
            $table->longText('body_html')->nullable()->after('custom_css');
            $table->json('gjs_data')->nullable()->after('body_html');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('document_templates', function (Blueprint $table) {
            $table->dropColumn(['body_html', 'gjs_data']);
        });
    }
};
