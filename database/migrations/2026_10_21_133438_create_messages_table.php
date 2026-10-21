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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('Kontakt, Fundsache, Bewerbung oder Firmenanfrage');
            $table->string('status', 20)->default('new');
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('phone', 60)->nullable();
            $table->string('subject', 200)->nullable();
            $table->text('body')->nullable();
            $table->json('details')->nullable()->comment('Felder je Formular, z. B. Stelle, Raum oder Gästezahl');
            $table->text('admin_note')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
