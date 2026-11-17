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
        Schema::create('receipt_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->boolean('is_dj')->default(false);
            $table->string('real_name', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('technical_rider')->nullable();
            $table->text('hospitality_rider')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_contacts');
    }
};
