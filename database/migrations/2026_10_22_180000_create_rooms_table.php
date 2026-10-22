<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Im Admin-Bereich gepflegte Angaben zu den Räumen – ein Eintrag je RoomEnum-Wert.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->text('description')->nullable();
            $table->string('size', 40)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedTinyInteger('bars')->default(0);
            $table->string('stage', 80)->nullable();
            $table->string('extra', 80)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
