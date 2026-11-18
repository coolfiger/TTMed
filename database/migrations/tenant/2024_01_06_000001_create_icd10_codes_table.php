<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('icd10_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('description', 500);
            $table->string('category_code', 10)->nullable()->index();
            $table->string('category_description', 300)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['code', 'description']);
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('icd10_codes');
    }
};
