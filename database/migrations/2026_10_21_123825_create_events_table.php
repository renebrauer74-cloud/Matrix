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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('subtitle')->nullable();
            $table->string('category', 20)->default('konzert');
            $table->string('category_label', 80)->nullable()->comment('Bei Sonstiges: Art (z. B. Lesung). Sonst: Genre/Motto');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->time('doors_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('room', 80)->nullable();
            $table->string('image_focus', 10)->default('center');
            $table->string('ticket_url', 500)->nullable();
            $table->string('price_presale', 60)->nullable();
            $table->string('price_box', 60)->nullable();
            $table->string('age_rating', 40)->nullable();
            $table->string('ticket_status', 20)->default('available');
            $table->string('status', 20)->default('published');
            $table->boolean('is_featured')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['category', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
