<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_positions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('summary')->nullable();
            $table->text('description')->nullable()->comment('Aufgaben, eine pro Zeile');
            $table->text('requirements')->nullable()->comment('Was mitgebracht werden sollte, eine Angabe pro Zeile');
            $table->string('employment', 80)->nullable()->comment('z. B. Minijob · Aushilfe');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Die drei Stellen, die immer gesucht werden – im Admin unter „Jobs“ anpassbar.
        $now = now();
        DB::table('job_positions')->insert([
            [
                'title' => 'Service an der Theke',
                'summary' => 'Drinks mixen, zapfen, kassieren – du sorgst dafür, dass niemand auf dem Trockenen sitzt.',
                'description' => "Getränke zubereiten und ausgeben\nKassieren mit Kartenzahlung und bar\nTheke sauber und aufgefüllt halten",
                'requirements' => "Mindestens 18 Jahre\nFreundlich und belastbar, auch wenn es voll wird\nErfahrung in der Gastro ist schön, aber kein Muss",
                'employment' => 'Minijob · Aushilfe',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Garderobe',
                'summary' => 'Der erste und letzte Eindruck des Abends: Jacken annehmen, Marken ausgeben, den Überblick behalten.',
                'description' => "Garderobe annehmen und ausgeben\nGarderobengebühr kassieren\nOrdnung halten, auch zur Stoßzeit",
                'requirements' => "Mindestens 18 Jahre\nZuverlässig und ordentlich\nFreundlicher Umgang mit Gästen",
                'employment' => 'Minijob · Aushilfe',
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Kasse',
                'summary' => 'Am Einlass verkaufst du Tickets an der Abendkasse und prüfst Vorverkaufstickets.',
                'description' => "Tickets an der Abendkasse verkaufen\nVorverkaufstickets scannen und Gästelisten abgleichen\nKassenabrechnung am Ende der Schicht",
                'requirements' => "Mindestens 18 Jahre\nSicher im Umgang mit Geld\nGenau und auch bei Andrang entspannt",
                'employment' => 'Minijob · Aushilfe',
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_positions');
    }
};
