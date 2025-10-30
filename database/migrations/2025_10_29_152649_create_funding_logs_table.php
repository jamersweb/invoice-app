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
        Schema::create('funding_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('funding_id')->nullable(); // Link to Funding if exists
            $table->date('transfer_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('bank_reference')->nullable(); // Bank transaction reference
            $table->string('internal_reference')->nullable(); // Internal reference number
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by'); // Admin user who recorded this
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('transfer_date');
            $table->index('funding_id');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_logs');
    }
};
