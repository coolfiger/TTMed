<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('business_name', 150)->nullable();
            $table->string('database_name', 100)->unique()->nullable();
            $table->string('db_host', 100)->default('localhost');
            $table->unsignedSmallInteger('db_port')->default(3306);
            $table->string('db_username', 100)->nullable();
            $table->text('db_password')->nullable();
            $table->string('custom_domain', 150)->unique()->nullable();
            $table->enum('status', ['active', 'suspended', 'deactivated', 'trial'])->default('trial');
            $table->string('storage_path', 255)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('tenants');
    }
};
