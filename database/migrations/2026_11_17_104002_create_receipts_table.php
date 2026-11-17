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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 40)->unique();
            $table->string('type', 12);
            $table->date('date');
            $table->unsignedInteger('amount_cents');
            $table->string('tax', 4);
            $table->foreignId('receipt_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('receipt_employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('partner_name', 150);
            $table->text('partner_address')->nullable();
            $table->text('purpose');
            $table->string('payment_method', 20);
            $table->string('place', 100);
            $table->string('signer_name', 150)->nullable();
            $table->json('company_snapshot');
            $table->unsignedSmallInteger('signature_width')->nullable();
            $table->unsignedSmallInteger('signature_height')->nullable();
            $table->foreignId('corrects_receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['receipt_company_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
