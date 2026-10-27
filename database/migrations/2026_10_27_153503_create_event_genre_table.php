<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Zuordnung Veranstaltung ↔ Genre. Das bisherige Freitextfeld „Genre / Motto“ bei Konzert und Party
     * wird in echte Genres überführt (z. B. „2000s & Charts“ → „2000s“ und „Charts“).
     */
    public function up(): void
    {
        Schema::create('event_genre', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'genre_id']);
        });

        $events = DB::table('events')
            ->where('category', '!=', 'sonstiges')
            ->whereNotNull('category_label')
            ->where('category_label', '!=', '')
            ->get(['id', 'category_label']);

        foreach ($events as $event) {
            foreach (preg_split('/\s*[,\/&]\s*/u', $event->category_label) ?: [] as $name) {
                $name = trim($name);

                if ($name === '') {
                    continue;
                }

                $slug = Str::slug($name, '-', 'de');
                $genreId = DB::table('genres')->where('slug', $slug)->value('id')
                    ?? DB::table('genres')->insertGetId(['name' => $name, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);

                DB::table('event_genre')->insertOrIgnore(['event_id' => $event->id, 'genre_id' => $genreId]);
            }
        }

        DB::table('events')->where('category', '!=', 'sonstiges')->update(['category_label' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_genre');
    }
};
