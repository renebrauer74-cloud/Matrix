<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('receipt_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('street', 150);
            $table->string('zip_city', 150);
            $table->string('tax_number', 40)->nullable();
            $table->string('vat_id', 40)->nullable();
            $table->string('contact', 190)->nullable();
            $table->boolean('is_small_business')->default(false);
            $table->string('default_place', 100)->nullable();
            $table->string('default_tax', 4)->default('19');
            $table->string('prefix', 10)->unique();
            $table->json('counters')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_companies');
    }
};
