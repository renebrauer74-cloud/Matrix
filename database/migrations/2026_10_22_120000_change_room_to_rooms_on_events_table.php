<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eine Veranstaltung kann in mehreren Räumen stattfinden: aus „room“ wird die Liste „rooms“.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('rooms')->nullable()->after('ends_at');
        });

        DB::table('events')
            ->whereNotNull('room')
            ->where('room', '<>', '')
            ->eachById(fn (object $event) => DB::table('events')
                ->where('id', $event->id)
                ->update(['rooms' => json_encode([$event->room])]));

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('room', 80)->nullable()->after('ends_at');
        });

        DB::table('events')
            ->whereNotNull('rooms')
            ->eachById(fn (object $event) => DB::table('events')
                ->where('id', $event->id)
                ->update(['room' => mb_substr(implode(', ', json_decode($event->rooms, true) ?? []), 0, 80) ?: null]));

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('rooms');
        });
    }
};
