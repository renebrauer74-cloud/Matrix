<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Serien (wiederkehrende Termine teilen sich eine series_id), zeitgesteuerte Highlights
     * und ein YouTube-Link. Eigene Videos liegen als Media in der Collection „event-videos“.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->uuid('series_id')->nullable()->after('id');
            $table->string('video_url', 500)->nullable()->after('social_links');
            $table->dateTime('featured_from')->nullable()->after('is_featured');
            $table->dateTime('featured_until')->nullable()->after('featured_from');
            $table->unsignedSmallInteger('featured_lead_days')->nullable()->after('featured_until')
                ->comment('Highlight so viele Tage vor Beginn – bei Serien je Termin neu berechnet');

            $table->index(['series_id', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['series_id', 'starts_at']);
            $table->dropColumn(['series_id', 'video_url', 'featured_from', 'featured_until', 'featured_lead_days']);
        });
    }
};
